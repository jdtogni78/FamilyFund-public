<?php

namespace Tests\Feature;

use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * #9 — "Catch up" helper button on the credit-line show page.
 *
 * When a line has one or more `late` schedule rows, an admin-only "Catch up"
 * button appears whose label states the total shares due across all late rows
 * and which (client-side) pre-fills the repay modal with that amount. When no
 * row is late, the button is absent — only the regular "Make payment" remains.
 */
class CreditLineCatchUpButtonTest extends TestCase
{
    use DatabaseTransactions;

    public function test_catch_up_button_shows_total_late_shares_when_rows_are_late(): void
    {
        $s = CreditLineScenarioBuilder::make();
        $s->withBorrowingPower('main', 5000);

        // Backdated 9-month-old line over 12 monthly rows -> several past due.
        $line = $s->openLine('main', 120, 12, 'monthly', Carbon::today()->subMonths(9), 'catch-up test');
        $s->sweepLate();

        $lateShares = (float) CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_LATE)
            ->sum('shares_due');

        // The fixture must actually produce late rows or the test proves nothing.
        $this->assertGreaterThan(0.0, $lateShares, 'fixture should produce at least one late row');

        $response = $this->actingAs($s->admin)
            ->get(route('credit_lines.show', ['line' => $line->id]));
        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('id="catchUpBtn"', $html, 'Catch-up button should render.');
        $this->assertStringContainsString(
            'Catch up (' . number_format($lateShares, 4) . ' sh)',
            $html,
            'Catch-up button label should state the total late shares due.'
        );
        // The button carries the machine-readable amount the JS pre-fills.
        $this->assertStringContainsString(
            'data-catchup-shares="' . number_format($lateShares, 4, '.', '') . '"',
            $html
        );

        while (ob_get_level() > 1) { @ob_end_clean(); }
    }

    public function test_no_catch_up_button_when_no_rows_are_late(): void
    {
        $s = CreditLineScenarioBuilder::make();
        $s->withBorrowingPower('main', 5000);

        // Opened today -> first row due in the future -> nothing late.
        $line = $s->openLine('main', 120, 12, 'monthly', Carbon::today(), 'no-late test');
        $s->sweepLate();

        $this->assertSame(
            0,
            CreditLinePayment::where('account_credit_line_id', $line->id)
                ->where('status', CreditLinePayment::STATUS_LATE)
                ->count()
        );

        $response = $this->actingAs($s->admin)
            ->get(route('credit_lines.show', ['line' => $line->id]));
        $response->assertOk();

        $html = $response->getContent();
        // The button (id="catchUpBtn") is absent; the modal-prefill <script>
        // that references the data attr is always present, so key off the id.
        $this->assertStringNotContainsString('id="catchUpBtn"', $html, 'No catch-up button when nothing is late.');
        // Sanity: the regular Make payment button is still present on an active line.
        $this->assertStringContainsString('Make payment', $html);

        while (ob_get_level() > 1) { @ob_end_clean(); }
    }
}
