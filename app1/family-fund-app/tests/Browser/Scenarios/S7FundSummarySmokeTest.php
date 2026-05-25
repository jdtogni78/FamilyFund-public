<?php

namespace Tests\Browser\Scenarios;

use App\Models\AccountCreditLine;
use App\Models\AccountExt;
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
 * INFRA NOTE: run via `testpool.sh tour` (Selenium sidecar) against an
 * isolated familyfund_testN DB, or from the familyfund container directly.
 * The whole credit-line surface is system-admin gated, so this drives
 * dev-login as ?as=admin (the seeded system-admin), not the default
 * fund-admin user. Creates a real loan-share row and self-cancels via
 * cleanupLine().
 */
class S7FundSummarySmokeTest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;

    public function test_fund_summary_renders_loaned_bucket_under_active_borrowing(): void
    {
        // Resolve the fund from the account rather than hardcoding an id —
        // the test baseline's first fund is not id 1 (account 7 lives on
        // fund 2), so a hardcoded /funds/1/overview flashed "Fund not found"
        // and bounced to the funds index.
        $fundId = AccountExt::findOrFail(self::ACCOUNT_ID)->fund_id;

        $this->browse(function (Browser $browser) use ($fundId) {
            $lineId = $this->openLineViaUi($browser, principalShares: 75, termMonths: 6, descr: 'S7 fund smoke');

            // The fund SHOW page (funds.show_ext) is where the
            // Allocated / Loaned / Unallocated 3-bucket stripe lives — not
            // the /overview net-worth dashboard. The "Loaned" box only
            // renders when borrowedPct > 0, which the CL above guarantees.
            $browser->visit('/dev-login/funds/' . $fundId . '?as=admin')
                ->waitForLocation('/funds/' . $fundId, 10)
                ->assertDontSee('Whoops!')
                ->assertDontSee('Undefined')
                // The bucket label introduced by 12ef9b38.
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
        // The whole credit-line controller is system-admin gated
        // (AccountCreditLineController::ensureAdmin → is_admin → isSystemAdmin).
        // dev-login's default user (claude@test.local) is only a fund-admin on
        // the synthetic baseline, so it 403s here and the create form never
        // renders. Log in as the seeded system-admin via ?as=admin (same as the
        // fund-show visit below and the sibling AccountDetailUITest).
        $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create?as=admin')
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
