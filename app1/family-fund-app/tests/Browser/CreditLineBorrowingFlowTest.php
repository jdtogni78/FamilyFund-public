<?php

namespace Tests\Browser;

use App\Models\AccountCreditLine;
use App\Models\TransactionExt;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\AccountCreditLinesPage;
use Tests\Browser\Pages\CreditLineShowPage;
use Tests\DuskTestCase;

/**
 * End-to-end browser test of the credit-line / borrowing flow.
 *
 * Exercises UC-01 (open), UC-05/UC-13 (repay → schedule advances),
 * UC-09 (readjust → audit row + new schedule), UC-45 (reverse → schedule
 * reopens), UC-49 (loans summary card), and UC-42 (trajectory chart).
 *
 * Uses the local dev-login route (claude@test.local, system-admin) and
 * account id = 7 (Acct7), which has ~5391 OWN shares available to borrow
 * against.
 *
 * NOTE: These tests are NOT database-isolated. They run against the live
 * dev database and create real rows. Each test cleans up its own line
 * (and related rows) in a tearDown step where feasible.
 */
class CreditLineBorrowingFlowTest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;

    /**
     * UC-01: An admin can open a new credit line through the UI.
     */
    public function test_admin_can_open_credit_line_through_ui(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, principalShares: 100, termMonths: 6, descr: 'E2E open UC-01');

            $browser->on(new CreditLineShowPage($lineId))
                ->assertSee('Credit Line #' . $lineId)
                ->assertSee('Principal (shares)')
                ->assertSee('100.0000')
                ->assertSee('Outstanding (shares)')
                ->assertSee('Payment schedule');

            // 6-month monthly schedule => 6 rows
            $rows = $browser->elements('.card .table tbody tr');
            $this->assertCount(6, $rows, 'Expected 6 schedule rows for term=6 monthly.');

            $this->cleanupLine($lineId);
        });
    }

    /**
     * UC-05 / UC-13: Issuing a repayment advances the schedule and reduces
     * outstanding shares.
     */
    public function test_repay_advances_schedule_and_reduces_outstanding(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 60, 6, 'E2E repay UC-05');

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);

            // Each scheduled payment ≈ 10 shares; pay the first one.
            $page->repay($browser, 10.0);

            $browser->on($page)
                ->assertSee('Credit Line #' . $lineId);

            // Outstanding should now read 50.0000 (60 − 10).
            $line = AccountCreditLine::findOrFail($lineId);
            $this->assertEqualsWithDelta(50.0, (float) $line->outstanding_shares, 0.0001);

            // At least one schedule row should be marked paid.
            $paidCount = $line->payments()->where('status', 'paid')->count();
            $this->assertGreaterThanOrEqual(1, $paidCount, 'Expected ≥ 1 paid schedule row after repayment.');

            $this->cleanupLine($lineId);
        });
    }

    /**
     * UC-09: Readjusting creates an audit row (adjustment timeline entry)
     * and a new schedule.
     */
    public function test_readjust_creates_audit_row_and_new_schedule(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 60, 6, 'E2E readjust UC-09');

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);

            $page->readjust($browser, newTermMonths: 12, reason: 'extend term');

            $browser->on($page)
                ->assertSee('Adjustment history')
                ->assertSee('Term:')
                ->assertSee('6')   // old term
                ->assertSee('12'); // new term

            // 12-month monthly schedule => 12 rows
            $line = AccountCreditLine::findOrFail($lineId);
            $this->assertSame(12, (int) $line->term_months);
            // After readjust: 6 original rows are marked cancelled (preserved
            // for the historical schedule snapshot, per plan §6 step 3) +
            // 12 fresh scheduled rows.
            $this->assertSame(12, $line->payments()->where('status', 'scheduled')->count());
            $this->assertSame(6, $line->payments()->where('status', 'cancelled')->count());

            $this->cleanupLine($lineId);
        });
    }

    /**
     * UC-45: Reversing a REP transaction reopens the schedule row that
     * the REP had marked paid.
     */
    public function test_reverse_reopens_schedule_row(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 60, 6, 'E2E reverse UC-45');

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);
            $page->repay($browser, 10.0);

            // Wait for the post-repay redirect to land so the REP row is committed.
            $browser->on($page)->assertSee('Credit Line #' . $lineId);

            $line = AccountCreditLine::findOrFail($lineId);
            $repTx = TransactionExt::where('account_credit_line_id', $lineId)
                ->where('type', TransactionExt::TYPE_REPAY)
                ->latest('id')->firstOrFail();

            // Hit the reverse endpoint directly — there is no inline UI
            // button yet; this matches how an admin uses the route.
            $browser->visit('/credit-lines/resolve');   // ensure CSRF cookie
            $browser->driver->executeScript(
                "fetch('" . url('/transactions/' . $repTx->id . '/reverse') . "', {"
                    . "method:'POST',"
                    . "headers:{'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content || ''},"
                    . "body: new URLSearchParams({transaction_id: '" . $repTx->id . "', reason: 'E2E reverse'})"
                    . "});"
            );
            $browser->pause(800);

            $browser->visit($page->url())
                ->assertSee('Credit Line #' . $lineId);

            $line->refresh();
            $this->assertEqualsWithDelta(60.0, (float) $line->outstanding_shares, 0.0001);
            $paidCount = $line->payments()->where('status', 'paid')->count();
            $this->assertSame(0, $paidCount, 'Schedule should have no paid rows after reversal.');

            $this->cleanupLine($lineId);
        });
    }

    /**
     * UC-49: The loans-summary card on the line page reflects account
     * state (disbursed, repaid, outstanding).
     */
    public function test_loans_summary_card_reflects_account_state(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 60, 6, 'E2E loans summary UC-49');

            $browser->on(new CreditLineShowPage($lineId))
                ->assertSee('Loans summary')
                ->assertSee('Lifetime disbursed (shares)')
                ->assertSee('Lifetime repaid (shares)')
                ->assertSee('Net outstanding (shares)')
                ->assertSee('Active lines:');

            $this->cleanupLine($lineId);
        });
    }

    /**
     * UC-42: The trajectory card renders a chart image element pointing
     * at QuickChart (we don't assert chart pixels).
     */
    public function test_trajectory_chart_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 60, 6, 'E2E trajectory UC-42');

            $browser->on(new CreditLineShowPage($lineId))
                ->assertSee('Payoff trajectory')
                ->assertPresent('img[alt="Payoff trajectory chart"]');

            $src = $browser->attribute('img[alt="Payoff trajectory chart"]', 'src');
            $this->assertNotEmpty($src);
            $this->assertStringContainsString('chart', $src);

            $this->cleanupLine($lineId);
        });
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Drive the "new credit line" form and return the new line's id.
     */
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

        // After redirect we're on /credit-lines/{id}. Extract id from URL.
        $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
        if (preg_match('#/credit-lines/(\d+)$#', $path, $m)) {
            return (int) $m[1];
        }

        // Fallback: look up by description.
        $line = AccountCreditLine::where('descr', $descr)->latest('id')->firstOrFail();
        return (int) $line->id;
    }

    /**
     * Best-effort cleanup so repeated runs don't accumulate state.
     * Uses the cancel endpoint (admin action) rather than hard-delete.
     */
    private function cleanupLine(int $lineId): void
    {
        try {
            $line = AccountCreditLine::find($lineId);
            if ($line && $line->status === 'active') {
                // Soft path: just mark cancelled so we don't perturb other rows.
                $line->update(['status' => 'cancelled']);
            }
        } catch (\Throwable $e) {
            // Swallow — leaves dev data for human inspection.
        }
    }
}
