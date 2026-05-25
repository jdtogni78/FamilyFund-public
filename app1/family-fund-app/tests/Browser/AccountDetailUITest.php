<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Ticket #27 — browser coverage for the standardized accounts detail view
 * (accounts/show_ext.blade.php and its partials), in BOTH light and dark mode.
 *
 * What this guards (the standardization that #27 applied):
 *   - every section header uses the themed `card-header-dark` class instead of
 *     an inline `style="background:#134e4a"` that navigation.css's `.card-header`
 *     `!important` light gradient was silently overriding (which left the white
 *     `btn-outline-light` collapse buttons nearly invisible in light mode)
 *   - the section collapse toggles WORK — they were dead BS4 `data-toggle`
 *     attributes and are now BS5 `data-bs-toggle`, so clicking one toggles the
 *     section (the BS4 form did nothing under Bootstrap 5)
 *   - the page renders cleanly in dark mode (the `.dark` html class is applied
 *     and the converted Tailwind `dark:` border utilities are compiled)
 *
 * Screenshots (light + dark) land in tests/Browser/screenshots/acct27/ for a
 * visual dark-mode review.
 *
 * Uses account 7 (fund 1) from the synthetic test baseline (TestBaselineSeeder),
 * logged in as the fund-admin QA user via the dev-login route. (The `as=admin`
 * alias resolves to ADMIN_EMAILS[0] = admin@dev.familyfund.local, which the
 * synthetic baseline does not seed — fund-admin administers fund 1, where
 * account 7 lives.)
 */
class AccountDetailUITest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;

    /** Turn on dark mode the same way the app does (localStorage + reload). */
    private function enableDarkMode(Browser $browser): void
    {
        $browser->script("localStorage.setItem('darkMode','true');");
        $browser->refresh();
        $browser->pause(600);
        $isDark = $browser->script("return document.documentElement.classList.contains('dark');")[0];
        $this->assertTrue($isDark, 'expected <html> to carry the .dark class after enabling dark mode');
    }

    /**
     * Force a clean LIGHT state. Dusk reuses the browser profile across tests,
     * so localStorage from an earlier test would otherwise leak dark mode in.
     */
    private function ensureLightMode(Browser $browser): void
    {
        $browser->script("localStorage.setItem('darkMode','false');");
        $browser->refresh();
        $browser->pause(600);
        $isDark = $browser->script("return document.documentElement.classList.contains('dark');")[0];
        $this->assertFalse($isDark, 'expected <html> NOT to carry the .dark class in light mode');
    }

    public function test_account_show_light_and_dark(): void
    {
        $this->browse(function (Browser $browser) {
            // Log in as admin and land on the account detail page (show_ext).
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '?as=fund-admin')
                ->waitForText('Transaction History', 15);
            $this->ensureLightMode($browser);
            $browser->assertSee('Shares Holdings Over Time')
                ->screenshot('acct27/01_show_light');

            // Section headers use the standardized themed dark class (not inline
            // hex that navigation.css overrides). There are many sections.
            $this->assertGreaterThanOrEqual(
                5,
                count($browser->elements('.card-header-dark')),
                'account detail sections should use the standardized card-header-dark class'
            );

            // Functional check of the BS4->BS5 collapse fix: the Transaction
            // History section starts expanded; clicking its toggle collapses it.
            // A dead BS4 data-toggle would leave it expanded.
            $browser->assertVisible('#collapseTransactions.show')
                ->click('a[data-bs-toggle="collapse"][href="#collapseTransactions"]')
                ->waitUntilMissing('#collapseTransactions.show', 5);

            // Now dark mode.
            $this->enableDarkMode($browser);
            $browser->waitForText('Transaction History', 10)
                ->assertSee('Shares Holdings Over Time')
                ->screenshot('acct27/02_show_dark');
        });
    }
}
