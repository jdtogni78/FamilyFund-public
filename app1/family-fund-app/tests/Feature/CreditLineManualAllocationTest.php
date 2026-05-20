<?php

namespace Tests\Feature;

use App\Models\CreditLinePayment;
use App\Models\CreditLinePaymentAllocation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * Admin manual (re-)allocation of a REP transaction across schedule rows —
 * the path for payments that can't be matched 1:1 to an installment.
 */
class CreditLineManualAllocationTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $scenario;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->scenario = CreditLineScenarioBuilder::make();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    /** Line of 90 over 9 monthly => 10 shares/row; one 10-share payment. */
    private function lineWithOnePayment(): array
    {
        $this->scenario->withBorrowingPower('main', 500);
        $line = $this->scenario->openLine(
            'main', 90, 9, 'monthly',
            Carbon::today()->subMonths(3),
            'manual-alloc'
        );
        // Auto-allocates oldest-first onto row 0.
        $tran = $this->scenario->repay($line, 10.0);

        $rows = $this->scenario->rows($line->refresh())->values();
        $this->assertSame(CreditLinePayment::STATUS_PAID, $rows->get(0)->status);

        return [$line->refresh(), $tran->refresh(), $rows];
    }

    public function test_admin_can_reallocate_a_payment_to_a_different_row(): void
    {
        [$line, $tran, $rows] = $this->lineWithOnePayment();
        $row0 = $rows->get(0);
        $row2 = $rows->get(2);

        // Form renders.
        $this->actingAs($this->scenario->admin)
            ->get(route('credit_lines.payments.allocate_form', [
                'line' => $line->id, 'transaction' => $tran->id,
            ]))
            ->assertOk()
            ->assertSee('#' . $tran->id);

        // Move the whole 10 shares from row 0 to row 2.
        $response = $this->actingAs($this->scenario->admin)
            ->post(route('credit_lines.payments.allocate', [
                'line' => $line->id, 'transaction' => $tran->id,
            ]), ['allocations' => [$row2->id => 10.0]]);

        $response->assertRedirect(route('credit_lines.show', ['line' => $line->id]));

        // Ledger moved entirely onto row 2.
        $this->assertSame(0, CreditLinePaymentAllocation::where('transaction_id', $tran->id)
            ->where('credit_line_payment_id', $row0->id)->count());
        $this->assertEqualsWithDelta(10.0, (float) CreditLinePaymentAllocation::where('transaction_id', $tran->id)
            ->where('credit_line_payment_id', $row2->id)->sum('shares'), 1e-4);

        // Row statuses re-derived.
        $this->assertContains($row0->refresh()->status, [
            CreditLinePayment::STATUS_SCHEDULED,
            CreditLinePayment::STATUS_LATE,
        ]);
        $this->assertSame(CreditLinePayment::STATUS_PAID, $row2->refresh()->status);
    }

    public function test_over_allocation_is_rejected_and_leaves_ledger_unchanged(): void
    {
        [$line, $tran, $rows] = $this->lineWithOnePayment();
        $row0 = $rows->get(0);

        $response = $this->actingAs($this->scenario->admin)
            ->post(route('credit_lines.payments.allocate', [
                'line' => $line->id, 'transaction' => $tran->id,
            ]), ['allocations' => [$row0->id => 999.0]]);

        $response->assertRedirect(route('credit_lines.payments.allocate_form', [
            'line' => $line->id, 'transaction' => $tran->id,
        ]));

        // Original allocation (10 on row 0) is untouched.
        $this->assertEqualsWithDelta(10.0, (float) CreditLinePaymentAllocation::where('transaction_id', $tran->id)
            ->where('credit_line_payment_id', $row0->id)->sum('shares'), 1e-4);
        $this->assertSame(CreditLinePayment::STATUS_PAID, $row0->refresh()->status);
    }

    public function test_under_allocation_is_rejected_and_leaves_ledger_unchanged(): void
    {
        [$line, $tran, $rows] = $this->lineWithOnePayment();
        $row0 = $rows->get(0);
        $row2 = $rows->get(2);

        // 10-share payment but caller only places 4 → server must reject
        // (strict balance: every share must land on a row).
        $response = $this->actingAs($this->scenario->admin)
            ->post(route('credit_lines.payments.allocate', [
                'line' => $line->id, 'transaction' => $tran->id,
            ]), ['allocations' => [$row2->id => 4.0]]);

        $response->assertRedirect(route('credit_lines.payments.allocate_form', [
            'line' => $line->id, 'transaction' => $tran->id,
        ]));

        // Original auto-cascade allocation (10 on row 0) is untouched.
        $this->assertEqualsWithDelta(10.0, (float) CreditLinePaymentAllocation::where('transaction_id', $tran->id)
            ->where('credit_line_payment_id', $row0->id)->sum('shares'), 1e-4);
        $this->assertSame(0, CreditLinePaymentAllocation::where('transaction_id', $tran->id)
            ->where('credit_line_payment_id', $row2->id)->count());
        $this->assertSame(CreditLinePayment::STATUS_PAID, $row0->refresh()->status);
    }

    public function test_non_admin_cannot_allocate(): void
    {
        [$line, $tran] = $this->lineWithOnePayment();

        $this->actingAs($this->scenario->nonAdminUser())
            ->get(route('credit_lines.payments.allocate_form', [
                'line' => $line->id, 'transaction' => $tran->id,
            ]))
            ->assertForbidden();
    }
}
