<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Ticket #28 — browser coverage for the standardized fund detail view
 * (funds/show_ext.blade.php and its section partials), in BOTH light and dark
 * mode. This is the flagship of the #28 detail/show-page standardization and
 * mirrors AccountDetailUITest (#27) / TradePortfolioDetailUITest (#26).
 *
 * What this guards (the standardization that #28 applied):
 *   - every teal section header uses the themed `card-header-dark` class instead
 *     of an inline `style="background:#134e4a"` that navigation.css's
 *     `.card-header` `!important` light gradient was silently overriding
 *   - admin-only section headers use the new `card-header-admin` amber class
 *     (also previously overridden by the base `.card-header` rule, losing the
 *     admin colour cue) — and it survives dark mode
 *   - the section collapse toggles WORK — they were dead BS4 `data-toggle`
 *     attributes and are now BS5 `data-bs-toggle`, so clicking one toggles the
 *     section (the BS4 form did nothing under Bootstrap 5). This also guards the
 *     removal of the over-broad `.collapse.show { display: inline }` rule.
 *   - the page renders cleanly in dark mode (the `.dark` html class is applied
 *     and the converted Tailwind `dark:` utilities are compiled)
 *
 * Screenshots (light + dark) land in tests/Browser/screenshots/fund28/ for a
 * visual dark-mode review.
 *
 * Uses fund 1 ("Family Fund A") from the synthetic test baseline
 * (TestBaselineSeeder), logged in as the system-admin via the dev-login
 * `?as=admin` route so the admin sections (which exercise card-header-admin)
 * render. The admin user (config('familyfund.admin_emails')[0] =
 * admin@dev.familyfund.local) is seeded by QaTestUsersSeeder.
 */
class FundDetailUITest extends DuskTestCase
{
    private const FUND_ID = 1;

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

    public function test_fund_show_light_and_dark(): void
    {
        $this->browse(function (Browser $browser) {
            // Log in as admin and land on the fund detail page (show_ext).
            // "Transaction History" is an admin-only section, so waiting on it
            // confirms the admin view (and its card-header-admin) rendered.
            $browser->visit('/dev-login/funds/' . self::FUND_ID . '?as=admin')
                ->waitForText('Transaction History', 15);
            $this->ensureLightMode($browser);
            // This page is heavy (many charts); wait for the reload to repaint
            // before asserting. Anchor on "Transaction History" (normal case) —
            // Selenium getText() returns text-transform:uppercase'd labels (e.g.
            // the "Total Value" stat label renders as "TOTAL VALUE"), so they
            // can't be matched by their source casing.
            $browser->waitForText('Transaction History', 15)
                ->screenshot('fund28/01_show_light');

            // Teal section headers use the standardized themed dark class (not
            // inline hex that navigation.css overrides). There are many.
            $this->assertGreaterThanOrEqual(
                5,
                count($browser->elements('.card-header-dark')),
                'fund detail sections should use the standardized card-header-dark class'
            );

            // Admin-only section headers use the new amber card-header-admin class.
            $this->assertGreaterThanOrEqual(
                1,
                count($browser->elements('.card-header-admin')),
                'admin fund sections should use the standardized card-header-admin class'
            );

            // Functional check of the BS4->BS5 collapse fix: the Assets section
            // starts expanded; clicking its toggle collapses it. A dead BS4
            // data-toggle (or the old over-broad .collapse.show rule) would
            // leave it expanded.
            $browser->assertVisible('#collapseAssets.show')
                ->click('a[data-bs-toggle="collapse"][href="#collapseAssets"]')
                ->waitUntilMissing('#collapseAssets.show', 5);

            // Now dark mode.
            $this->enableDarkMode($browser);
            $browser->waitForText('Transaction History', 10)
                ->screenshot('fund28/02_show_dark');
        });
    }
}
