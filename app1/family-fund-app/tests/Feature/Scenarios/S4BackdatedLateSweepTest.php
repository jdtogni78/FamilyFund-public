<?php

namespace Tests\Feature\Scenarios;

use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S4 — Backdated draw + late sweep + catch-up repayment.
 *
 * Opens a line dated 6 months ago, lets LateDetector mark every past-due
 * scheduled row late, then drops a single catch-up REP onto the oldest row.
 *
 * Invariants:
 *   - DrawService::open with a backdated origination immediately flags
 *     past-due rows late (no sweep call needed); a second sweep is a no-op.
 *   - A targeted REP via repayRow on a LATE row settles that row to PAID
 *     without touching siblings.
 *   - Future-due rows stay SCHEDULED even after the late backlog has formed.
 */
class S4BackdatedLateSweepTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_backdated_origination_flags_past_due_rows_late_then_catch_up_settles_oldest(): void
    {
        $this->s->withBorrowingPower('bD', 500);

        $origination = Carbon::today()->subMonths(6);
        $line = $this->s->openLine('bD', 120, 12, 'monthly', $origination, 'S4: backdated 6mo');

        $rows = $this->s->rows($line);
        $this->assertSame(12, $rows->count());

        // Past-due rows are LATE *immediately* (DrawService delegates to
        // LateDetector at the tail of open()).
        $lateRows = $rows->where('status', CreditLinePayment::STATUS_LATE);
        $this->assertGreaterThanOrEqual(5, $lateRows->count(),
            '6-month backdate, monthly frequency → expect ≥5 past-due rows already late');

        $futureRows = $rows->where('status', CreditLinePayment::STATUS_SCHEDULED);
        $this->assertGreaterThanOrEqual(5, $futureRows->count(),
            'future-due rows must remain scheduled after the backdated draw');

        // A second sweep is a no-op (idempotency).
        $beforeStatuses = $this->statusCounts($line);
        $this->s->sweepLate();
        $afterStatuses = $this->statusCounts($line);
        $this->assertSame($beforeStatuses, $afterStatuses, 'late sweep must be idempotent');

        // Catch-up REP on the oldest LATE row.
        $oldestLate = $rows->where('status', CreditLinePayment::STATUS_LATE)
            ->sortBy('due_date')->first();
        $this->assertNotNull($oldestLate);
        $this->s->repay->repayRow($oldestLate, (float) $oldestLate->shares_due, Carbon::today());

        $oldestLate->refresh();
        $this->assertSame(CreditLinePayment::STATUS_PAID, $oldestLate->status);

        // Sibling late rows are unaffected.
        $stillLate = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('id', '!=', $oldestLate->id)
            ->where('status', CreditLinePayment::STATUS_LATE)
            ->count();
        $this->assertGreaterThanOrEqual(4, $stillLate,
            'catch-up REP must only settle its target row');
    }

    public function test_credit_line_show_renders_for_backdated_line(): void
    {
        $this->s->withBorrowingPower('bD', 500);
        $line = $this->s->openLine('bD', 120, 12, 'monthly', Carbon::today()->subMonths(6), 'S4: backdated UI');

        $this->actingAs($this->s->admin)
            ->get(route('credit_lines.show', ['line' => $line->id]))
            ->assertOk()
            ->assertSee('Credit Line #' . $line->id);
    }

    /** @return array<string,int> */
    private function statusCounts($line): array
    {
        return $line->payments()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();
    }
}
