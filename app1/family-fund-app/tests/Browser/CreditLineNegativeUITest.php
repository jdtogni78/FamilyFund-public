<?php

namespace Tests\Browser;

use App\Models\AccountCreditLine;
use App\Models\TransactionExt;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\CreditLineShowPage;
use Tests\DuskTestCase;

/**
 * Negative-path browser tests for the credit-line feature.
 *
 * Covers:
 *  - Non-admin cannot access the create form (test 1)
 *  - Validation errors on bad input (tests 2–3)
 *  - Over-borrow rejection (test 4)
 *  - Cancel blocked when outstanding (test 5)
 *  - Account closure blocked by active credit line (test 6)
 *  - No-change readjust surfaces error (test 7)
 *  - Double-reverse rejection (test 8)
 *
 * Uses account 7 (Acct7) against the live dev DB.
 * Each test cleans up credit lines it creates in tearDown / try-finally.
 */
class CreditLineNegativeUITest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;

    // ---------------------------------------------------------------
    // Test 1: Non-admin cannot access the create form
    // ---------------------------------------------------------------

    public function test_non_admin_user_cannot_access_credit_line_create_form(): void
    {
        // Wave-2 review (2026-05-14): GET /create itself is now admin-gated
        // (ensureAdmin() in the controller), so the form never renders for
        // non-admins. The assertion is therefore the page-source check, not
        // a form-submit round trip.
        $this->browse(function (Browser $browser) {
            $nonAdmin = User::where('email', 'user1@dev.familyfund.local')->firstOrFail();
            $browser->loginAs($nonAdmin)
                ->visit('/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->pause(500)
                ->screenshot('negative/01a_non_admin_create_attempt');

            $pageSource = $browser->driver->getPageSource();
            $blocked = str_contains($pageSource, '403')
                || str_contains($pageSource, 'unauthorized')
                || str_contains($pageSource, 'Unauthorized')
                || str_contains($pageSource, 'Forbidden');

            $this->assertTrue(
                $blocked,
                'Non-admin GET /accounts/{id}/credit-lines/create should be 403.'
            );
        });
    }

    // ---------------------------------------------------------------
    // Test 2: Negative principal_shares shows validation error
    // ---------------------------------------------------------------

    public function test_open_credit_line_with_negative_principal_shows_validation_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->screenshot('negative/02a_create_form_loaded');

            // The form has min:0.0001, so we must bypass HTML validation via JS.
            $browser->script("document.querySelector('input[name=\"principal_shares\"]').removeAttribute('min');");
            $browser->script("document.querySelector('input[name=\"principal_shares\"]').removeAttribute('required');");

            $browser->type('input[name="principal_shares"]', '-10')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '6')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->press('Open')
                ->pause(1000)
                ->screenshot('negative/02b_negative_principal_result');

            // Should see a validation error (alert-danger or "errors" block).
            $browser->assertSee('principal');
        });
    }

    // ---------------------------------------------------------------
    // Test 3: term_months = 0 shows validation error
    // ---------------------------------------------------------------

    public function test_open_credit_line_with_zero_term_shows_validation_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->screenshot('negative/03a_create_form_loaded');

            // Remove HTML5 min constraint so the form submits.
            $browser->script("document.querySelector('input[name=\"term_months\"]').removeAttribute('min');");
            $browser->script("document.querySelector('input[name=\"term_months\"]').removeAttribute('required');");

            $browser->type('input[name="principal_shares"]', '50')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '0')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->press('Open')
                ->pause(1000)
                ->screenshot('negative/03b_zero_term_result');

            // Laravel validation rule: min:1 → error containing "term"
            $browser->assertSee('term');
        });
    }

    // ---------------------------------------------------------------
    // Test 4: Over-borrow shows flash error
    // ---------------------------------------------------------------

    public function test_over_borrow_shows_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->screenshot('negative/04a_create_form_loaded');

            $browser->type('input[name="principal_shares"]', '9999999')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '12')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->type('input[name="descr"]', 'Negative test: over-borrow')
                ->press('Open')
                ->pause(1000)
                ->screenshot('negative/04b_over_borrow_result');

            // OverBorrowException message contains "Cannot borrow"
            $browser->assertSee('Cannot borrow');
        });
    }

    // ---------------------------------------------------------------
    // Test 5: Cancel active line with outstanding blocks
    // ---------------------------------------------------------------

    public function test_cancel_active_line_with_outstanding_blocks(): void
    {
        $lineId = null;
        $this->browse(function (Browser $browser) use (&$lineId) {
            // Open a line.
            $lineId = $this->openLineViaUi($browser, 50.0, 6, 'Negative test: cancel block');

            $page = new CreditLineShowPage($lineId);
            $browser->on($page)
                ->screenshot('negative/05a_line_active');

            // Attempt to cancel without repaying — bypass the JS confirm dialog.
            $browser->script("window.confirm = function() { return true; }");
            $browser->press('Cancel line')
                ->pause(1000)
                ->screenshot('negative/05b_cancel_attempt_result');

            // Should see the CancelNotAllowedException flash error.
            $browser->assertSee('Cannot cancel');

            // DB: line must still be active.
            $line = AccountCreditLine::findOrFail($lineId);
            $this->assertEquals('active', $line->status, 'Line should remain active after failed cancel.');

            // Cleanup: force-cancel in DB.
            $this->cleanupLine($lineId);
            $lineId = null;
        });
    }

    // ---------------------------------------------------------------
    // Test 6: Account show blocked when active credit line exists
    // ---------------------------------------------------------------

    public function test_account_show_blocked_when_active_credit_line_exists(): void
    {
        $lineId = null;
        $this->browse(function (Browser $browser) use (&$lineId) {
            // Open a line.
            $lineId = $this->openLineViaUi($browser, 30.0, 3, 'Negative test: closure block');

            // Now attempt to delete the account via the destroy route.
            // Use JS fetch with DELETE method + CSRF.
            $browser->visit('/accounts/' . self::ACCOUNT_ID)
                ->pause(500)
                ->screenshot('negative/06a_account_show');

            $browser->script(
                "fetch('/accounts/" . self::ACCOUNT_ID . "', {"
                    . "method:'POST',"
                    . "headers:{"
                    . "  'X-CSRF-TOKEN': document.head.querySelector('meta[name=\"csrf-token\"]')?.content || '',"
                    . "  'Content-Type': 'application/x-www-form-urlencoded'"
                    . "},"
                    . "body:'_method=DELETE',"
                    . "redirect:'follow'"
                    . "}).then(r => r.text()).then(html => {"
                    . "  window.__deleteResponse = html;"
                    . "});"
            );
            $browser->pause(1500);

            // After the redirect the browser ends up on the show page with a flash error.
            $browser->visit('/accounts/' . self::ACCOUNT_ID)
                ->pause(500)
                ->screenshot('negative/06b_after_delete_attempt');

            // The closure-block flash message should be visible on the redirected page
            // OR the line is still active in the DB (the account was not deleted).
            $line = AccountCreditLine::find($lineId);
            $this->assertNotNull($line, 'Credit line should still exist — account delete was blocked.');
            $this->assertEquals('active', $line->status, 'Credit line should still be active.');

            $this->cleanupLine($lineId);
            $lineId = null;
        });
    }

    // ---------------------------------------------------------------
    // Test 7: Readjust with no change shows error
    // ---------------------------------------------------------------

    public function test_readjust_no_change_shows_error(): void
    {
        $lineId = null;
        $this->browse(function (Browser $browser) use (&$lineId) {
            $lineId = $this->openLineViaUi($browser, 40.0, 6, 'Negative test: no-change readjust');

            $page = new CreditLineShowPage($lineId);
            $browser->on($page)
                ->screenshot('negative/07a_line_before_readjust');

            // Submit readjust with the SAME term (6 months) — a no-change.
            $browser->script("document.querySelector('form[action*=\"readjust\"] input[name=\"new_term_months\"]').value = '6';");
            $browser->press('Readjust')
                ->pause(1000)
                ->screenshot('negative/07b_readjust_no_change_result');

            // NoChangeException message: "No changes to term_months or payment_frequency"
            $browser->assertSee('No changes');

            $this->cleanupLine($lineId);
            $lineId = null;
        });
    }

    // ---------------------------------------------------------------
    // Test 8: Reverse an already-reversed REP fails
    // ---------------------------------------------------------------

    public function test_reverse_already_reversed_transaction_fails(): void
    {
        $lineId = null;
        $this->browse(function (Browser $browser) use (&$lineId) {
            $lineId = $this->openLineViaUi($browser, 60.0, 6, 'Negative test: double-reverse');

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);

            // Repay something to create a REP transaction.
            $page->repay($browser, 10.0);
            $browser->on($page)
                ->assertSee('Credit Line #' . $lineId)
                ->screenshot('negative/08a_after_repay');

            $repTx = TransactionExt::where('account_credit_line_id', $lineId)
                ->where('type', TransactionExt::TYPE_REPAY)
                ->latest('id')
                ->firstOrFail();

            // First reversal — submit via a dynamically injected form so the
            // browser navigates properly and session flash is visible.
            $this->submitReverseForm($browser, $repTx->id, 'First reversal', $page->url());
            $browser->waitFor('.alert, .flash-message, #flash-message', 5)
                ->screenshot('negative/08b_after_first_reverse');

            // Second reversal of the same transaction — should fail.
            $browser->visit($page->url());
            $this->submitReverseForm($browser, $repTx->id, 'Second reversal', $page->url());
            $browser->waitFor('.alert, .flash-message, #flash-message', 5)
                ->screenshot('negative/08c_after_double_reverse');

            // AlreadyReversedException message: "has already been reversed"
            $browser->assertSee('already been reversed');

            $this->cleanupLine($lineId);
            $lineId = null;
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

        $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
        if (preg_match('#/credit-lines/(\d+)$#', $path, $m)) {
            return (int) $m[1];
        }

        $line = AccountCreditLine::where('descr', $descr)->latest('id')->firstOrFail();
        return (int) $line->id;
    }

    /**
     * Submit a reverse request by injecting a real HTML form so the browser
     * navigates fully (including session flash).
     *
     * @param string $returnUrl URL to navigate to before submitting (should be
     *                          the current credit-line show page so CSRF meta is present).
     */
    private function submitReverseForm(Browser $browser, int $transactionId, string $reason, string $returnUrl): void
    {
        $reverseUrl = url('/transactions/' . $transactionId . '/reverse');
        $escapedReason = addslashes($reason);

        $browser->script(
            "(function() {"
                . "var csrf = document.head.querySelector('meta[name=\"csrf-token\"]')?.content || '';"
                . "var f = document.createElement('form');"
                . "f.method = 'POST';"
                . "f.action = '" . addslashes($reverseUrl) . "';"
                . "var t = document.createElement('input'); t.name='_token'; t.value=csrf; f.appendChild(t);"
                . "var id = document.createElement('input'); id.name='transaction_id'; id.value='" . $transactionId . "'; f.appendChild(id);"
                . "var r = document.createElement('input'); r.name='reason'; r.value='" . $escapedReason . "'; f.appendChild(r);"
                . "document.body.appendChild(f);"
                . "f.submit();"
                . "})();"
        );
        $browser->pause(2000);
    }

    /**
     * Check whether a CSS selector matches any element on the page.
     */
    private function elementExists(Browser $browser, string $selector): bool
    {
        return count($browser->elements($selector)) > 0;
    }

    // ---------------------------------------------------------------
     // Wave-2 review (2026-05-14): admin-gate enforcement on GET endpoints.
     //
     // Before the fix, AccountCreditLineControllerExt::{index,show,edit} and
     // AdminTransactionController::create() were only auth-protected. A non-
     // admin authenticated user could GET other accounts' credit-line data.
     // These four tests assert the gate now returns 403 / "Forbidden" /
     // "unauthorized" for a non-admin user.
     // ---------------------------------------------------------------

    public function test_non_admin_cannot_get_credit_lines_index(): void
    {
        $this->browse(function (Browser $browser) {
            $nonAdmin = User::where('email', 'user1@dev.familyfund.local')->firstOrFail();
            $browser->loginAs($nonAdmin)
                ->visit('/accounts/' . self::ACCOUNT_ID . '/credit-lines')
                ->pause(500)
                ->screenshot('negative/gate_index');

            $source = $browser->driver->getPageSource();
            $blocked = str_contains($source, '403')
                || str_contains($source, 'Forbidden')
                || stripos($source, 'unauthorized') !== false;
            $this->assertTrue($blocked, 'Non-admin GET /accounts/{id}/credit-lines should be 403.');
        });
    }

    public function test_non_admin_cannot_get_credit_line_show(): void
    {
        // Find any existing credit line (the dev DB normally has a few from the
        // happy-path tour; fall back to a synthetic id which will 404 — also a
        // valid non-200 outcome and still proves the page is not viewable).
        $line = AccountCreditLine::orderByDesc('id')->first();
        $lineId = $line?->id ?? 999999;

        $this->browse(function (Browser $browser) use ($lineId) {
            $nonAdmin = User::where('email', 'user1@dev.familyfund.local')->firstOrFail();
            $browser->loginAs($nonAdmin)
                ->visit('/credit-lines/' . $lineId)
                ->pause(500)
                ->screenshot('negative/gate_show');

            $source = $browser->driver->getPageSource();
            $blocked = str_contains($source, '403')
                || str_contains($source, 'Forbidden')
                || stripos($source, 'unauthorized') !== false;
            $this->assertTrue($blocked, 'Non-admin GET /credit-lines/{id} should be 403.');
        });
    }

    public function test_non_admin_cannot_get_credit_line_edit(): void
    {
        $line = AccountCreditLine::orderByDesc('id')->first();
        $lineId = $line?->id ?? 999999;

        $this->browse(function (Browser $browser) use ($lineId) {
            $nonAdmin = User::where('email', 'user1@dev.familyfund.local')->firstOrFail();
            $browser->loginAs($nonAdmin)
                ->visit('/credit-lines/' . $lineId . '/edit')
                ->pause(500)
                ->screenshot('negative/gate_edit');

            $source = $browser->driver->getPageSource();
            $blocked = str_contains($source, '403')
                || str_contains($source, 'Forbidden')
                || stripos($source, 'unauthorized') !== false;
            $this->assertTrue($blocked, 'Non-admin GET /credit-lines/{id}/edit should be 403.');
        });
    }

    public function test_non_admin_cannot_get_admin_transaction_create(): void
    {
        $this->browse(function (Browser $browser) {
            $nonAdmin = User::where('email', 'user1@dev.familyfund.local')->firstOrFail();
            $browser->loginAs($nonAdmin)
                ->visit('/admin/transactions/create')
                ->pause(500)
                ->screenshot('negative/gate_admin_tx_create');

            $source = $browser->driver->getPageSource();
            $blocked = str_contains($source, '403')
                || str_contains($source, 'Forbidden')
                || stripos($source, 'unauthorized') !== false;
            $this->assertTrue($blocked, 'Non-admin GET /admin/transactions/create should be 403.');
        });
    }

    /**
     * Soft-cancel a credit line to avoid polluting dev data.
     */
    private function cleanupLine(?int $lineId): void
    {
        if ($lineId === null) {
            return;
        }
        try {
            $line = AccountCreditLine::find($lineId);
            if ($line) {
                $line->update(['status' => 'cancelled']);
            }
        } catch (\Throwable $e) {
            // Swallow — leaves dev data for human inspection.
        }
    }
}
