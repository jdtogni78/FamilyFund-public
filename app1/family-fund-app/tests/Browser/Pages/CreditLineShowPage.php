<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

/**
 * Dusk page object for the credit-line "show" view.
 *
 * Mirrors resources/views/account_credit_lines/show.blade.php.
 */
class CreditLineShowPage extends Page
{
    public function __construct(public int $lineId) {}

    public function url(): string
    {
        return '/credit-lines/' . $this->lineId;
    }

    public function assert(Browser $browser): void
    {
        // Tolerate the post-submit redirect not having landed yet: wait up
        // to 5s for the URL to match, then assert the heading is on the page.
        $browser->waitUsing(5, 100, function () use ($browser) {
            $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
            return $path === $this->url();
        });
        $browser->waitForText('Loan Share #' . $this->lineId, 5);
    }

    /**
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@summaryHeader'     => '.card-header strong',
            '@scheduleTable'     => 'table.table',
            '@adjustmentTimeline'=> '.timeline',
            '@trajectoryChart'   => 'img[alt="Payoff trajectory chart"]',
            '@repayForm'         => 'form[action$="/repay"]',
            '@repaySharesInput'  => 'form[action$="/repay"] input[name="shares"]',
            '@repaySubmit'       => 'form[action$="/repay"] button[type="submit"]',
            '@readjustForm'      => 'form[action$="/readjust"]',
            '@readjustTermInput' => 'form[action$="/readjust"] input[name="new_term_months"]',
            '@readjustSubmit'    => 'form[action$="/readjust"] button[type="submit"]',
        ];
    }

    /**
     * Submit a repayment for the given number of shares.
     */
    public function repay(Browser $browser, float $shares): void
    {
        $browser->type('form[action$="/repay"] input[name="shares"]', (string) $shares)
            ->press('Record repayment');
    }

    /**
     * Submit a readjustment changing the term to $newTermMonths.
     */
    public function readjust(Browser $browser, int $newTermMonths, ?string $reason = null): void
    {
        $browser->type('form[action$="/readjust"] input[name="new_term_months"]', (string) $newTermMonths);
        if ($reason !== null) {
            $browser->type('form[action$="/readjust"] input[name="reason"]', $reason);
        }
        $browser->press('Readjust');
    }
}
