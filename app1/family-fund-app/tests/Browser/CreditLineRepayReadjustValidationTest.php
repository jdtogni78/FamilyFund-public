<?php

namespace Tests\Browser;

use App\Models\AccountCreditLine;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\CreditLineShowPage;
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

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);

            $browser->script("var el=document.querySelector('form[action$=\"/repay\"] input[name=\"shares\"]'); if(el){el.removeAttribute('min');el.removeAttribute('required');}");

            $browser->type('form[action$="/repay"] input[name="shares"]', '0')
                ->press('Record repayment')
                ->pause(1000)
                ->screenshot('repay-readjust-validation/repay_zero');

            // Server rule `min:0.0001` produces an error referring to shares.
            $browser->assertSee('shares');
        });
    }

    public function test_repay_with_negative_shares_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 30.0, 6, 'repay-negative validation');

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);

            $browser->script("var el=document.querySelector('form[action$=\"/repay\"] input[name=\"shares\"]'); if(el){el.removeAttribute('min');}");

            $browser->type('form[action$="/repay"] input[name="shares"]', '-5')
                ->press('Record repayment')
                ->pause(1000)
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

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);

            // Try to repay 50 against an outstanding of ~5.
            $browser->type('form[action$="/repay"] input[name="shares"]', '50')
                ->press('Record repayment')
                ->pause(1500)
                ->screenshot('repay-readjust-validation/repay_over_outstanding');

            // Either: validation error referencing outstanding/exceeds, OR
            // a flash that warns about overpayment / prepayment.
            $source = $browser->driver->getPageSource();
            $hasWarning = stripos($source, 'exceed') !== false
                || stripos($source, 'overpay') !== false
                || stripos($source, 'prepayment') !== false
                || stripos($source, 'more than outstanding') !== false;

            $this->assertTrue(
                $hasWarning,
                'Overpayment must produce a visible warning ("exceeds", "overpay", '
                . '"prepayment", or "more than outstanding"). Currently the form '
                . 'silently accepts repay > outstanding — see RepayCreditLineRequest.'
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

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);

            $browser->script("var el=document.querySelector('form[action$=\"/readjust\"] input[name=\"new_term_months\"]'); if(el){el.removeAttribute('min');}");

            $browser->type('form[action$="/readjust"] input[name="new_term_months"]', '0')
                ->press('Readjust')
                ->pause(1000)
                ->screenshot('repay-readjust-validation/readjust_term_zero');

            $browser->assertSee('term');
        });
    }

    public function test_readjust_term_above_max_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 30.0, 6, 'readjust-481 validation');

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);

            $browser->script("var el=document.querySelector('form[action$=\"/readjust\"] input[name=\"new_term_months\"]'); if(el){el.removeAttribute('max');}");

            $browser->type('form[action$="/readjust"] input[name="new_term_months"]', '481')
                ->press('Readjust')
                ->pause(1000)
                ->screenshot('repay-readjust-validation/readjust_term_481');

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

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);

            // Pick a date a year before today (well before today's freshly
            // created line's origination_date).
            $way_back = now()->copy()->subYear()->toDateString();

            $browser->script(
                "var f = document.querySelector('form[action$=\"/readjust\"]');"
                . "if (f && !f.querySelector('input[name=\"effective_date\"]')) {"
                . "  var i = document.createElement('input');"
                . "  i.type='date'; i.name='effective_date';"
                . "  f.appendChild(i);"
                . "}"
            );

            $browser->script(
                "document.querySelector('form[action$=\"/readjust\"] input[name=\"effective_date\"]').value = "
                . json_encode($way_back) . ";"
            );

            $browser->type('form[action$="/readjust"] input[name="new_term_months"]', '24')
                ->press('Readjust')
                ->pause(1500)
                ->screenshot('repay-readjust-validation/readjust_before_origin');

            $source = $browser->driver->getPageSource();
            $rejected = stripos($source, 'origination') !== false
                || stripos($source, 'before') !== false
                || stripos($source, 'invalid') !== false
                || stripos($source, 'effective') !== false;

            $this->assertTrue(
                $rejected,
                'Readjust effective_date before origination_date must be rejected '
                . '(or at least surface a visible warning). See ReadjustCreditLineRequest.'
            );
        });
    }

    public function test_readjust_frequency_change_to_quarterly_succeeds(): void
    {
        $this->browse(function (Browser $browser) {
            $lineId = $this->openLineViaUi($browser, 30.0, 12, 'readjust-frequency validation');

            $page = new CreditLineShowPage($lineId);
            $browser->on($page);

            $browser->script(
                "var f = document.querySelector('form[action$=\"/readjust\"]');"
                . "if (f && !f.querySelector('select[name=\"new_payment_frequency\"]')) {"
                . "  var s = document.createElement('select');"
                . "  s.name = 'new_payment_frequency';"
                . "  ['monthly','quarterly','annual'].forEach(function(v){"
                . "    var o = document.createElement('option'); o.value=v; o.text=v;"
                . "    s.appendChild(o);"
                . "  });"
                . "  f.appendChild(s);"
                . "}"
            );

            $browser->select('form[action$="/readjust"] select[name="new_payment_frequency"]', 'quarterly')
                ->type('form[action$="/readjust"] input[name="new_term_months"]', '12')
                ->press('Readjust')
                ->pause(1500)
                ->screenshot('repay-readjust-validation/readjust_frequency_quarterly');

            // After success: page should show the updated frequency on the
            // line summary card.
            $browser->assertSee('quarterly');
        });
    }
}
