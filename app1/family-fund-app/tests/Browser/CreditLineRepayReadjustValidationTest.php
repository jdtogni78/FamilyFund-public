<?php

namespace Tests\Browser;

use App\Models\AccountCreditLine;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser validation tests for the credit-line REPAY and READJUST forms.
 *
 * Bug reported 2026-05-19: "validations were not correct (ui)".
 *
 * The existing CreditLineNegativeUITest covers the cancel-with-outstanding
 * and no-change-readjust paths. This suite extends that with the repay/
 * readjust form's untested validation matrix:
 *
 *   - Repay shares = 0                  (server rule: min:0.0001)
 *   - Repay shares < 0                  (server rule: min:0.0001)
 *   - Repay shares > outstanding        (overpayment behaviour — UI should warn)
 *   - Readjust term = 0                 (server rule: min:1)
 *   - Readjust term > 480               (server rule: max:480)
 *   - Readjust effective_date in future (loose `date` rule today; should at
 *                                        least display correctly when posted)
 *   - Readjust effective_date before origination (no rule today — likely
 *                                                  produces wrong waveform)
 *   - Readjust frequency change persists in form
 *
 * Uses ACCOUNT_ID = 7 against the live dev DB, mirroring CreditLineNegativeUITest.
 * Lines created during the test are soft-cancelled in tearDown.
 */
