<?php

namespace Tests\Browser\Scenarios;

use App\Models\AccountCreditLine;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * S5 — UI smoke for "goal progress with an active borrowing".
 *
 * Opens a credit line on account 7, then visits the account dashboard and
 * confirms the goal-progress card renders without erroring. This is a UI-only
 * smoke — the canonical net-vs-gross assertion lives in
 * tests/Feature/Scenarios/S5GoalReactsToBorrowingTest.php, where one branch
 * is intentionally skipped pending the AccountTrait::createGoalsResponse fix
 * (see memory `goal-current-is-net`).
 *
 * If a future change rebinds the goal "Current" to AccountTrait.php:146
 * properly, that feature test flips from skipped to passing, and this smoke
 * test stays as the UI-level regression net.
 *
 * INFRA NOTE: see S1MultiLineSmokeTest header — Dusk runs in the main
 * `familyfund` container only.
 */
class S5GoalWithBorrowingSmokeTest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;

    public function test_goal_progress_card_renders_while_a_borrowing_is_active(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, principalShares: 50, termMonths: 6, descr: 'S5 smoke borrowing');

            // The account dashboard surfaces goal progress (if the beneficiary
            // has any goals configured). The card must at least render without
            // throwing — that is the UI-level invariant we lock in here.
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID)
                ->waitForLocation('/accounts/' . self::ACCOUNT_ID, 10)
                ->assertDontSee('Whoops!')                     // generic Laravel error
                ->assertDontSee('Undefined');                  // PHP notice surfaces

            // The credit line itself is reachable from the dashboard layout.
            $browser->visit('/credit-lines/' . $lineId)
                ->assertSee('Credit Line #' . $lineId);

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
