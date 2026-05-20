<?php

namespace Tests\Browser\Scenarios;

use App\Models\AccountCreditLine;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\AccountCreditLinesPage;
use Tests\Browser\Pages\CreditLineShowPage;
use Tests\DuskTestCase;

/**
 * S1 — UI smoke for "multiple lines, one paid off".
 *
 * Opens two credit lines on account 7 via the create form, repays the first
 * line in full, and verifies the show page reflects paid-off status. The
 * second line stays active.
 *
 * Companion to tests/Feature/Scenarios/S1MultiLineOnePaidOffTest.php — the
 * feature test owns the invariant assertions; this Dusk test only proves the
 * UI flows render without errors and surface the load-bearing strings.
 *
 * INFRA NOTE: this test runs against the live dev DB and creates real rows
 * (matches the convention of tests/Browser/CreditLineBorrowingFlowTest.php).
 * Each line is cancelled in tearDown. Per memory `project_dusk_pool_infra_gap`,
 * Dusk only runs in the main `familyfund` container; pool slots lack
 * ChromeDriver. Run via `FF_CONTAINER=familyfund php artisan dusk
 * tests/Browser/Scenarios/S1MultiLineSmokeTest.php`.
 */
class S1MultiLineSmokeTest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;

    public function test_two_lines_open_then_first_paid_off_via_ui(): void
    {
        $this->browse(function (Browser $browser) {
            $l1Id = $this->openLineViaUi($browser, principalShares: 30, termMonths: 3, descr: 'S1 smoke: L1');
            $l2Id = $this->openLineViaUi($browser, principalShares: 60, termMonths: 6, descr: 'S1 smoke: L2');

            // Pay L1 fully via three monthly rows (10 each).
            $l1Page = new CreditLineShowPage($l1Id);
            $browser->visit($l1Page->url());
            $l1Page->repay($browser, 10.0);
            $browser->on($l1Page);
            $l1Page->repay($browser, 10.0);
            $browser->on($l1Page);
            $l1Page->repay($browser, 10.0);
            $browser->on($l1Page);

            $l1 = AccountCreditLine::findOrFail($l1Id);
            $this->assertSame('paid_off', $l1->status);
            $this->assertEqualsWithDelta(0.0, (float) $l1->outstanding_shares, 0.0001);

            // L2 stays active.
            $l2 = AccountCreditLine::findOrFail($l2Id);
            $this->assertSame('active', $l2->status);

            // The account credit-lines index page renders both lines.
            $browser->visit(new AccountCreditLinesPage(self::ACCOUNT_ID))
                ->assertSee('S1 smoke: L1')
                ->assertSee('S1 smoke: L2');

            $this->cleanupLine($l1Id);
            $this->cleanupLine($l2Id);
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
            // Leave for inspection.
        }
    }
}