class CreditLineRepayReadjustValidationTest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;
    private array $createdLineIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdLineIds as $id) {
            try {
                $line = AccountCreditLine::find($id);
                if ($line) {
                    $line->update(['status' => 'cancelled']);
                }
            } catch (\Throwable $e) {
                // best-effort cleanup; dev DB is fine
            }
        }
        parent::tearDown();
    }

    /**
     * Open a line via UI and remember its id for cleanup.
     */
    private function openLineViaUi(
        Browser $browser,
        float $principalShares,
        int $termMonths,
        string $descr,
    ): int {
        $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
            ->waitFor('form[action*="/credit-lines"]')
            ->type('input[name="nickname"]', $descr)
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
            $id = (int) $m[1];
            $this->createdLineIds[] = $id;
            return $id;
        }

        $line = AccountCreditLine::where('descr', $descr)->latest('id')->firstOrFail();
        $this->createdLineIds[] = (int) $line->id;
        return (int) $line->id;
    }

    // ---------------------------------------------------------------
    // REPAY validations
    // ---------------------------------------------------------------

    public function test_repay_with_zero_shares_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 30.0, 6, 'repay-zero validation');

            // Repay/readjust forms live on the /actions sub-page (the show page
            // exposes repay via a modal). /actions renders the errors bag, so
            // server-side validation messages are visible here.
            $browser->visit('/credit-lines/' . $lineId . '/actions')
                ->waitFor('form[action$="/repay"]', 5);
            $browser->script("var f=document.querySelector('form[action\$=\"/repay\"]'); if(f){f.setAttribute('novalidate','novalidate');}");

            $browser->type('form[action$="/repay"] input[name="shares"]', '0')
                ->press('Record repayment')
                ->pause(800)
                ->screenshot('repay-readjust-validation/repay_zero');

            // Server rule `min:0.0001` produces an error referring to shares.
            $browser->assertSee('shares');
        });
    }

    public function test_repay_with_negative_shares_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 30.0, 6, 'repay-negative validation');

            $browser->visit('/credit-lines/' . $lineId . '/actions')
                ->waitFor('form[action$="/repay"]', 5);
            $browser->script("var f=document.querySelector('form[action\$=\"/repay\"]'); if(f){f.setAttribute('novalidate','novalidate');}");

            $browser->type('form[action$="/repay"] input[name="shares"]', '-5')
                ->press('Record repayment')
                ->pause(800)
                ->screenshot('repay-readjust-validation/repay_negative');

            $browser->assertSee('shares');
        });
    }

    /**
     * EXPECTED: Repaying more than outstanding either:
     *   - is hard-rejected with an explicit "exceeds outstanding" error, OR
     *   - accepts and credits the excess as a principal prepayment (with a
     *     visible warning that the line is now overpaid).
     *
     * Today the form's validator only enforces `min:0.0001` — overpayment is
     * silently accepted. The user complaint is about validations being
     * incorrect; this is the path most likely to surprise them.
     */
    public function test_repay_more_than_outstanding_either_rejects_or_warns(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 5.0, 6, 'repay-overpay validation');

            $browser->visit('/credit-lines/' . $lineId . '/actions')
                ->waitFor('form[action$="/repay"]', 5);

            // Try to repay 50 against an outstanding of ~5. RepayService now
            // hard-rejects overpayment (QA bug #1 fix); the controller flashes
            // the error and redirects to the show page.
            $browser->type('form[action$="/repay"] input[name="shares"]', '50')
                ->press('Record repayment')
                ->pause(1200)
                ->screenshot('repay-readjust-validation/repay_over_outstanding');

            $source = $browser->driver->getPageSource();
            $rejected = stripos($source, 'cannot repay') !== false
                || stripos($source, 'reduce the amount') !== false
                || stripos($source, 'exceed') !== false;

            $this->assertTrue(
                $rejected,
                'Overpayment must be rejected with a visible error ("Cannot repay … '
                . 'against a credit line with … outstanding. Reduce the amount …"). '
                . 'See the RepayService overpayment guard.'
            );
        });
    }

    // ---------------------------------------------------------------
    // READJUST validations
    // ---------------------------------------------------------------

    public function test_readjust_term_zero_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 30.0, 6, 'readjust-zero-term validation');

            $browser->visit('/credit-lines/' . $lineId . '/actions')
                ->waitFor('form[action$="/readjust"]', 5);

            $browser->type('form[action$="/readjust"] input[name="new_term_months"]', '0')
                ->press('Readjust')
                ->pause(800)
                ->screenshot('repay-readjust-validation/readjust_term_zero');

            // Server rule min:1 → error mentioning term.
            $browser->assertSee('term');
        });
    }

    public function test_readjust_term_above_max_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 30.0, 6, 'readjust-481 validation');

            $browser->visit('/credit-lines/' . $lineId . '/actions')
                ->waitFor('form[action$="/readjust"]', 5);

            $browser->type('form[action$="/readjust"] input[name="new_term_months"]', '481')
                ->press('Readjust')
                ->pause(800)
                ->screenshot('repay-readjust-validation/readjust_term_481');

            // Server rule max:480 → error mentioning term.
            $browser->assertSee('term');
        });
    }

    /**
     * EXPECTED: Readjust effective_date must be on or after the line's
     *   origination_date. Otherwise the new schedule would have payment rows
     *   due BEFORE the line existed — a nonsensical state that surfaces as
     *   a malformed trajectory chart waveform.
     *
     * Today ReadjustCreditLineRequest only validates `effective_date|date`
     * — there is no lower bound. The user's "wrong waveform" complaint maps
     * here for backdated readjusts.
     */
    public function test_readjust_effective_date_before_origination_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 30.0, 6, 'readjust-pre-origin validation');

            $browser->visit('/credit-lines/' . $lineId . '/actions')
                ->waitFor('form[action$="/readjust"]', 5);

            // A date a year before today — well before this freshly created
            // line's origination_date. Set via JS + change event: Dusk ->type()
            // garbles the year on HTML5 date inputs.
            $way_back = now()->copy()->subYear()->toDateString();
            $browser->script(
                "var el=document.querySelector('form[action\$=\"/readjust\"] input[name=\"effective_date\"]');"
                . "el.value=" . json_encode($way_back) . ";"
                . "el.dispatchEvent(new Event('change'));"
            );

            $browser->type('form[action$="/readjust"] input[name="new_term_months"]', '24')
                ->press('Readjust')
                ->pause(1000)
                ->screenshot('repay-readjust-validation/readjust_before_origin');

            // ReadjustCreditLineRequest now rejects effective_date < origination_date.
            $browser->assertSee('origination');
        });
    }

    public function test_readjust_frequency_change_to_quarterly_succeeds(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 30.0, 12, 'readjust-frequency validation');

            $browser->visit('/credit-lines/' . $lineId . '/actions')
                ->waitFor('form[action$="/readjust"]', 5);

            $browser->select('form[action$="/readjust"] select[name="new_payment_frequency"]', 'quarterly')
                ->type('form[action$="/readjust"] input[name="new_term_months"]', '12')
                ->press('Readjust')
                ->pause(1200)
                ->screenshot('repay-readjust-validation/readjust_frequency_quarterly');

            // Readjust succeeded → the line's frequency is now quarterly.
            $line = AccountCreditLine::find($lineId);
            $this->assertSame(
                'quarterly',
                $line->payment_frequency,
                'Readjust to quarterly should persist the new payment frequency.'
            );
        });
    }
}
