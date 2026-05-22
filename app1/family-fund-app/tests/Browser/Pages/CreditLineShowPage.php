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
            '@summaryHeader'      => '.card-header strong',
            '@scheduleTable'      => 'table.table',
            '@scheduleRows'       => 'table tbody tr[data-due]',
            '@adjustmentTimeline' => '.timeline',
            '@trajectoryChart'    => 'img[alt="Payoff trajectory chart"]',
            '@makePaymentButton'  => 'button[data-bs-target="#makePaymentModal"]',
            '@repayModal'         => '#makePaymentModal',
            '@repaySharesInput'   => '#makePaymentShares',
            '@repaySubmit'        => '#makePaymentModal button[type="submit"]',
        ];
    }

    /**
     * Submit a repayment via the "Make payment" modal on the show page.
     *
     * The credit-line/show page exposes repay through a Bootstrap modal
     * triggered by the green "Make payment" button. Dusk needs to (1)
     * open the modal, (2) wait for the modal to be visible, (3) fill the
     * input, (4) click the submit button inside the modal.
     */
    public function repay(Browser $browser, float $shares): void
    {
        $browser->click('button[data-bs-target="#makePaymentModal"]')
            ->waitFor('#makePaymentModal.show', 5)
            ->waitFor('#makePaymentShares', 2)
            ->type('#makePaymentShares', (string) $shares)
            // The modal's submit is "Record payment" (singular -p) — older
            // tests expected "Record repayment".
            ->click('#makePaymentModal button[type="submit"]')
            // After submit the page redirects back; wait for either the
            // modal to disappear or the heading to re-appear.
            ->waitUntilMissing('#makePaymentModal.show', 5);
    }

    /**
     * Submit a readjustment changing the term to $newTermMonths.
     *
     * The readjust form lives on the separate `/credit-lines/{id}/actions`
     * page (actions.blade.php), not the show page. Navigate there first.
     */
    public function readjust(Browser $browser, int $newTermMonths, ?string $reason = null): void
    {
        $browser->visit('/credit-lines/' . $this->lineId . '/actions')
            ->waitFor('form[action$="/readjust"]', 5)
            ->type('form[action$="/readjust"] input[name="new_term_months"]', (string) $newTermMonths);
        if ($reason !== null) {
            $browser->type('form[action$="/readjust"] input[name="reason"]', $reason);
        }
        $browser->press('Readjust');
    }
}
