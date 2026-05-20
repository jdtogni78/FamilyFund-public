<?php

namespace Tests\Browser;

use App\Models\AccountCreditLine;
use App\Models\TransactionExt;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\CreditLineShowPage;
use Tests\DuskTestCase;

/**
 * UI tour — walks the full credit-line / borrowing flow taking
 * screenshots at every meaningful state so a human can eyeball the
 * rendered UI and reports.
 *
 * Screenshots land in tests/Browser/screenshots/tour-*.png.
 *
 * Not a strict pass/fail test — its assertions are minimal. The
 * point is to give a reviewer a visual story they can scroll through.
 */
class CreditLineUITourTest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;
    private const FUND_ID = 1;

    public function test_ui_tour_of_credit_line_flow(): void
    {
        $this->browse(function (Browser $browser) {
            // ── 01. Account page before any credit line ──────────────────
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID)
                ->pause(500)
                ->screenshot('tour/01_account_page_before')
                ->resize(1920, 1080)
                ->screenshot('tour/01b_account_page_before_wide');

            // ── 02. Account credit-lines index (empty) ───────────────────
            $browser->visit('/accounts/' . self::ACCOUNT_ID . '/credit-lines')
                ->pause(500)
                ->screenshot('tour/02_credit_lines_index_empty');

            // ── 03. Create form ──────────────────────────────────────────
            $browser->visit('/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->screenshot('tour/03_create_form_empty');

            // Fill the form (don't submit yet — screenshot the filled state)
            $browser->type('input[name="principal_shares"]', '120')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '6')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->type('input[name="descr"]', 'UI tour — UC-01 happy path')
                ->screenshot('tour/04_create_form_filled')
                ->press('Open');

            // Wait for redirect to /credit-lines/{id}
            $browser->waitUsing(10, 200, function () use ($browser) {
                $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
                return (bool) preg_match('#/credit-lines/\d+$#', $path);
            });

            $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
            preg_match('#/credit-lines/(\d+)$#', $path, $m);
            $lineId = (int) $m[1];

            // ── 05. Show page right after open ──────────────────────────
            $browser->waitForText('Credit Line #' . $lineId, 5)
                ->screenshot('tour/05_show_page_just_opened');

            // Scroll down to capture below-the-fold sections
            $browser->script("window.scrollTo(0, 600);");
            $browser->pause(200);
            $browser->screenshot('tour/06_show_page_schedule_visible');

            $browser->script("window.scrollTo(0, 1200);");
            $browser->pause(200);
            $browser->screenshot('tour/07_show_page_adjustment_history');

            // ── 08. Account page now (loans summary card has a line) ────
            $browser->visit('/accounts/' . self::ACCOUNT_ID)
                ->pause(500)
                ->screenshot('tour/08_account_page_after_open')
                // SHARES + Market Value tiles must subtract the 120 shares
                // just borrowed and show a "borrowed" sub-line.
                ->assertSee('borrowed')
                ->assertSee('120.00 borrowed');

            // ── 09. First repay → show page ─────────────────────────────
            $page = new CreditLineShowPage($lineId);
            $browser->visit($page->url())
                ->waitForText('Credit Line #' . $lineId, 5);

            $browser->type('form[action$="/repay"] input[name="shares"]', '20')
                ->press('Record repayment')
                ->waitForText('Credit Line #' . $lineId, 5)
                ->screenshot('tour/09_show_after_first_repay');

            // ── 10. Second repay (different size, partial) ──────────────
            $browser->type('form[action$="/repay"] input[name="shares"]', '15')
                ->press('Record repayment')
                ->waitForText('Credit Line #' . $lineId, 5)
                ->screenshot('tour/10_show_after_second_repay');

            // Scroll for schedule
            $browser->script("window.scrollTo(0, 800);");
            $browser->pause(200);
            $browser->screenshot('tour/11_schedule_after_two_repays');

            // ── 12. Readjust → new schedule + adjustment timeline entry ─
            $browser->script("window.scrollTo(0, 0);");
            $browser->pause(150);
            $browser->type('form[action$="/readjust"] input[name="new_term_months"]', '12')
                ->press('Readjust')
                ->waitForText('Credit Line #' . $lineId, 5)
                ->screenshot('tour/12_show_after_readjust');

            $browser->script("window.scrollTo(0, 800);");
            $browser->pause(200);
            $browser->screenshot('tour/13_schedule_after_readjust');

            $browser->script("window.scrollTo(0, 1500);");
            $browser->pause(200);
            $browser->screenshot('tour/14_adjustment_timeline_with_entry');

            // ── 15. Open schedule snapshot modal (if present) ───────────
            $hasSnapshotBtn = $browser->driver->executeScript(
                "return document.querySelectorAll('[data-bs-target*=\"snapshot\"]').length > 0;"
            );
            if ($hasSnapshotBtn) {
                $browser->click('[data-bs-target*="snapshot"]:not([disabled])')
                    ->pause(500)
                    ->screenshot('tour/15_schedule_snapshot_modal');
                $browser->script("document.querySelectorAll('.modal.show .btn-close').forEach(b => b.click());");
                $browser->pause(400);
            } else {
                $browser->screenshot('tour/15_no_snapshot_modal_found');
            }

            // ── 16. Open trajectory-at-point modal (if present) ─────────
            $hasTrajBtn = $browser->driver->executeScript(
                "return document.querySelectorAll('[data-bs-target*=\"trajectory\"]').length > 0;"
            );
            if ($hasTrajBtn) {
                $browser->click('[data-bs-target*="trajectory"]:not([disabled])')
                    ->pause(500)
                    ->screenshot('tour/16_trajectory_at_point_modal');
                $browser->script("document.querySelectorAll('.modal.show .btn-close').forEach(b => b.click());");
                $browser->pause(400);
            } else {
                $browser->screenshot('tour/16_no_trajectory_modal_found');
            }

            // ── 17. Find a REP transaction and reverse it ───────────────
            $rep = TransactionExt::where('account_credit_line_id', $lineId)
                ->where('type', TransactionExt::TYPE_REPAY)
                ->latest('id')
                ->first();
            if ($rep) {
                $browser->driver->executeScript(
                    "fetch('" . url('/transactions/' . $rep->id . '/reverse') . "', {"
                    . "method:'POST',"
                    . "headers:{'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content || '',"
                    . "'Content-Type': 'application/x-www-form-urlencoded'},"
                    . "body: new URLSearchParams({transaction_id: '" . $rep->id . "', reason: 'UI tour reverse'})"
                    . "});"
                );
                $browser->pause(800);
                $browser->visit($page->url())
                    ->waitForText('Credit Line #' . $lineId, 5)
                    ->screenshot('tour/17_show_after_reverse');
            }

            // ── 18. Trajectory chart close-up ───────────────────────────
            $browser->script("var img = document.querySelector('img[alt*=\"trajectory\" i], img[src*=\"quickchart\" i]'); if (img) img.scrollIntoView({block: 'center'});");
            $browser->pause(300);
            $browser->screenshot('tour/18_trajectory_chart_closeup');

            // ── 19. Fund show page ──────────────────────────────────────
            $browser->visit('/funds/' . self::FUND_ID)
                ->pause(500)
                ->screenshot('tour/19_fund_show_top');
            $browser->script("window.scrollTo(0, document.body.scrollHeight);");
            $browser->pause(300);
            $browser->screenshot('tour/20_fund_show_bottom_admin_panel');

            // ── 21. Account quarterly PDF (today) ───────────────────────
            $today = now()->format('Y-m-d');
            $browser->visit('/accounts/' . self::ACCOUNT_ID . '/pdf_as_of/' . $today)
                ->pause(3000)  // give wkhtmltopdf time + Chrome's PDF viewer
                ->screenshot('tour/21_account_quarterly_pdf');

            // ── 22. Fund quarterly PDF (today) ──────────────────────────
            $browser->visit('/funds/' . self::FUND_ID . '/pdf_as_of/' . $today)
                ->pause(3000)
                ->screenshot('tour/22_fund_quarterly_pdf');

            // ── Cleanup: cancel the tour line so it doesn't pollute ─────
            $line = AccountCreditLine::find($lineId);
            if ($line && $line->status === 'active') {
                $line->update(['status' => 'cancelled']);
            }

            // Minimal assertion so phpunit marks the test as a pass.
            $this->assertTrue(true, 'Tour completed; review screenshots in tests/Browser/screenshots/tour-*.png');
        });
    }
}
