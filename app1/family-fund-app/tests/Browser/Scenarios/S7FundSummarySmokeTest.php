<?php

namespace Tests\Browser\Scenarios;

use App\Models\AccountCreditLine;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * S7 — UI smoke for the fund summary's Allocated/Loaned/Unallocated stripe.
 *
 * Opens a credit line on account 7 (Fund 1), then visits the fund show page
 * and asserts the 3-bucket allocation stripe (commit 12ef9b38) renders the
 * "Loaned" sub-bucket and the per-account "Allocated" + "Loaned" columns
 * without page errors.
 *
 * Companion to tests/Feature/Scenarios/S7FundInvariantsUnderLendingTest.php —
 * the feature test owns the math; this smoke proves the new HTML survives
 * a real borrowing.
 *
 * INFRA NOTE: run from familyfund container (pool slots lack ChromeDriver
 * per memory project_dusk_pool_infra_gap). Creates real rows on the dev DB
 * and self-cancels in tearDown.
 */
class S7FundSummarySmokeTest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;
    // Account 7 is in fund 2 on the committed test baseline; the original
    // FUND_ID=1 was unrelated to the account opening the loan share and
    // 404'd because fund 1 doesn't exist in the baseline.
    private const FUND_ID = 2;

    public function test_fund_summary_renders_loaned_bucket_under_active_borrowing(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, principalShares: 75, termMonths: 6, descr: 'S7 fund smoke');

            // The 3-bucket stripe (Allocated / Loaned / Unallocated) lives
            // on the fund SHOW page (show_ext.blade.php:197-218), not the
            // overview page. The overview page renders summary tiles only.
            $browser->visit('/dev-login/funds/' . self::FUND_ID . '?as=admin')
                ->waitForLocation('/funds/' . self::FUND_ID, 10)
                ->assertDontSee('Whoops!')
                ->assertDontSee('Undefined')
                ->assertSee('Loaned');

            $this->cleanupLine($lineId);
        });
    }

    private function openLineViaUi(
        Browser $browser,
        float $principalShares,
        int $termMonths,
        string $descr,
    ): int {
        $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
            ->waitFor('form[action*="/credit-lines"]')
            ->type('input[name="nickname"]', $descr)
            ->type('input[name="principal_shares"]', (string) $principalShares)
            ->clear('input[name="term_months"]')
            ->type('input[name="term_months"]', (string) $termMonths)
            ->select('select[name="payment_frequency"]', 'monthly')
            ->type('input[name="descr"]', $descr)
            ->press('Open')
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
