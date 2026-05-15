<?php

namespace Tests\Browser;

use App\Models\AccountCreditLine;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Phase 9: browser test for the credit-line payment simulator page.
 *
 * Verifies the simulator page renders, the form submits, the 3-scenario
 * summary table appears, and the QuickChart image embeds. Screenshots
 * are saved for visual review.
 *
 * Strategy:
 *   - open a fresh line via the existing /credit-lines/create flow
 *   - visit /credit-lines/{id}/simulator
 *   - submit $50/month
 *   - assert chart + 3-row summary present
 *   - cancel the line in tearDown (best-effort)
 */
class CreditLineSimulatorUITest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;

    public function test_simulator_renders_form_and_chart(): void
    {
        $this->browse(function (Browser $browser) {
            // Open a fresh line so the test isn't sensitive to which lines exist.
            $descr = 'E2E simulator UC-Phase9 ' . uniqid();
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->type('input[name="principal_shares"]', '100')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '12')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->type('input[name="descr"]', $descr)
                ->press('Open')
                ->waitUsing(10, 200, function () use ($browser) {
                    $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
                    return (bool) preg_match('#/credit-lines/\d+$#', $path);
                });

            $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
            preg_match('#/credit-lines/(\d+)$#', $path, $m);
            $lineId = (int) $m[1];

            // Visit simulator (empty form).
            $browser->visit('/credit-lines/' . $lineId . '/simulator')
                ->waitForText('Payment simulator')
                ->assertSee('Monthly payment (USD)')
                ->screenshot('simulator/01_empty_form');

            // Fill in a hypothetical payment and submit.
            $browser->type('input[name="monthly_payment_usd"]', '50')
                ->press('Simulate')
                ->waitForText('Scenario summary', 10);

            // Assert the 3-scenario summary table is present.
            $browser->assertSee('Scenario summary')
                ->assertSee('Conservative')
                ->assertSee('Expected')
                ->assertSee('Aggressive')
                ->assertSee('Cumulative-shares-paid projection');

            // The summary table should have exactly 3 data rows (one per scenario).
            $rows = $browser->elements('table tbody tr');
            $this->assertGreaterThanOrEqual(
                3,
                count($rows),
                'Expected at least 3 summary table rows (conservative/expected/aggressive).'
            );

            // The chart <img> should be present.
            $imgs = $browser->elements('img[alt*="simulation"]');
            $this->assertGreaterThanOrEqual(
                1,
                count($imgs),
                'Expected the QuickChart simulator image to be rendered.'
            );

            $browser->script("window.scrollTo(0, 400);");
            $browser->pause(200);
            $browser->screenshot('simulator/02_with_results');

            $this->cleanupLine($lineId);
        });
    }

    public function test_simulator_time_mode_renders(): void
    {
        $this->browse(function (Browser $browser) {
            // Open a fresh line.
            $descr = 'E2E simulator time mode ' . uniqid();
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->type('input[name="principal_shares"]', '100')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '12')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->type('input[name="descr"]', $descr)
                ->press('Open')
                ->waitUsing(10, 200, function () use ($browser) {
                    $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
                    return (bool) preg_match('#/credit-lines/\d+$#', $path);
                });

            $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
            preg_match('#/credit-lines/(\d+)$#', $path, $m);
            $lineId = (int) $m[1];

            // Use radio() to reliably check the radio + visit URL directly as fallback.
            $browser->visit('/credit-lines/' . $lineId . '/simulator?mode=time')
                ->waitForText('Payment simulator')
                ->radio('mode', 'time')
                ->pause(200)
                ->clear('target_months')
                ->type('target_months', '12')
                ->press('Simulate')
                ->waitForText('Scenario summary', 10);

            // The <th> text is uppercased via CSS, so check via DOM source rather than innerText.
            $html = $browser->driver->getPageSource();
            $this->assertStringContainsString(
                'Required monthly payment',
                $html,
                'Expected "Required monthly payment" column header in time-mode summary.'
            );
            $browser->assertSee('Conservative')
                ->assertSee('Expected')
                ->assertSee('Aggressive');

            $browser->script("window.scrollTo(0, 400);");
            $browser->pause(200);
            $browser->screenshot('simulator/03_time_mode_with_results');

            $this->cleanupLine($lineId);
        });
    }

    private function cleanupLine(int $lineId): void
    {
        try {
            $line = AccountCreditLine::find($lineId);
            if ($line && $line->status === 'active') {
                $line->update(['status' => 'cancelled']);
            }
        } catch (\Throwable $e) {
            // Best-effort.
        }
    }
}
