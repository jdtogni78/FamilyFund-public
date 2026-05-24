<?php

namespace Tests\Feature;

use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * #10 — collapse adjacent `late` schedule rows on the credit-line show page.
 *
 * A run of two or more *adjacent* (consecutive in due-date order) late rows is
 * rolled up into a single "X shares overdue from D1 through D2 (N payments)"
 * summary row, with the individual rows preserved (hidden) for drill-down.
 * A lone late row, or non-adjacent late rows, are NOT rolled up. DB rows are
 * untouched — this is purely a view-layer roll-up.
 *
 * Statuses are set directly so the adjacency cases are deterministic; the view
 * groups by consecutive status regardless of how a row became late.
 */
class CreditLineLateRollupTest extends TestCase
{
    use DatabaseTransactions;

    /** @return \Illuminate\Database\Eloquent\Collection<int,CreditLinePayment> */
    private function freshRows(int $lineId)
    {
        return CreditLinePayment::where('account_credit_line_id', $lineId)
            ->orderBy('due_date')->get();
    }

    public function test_adjacent_late_rows_collapse_into_one_summary_row(): void
    {
        $s = CreditLineScenarioBuilder::make();
        $s->withBorrowingPower('main', 5000);
        // Opened today => all 12 monthly rows are 'scheduled'.
        $line = $s->openLine('main', 120, 12, 'monthly', Carbon::today(), 'late-rollup test');

        // Make rows 1,2,3 (consecutive) late.
        $rows = $s->rows($line);
        foreach ([1, 2, 3] as $k) {
            $rows[$k]->update(['status' => CreditLinePayment::STATUS_LATE]);
        }

        $late  = $this->freshRows($line->id)->whereIn('id', [$rows[1]->id, $rows[2]->id, $rows[3]->id]);
        $total = number_format((float) $late->sum('shares_due'), 4);
        $from  = Carbon::parse($rows[1]->due_date)->format('Y-m-d');
        $to    = Carbon::parse($rows[3]->due_date)->format('Y-m-d');

        $response = $this->actingAs($s->admin)->get(route('credit_lines.show', ['line' => $line->id]));
        $response->assertOk();
        $html = $response->getContent();

        // Exactly one rolled-up summary row.
        $this->assertSame(1, substr_count($html, '<tr class="late-group-summary"'));
        $this->assertStringContainsString(
            $total . ' shares overdue from ' . $from . ' through ' . $to,
            $html,
            'Summary should state total late shares and the date range.'
        );
        $this->assertStringContainsString('(3 payments)', $html);

        // Drill-down preserved: the 3 individual rows are in the DOM, hidden.
        $this->assertSame(3, substr_count($html, 'class="late-group-detail"'));
        $this->assertStringContainsString('style="display:none;"', $html);

        while (ob_get_level() > 1) { @ob_end_clean(); }
    }

    public function test_non_adjacent_late_rows_are_not_collapsed(): void
    {
        $s = CreditLineScenarioBuilder::make();
        $s->withBorrowingPower('main', 5000);
        $line = $s->openLine('main', 120, 12, 'monthly', Carbon::today(), 'non-adjacent test');

        // Rows 1 and 3 late, row 2 left scheduled => two runs of length 1.
        $rows = $s->rows($line);
        $rows[1]->update(['status' => CreditLinePayment::STATUS_LATE]);
        $rows[3]->update(['status' => CreditLinePayment::STATUS_LATE]);

        $response = $this->actingAs($s->admin)->get(route('credit_lines.show', ['line' => $line->id]));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(
            0,
            substr_count($html, '<tr class="late-group-summary"'),
            'non-adjacent late rows must not be rolled up'
        );

        while (ob_get_level() > 1) { @ob_end_clean(); }
    }

    public function test_single_late_row_is_not_collapsed(): void
    {
        $s = CreditLineScenarioBuilder::make();
        $s->withBorrowingPower('main', 5000);
        $line = $s->openLine('main', 120, 12, 'monthly', Carbon::today(), 'single-late test');

        $rows = $s->rows($line);
        $rows[1]->update(['status' => CreditLinePayment::STATUS_LATE]);

        $response = $this->actingAs($s->admin)->get(route('credit_lines.show', ['line' => $line->id]));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(
            0,
            substr_count($html, '<tr class="late-group-summary"'),
            'a single late row must not be rolled up'
        );

        while (ob_get_level() > 1) { @ob_end_clean(); }
    }
}
