<?php

namespace Tests\Browser\Scenarios;

use App\Models\AccountCreditLine;
use Carbon\Carbon;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * S9 — UI smoke for the late-payment banner / delay-notification surface.
 *
 * Opens a credit line with a backdated origination so its first row is
 * past due, then visits the loan-share show page and asserts the
 * "Late" badge (or equivalent) renders. The feature test
 * (tests/Feature/Scenarios/S9DelayNotificationDedupTest.php) covers the
 * email-dedup math; this smoke proves the UI surfaces the late state
 * without erroring.
 *
 * INFRA NOTE: see S7FundSummarySmokeTest header.
 */
class S9LateNotificationSmokeTest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;

    public function test_loan_share_show_renders_late_state_for_backdated_line(): void
    {
        $this->browse(function (Browser $browser) {
            $originationDate = Carbon::today()->subDays(30)->toDateString();
            $lineId = $this->openLineViaUiBackdated(
                $browser,
                principalShares: 60,
                termMonths: 6,
                descr: 'S9 late smoke',
                originationDate: $originationDate
            );

            $browser->visit('/credit-lines/' . $lineId)
                ->waitFor('.card', 5)
                ->assertSee('Loan Share #' . $lineId)
                ->assertDontSee('Whoops!')
                ->assertDontSee('Undefined');

            // At least one schedule row will be flagged late on a 30-day
            // backdated draw (default grace is 3 days). The status pill text
            // appears in the schedule table.
            $browser->assertSeeIn('table.table', 'Late');

            $this->cleanupLine($lineId);
        });
    }

    private function openLineViaUiBackdated(
        Browser $browser,
        float $principalShares,
        int $termMonths,
        string $descr,
        string $originationDate,
    ): int {
        $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
            ->waitFor('form[action*="/credit-lines"]')
            ->type('input[name="nickname"]', $descr)
            ->type('input[name="principal_shares"]', (string) $principalShares)
            ->clear('input[name="term_months"]')
            ->type('input[name="term_months"]', (string) $termMonths)
            ->select('select[name="payment_frequency"]', 'monthly')
            ->type('input[name="descr"]', $descr);

        // Set the date via JS — Dusk's ->type() on a native HTML5 date
        // input enters characters into the month/day/year segments rather
        // than the field value, which garbles to e.g. year 0421. Setting
        // .value directly and dispatching 'change' is the supported pattern
        // for Selenium against <input type="date">.
        if ($browser->element('input[name="origination_date"]')) {
            $browser->script(
                "var el = document.querySelector('input[name=\"origination_date\"]');"
                . "el.value = '" . $originationDate . "';"
                . "el.dispatchEvent(new Event('change', {bubbles:true}));"
            );
        }

        $browser->press('Open')
            ->waitUsing(10, 200, function () use ($browser) {
                $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
                return (bool) preg_match('#/credit-lines/\d+$#', $path);
            });

        $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
        if (preg_match('#/credit-lines/(\d+)$#', $path, $m)) {
            return (int) $m[1];
        }
        return (int) AccountCreditLine::where('descr', $descr)->latest('id')->firstOrFail()->id;
    }

    private function cleanupLine(int $lineId): void
    {
        try {
            $line = AccountCreditLine::find($lineId);
            if ($line && $line->status === 'active') {
                $line->update(['status' => 'cancelled']);
            }
        } catch (\Throwable $e) {
            // leave for inspection
        }
    }
}
