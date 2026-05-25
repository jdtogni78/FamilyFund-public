<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Ticket #26 — browser coverage for the standardized trade_portfolios detail
 * views, in BOTH light and dark mode.
 *
 * What this guards (the standardization that #26 applied):
 *   - the detail header uses the themed `card-header-dark` and renders in dark
 *     mode (the page actually picks up the `.dark` html class)
 *   - the CASH highlight row uses the dark-adapting `.table-info` class
 *     (it used to be a hard-coded light `#f0fdfa` that vanished in dark mode)
 *   - the allocation charts' collapse toggle WORKS — it was a dead BS4
 *     `data-toggle` and is now BS5 `data-bs-toggle`, so clicking it expands
 *   - the shared funds detail page (show_ext: inner_show_compact + stacked
 *     bar charts) still renders cleanly in both modes
 *
 * Screenshots (light + dark) land in tests/Browser/screenshots/tp26/ for a
 * visual dark-mode review.
 *
 * Uses TP #10 / Fund #2 from the committed test baseline (active, has items
 * across groups + cash), logged in as admin via the dev-login route.
 */
class TradePortfolioDetailUITest extends DuskTestCase
{
    private const TP_ID = 10;
    private const FUND_ID = 2;

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

    public function test_trade_portfolio_show_light_and_dark(): void
    {
        $this->browse(function (Browser $browser) {
            // Log in as admin and land on the trade-portfolio detail page.
            $browser->visit('/dev-login/tradePortfolios/' . self::TP_ID . '?as=admin')
                ->waitForText('Trade Portfolio ' . self::TP_ID, 15);
            $this->ensureLightMode($browser);
            $browser->assertSee('CASH')      // cash highlight row (now .table-info)
                ->assertSee('Reserve')   // summary stat label
                ->screenshot('tp26/01_show_light');

            // The themed dark header class is present in the DOM.
            $this->assertStringContainsString(
                'card-header-dark',
                $browser->driver->getPageSource(),
                'detail header should use the standardized card-header-dark class'
            );

            // The CASH row uses the dark-adapting contextual class, not an
            // inline light background.
            $this->assertGreaterThanOrEqual(
                1,
                count($browser->elements('tr.table-info')),
                'cash row should use .table-info (dark-mode adaptive)'
            );

            // Functional check of the BS4->BS5 collapse fix: the allocation
            // chart starts collapsed; clicking the toggle expands it.
            $browser->assertMissing('#collapseTPTA' . self::TP_ID . '.show')
                ->click('a[data-bs-toggle="collapse"][href="#collapseTPTA' . self::TP_ID . '"]')
                ->waitFor('#collapseTPTA' . self::TP_ID . '.show', 5)
                ->assertVisible('#collapseTPTA' . self::TP_ID . ' canvas');

            // Now dark mode.
            $this->enableDarkMode($browser);
            $browser->waitForText('Trade Portfolio ' . self::TP_ID, 10)
                ->assertSee('CASH')
                ->screenshot('tp26/02_show_dark');
        });
    }

    public function test_show_diff_and_rebalance_light_and_dark(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/tradePortfolios/' . self::TP_ID . '/show_diff?as=admin')
                ->waitForText('Trade Portfolio Diff', 15);
            $this->ensureLightMode($browser);
            $browser->screenshot('tp26/03_show_diff_light');
            $this->enableDarkMode($browser);
            $browser->waitForText('Trade Portfolio Diff', 10)
                ->screenshot('tp26/04_show_diff_dark');

            $browser->visit('/dev-login/tradePortfolios/' . self::TP_ID . '/rebalance?as=admin')
                ->waitForText('Rebalance', 15);
            $this->ensureLightMode($browser);
            $browser->screenshot('tp26/05_rebalance_light');
            $this->enableDarkMode($browser);
            $browser->waitForText('Rebalance', 10)
                ->screenshot('tp26/06_rebalance_dark');
        });
    }

    public function test_fund_show_ext_shared_partials_light_and_dark(): void
    {
        $this->browse(function (Browser $browser) {
            // show_ext renders the shared trade_portfolios partials
            // (inner_show_compact + the two stacked-bar charts).
            $browser->visit('/dev-login/funds/' . self::FUND_ID . '?as=admin')
                ->waitForText('Portfolio Allocations by', 15);
            $this->ensureLightMode($browser);
            $browser->screenshot('tp26/07_fund_show_ext_light');

            // Stacked-bar collapse toggle uses the fixed BS5 attribute.
            $this->assertGreaterThanOrEqual(
                1,
                count($browser->elements('a[data-bs-toggle="collapse"][href="#collapsePortfolioAllocations"]')),
                'stacked-bar toggle should use data-bs-toggle'
            );

            $this->enableDarkMode($browser);
            $browser->waitForText('Portfolio Allocations by', 10)
                ->screenshot('tp26/08_fund_show_ext_dark');
        });
    }
}
