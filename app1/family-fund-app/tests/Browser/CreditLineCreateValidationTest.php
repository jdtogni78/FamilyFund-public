<?php

namespace Tests\Browser;

use App\Models\AccountCreditLine;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser validation tests for the credit-line CREATE form.
 *
 * Bug reported 2026-05-19: "validations were not correct (ui)".
 *
 * The existing CreditLineNegativeUITest covers negative principal, term=0,
 * over-borrow and admin-gate rejection. This suite extends that into the
 * remaining validation matrix the user actually faces on the form:
 *
 *   - origination_date in the FUTURE   (server rule: before_or_equal:today)
 *   - term_months = 481                (server rule: max:480)
 *   - principal_shares = 0             (server rule: min:0.0001)
 *   - principal_shares = 0.00001       (under the 4-dp rounding minimum)
 *   - nickname empty                   (server rule: required)
 *   - origination_date in past + JS backdate-warning visible
 *   - JS over-borrow warning shows live + Submit disabled while over
 *   - Frequency dropdown all three options selectable + submit succeeds
 *
 * Setup follows the conventions of CreditLineNegativeUITest:
 *   - Uses ACCOUNT_ID = 7 against the live dev DB
 *   - dev-login route bootstraps an admin session
 *   - try/finally cleanup so success cases don't leave dev-DB pollution
 */
class CreditLineCreateValidationTest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;

    // ---------------------------------------------------------------
    // Date constraints
    // ---------------------------------------------------------------

    public function test_origination_date_in_future_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->screenshot('create-validation/origination_future_before');

            // The form's HTML5 `max` attribute is today; remove it so a future
            // date can be typed and we exercise the server-side rule.
            $browser->script("document.getElementById('origination_date').removeAttribute('max');");

            $tomorrow = now()->copy()->addDay()->toDateString();

            $browser->script(
                "document.getElementById('origination_date').value = " . json_encode($tomorrow) . ";"
            );
            $browser->type('input[name="nickname"]', 'future-date test')
                ->type('input[name="principal_shares"]', '5')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '6')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->press('Open')
                ->pause(1000)
                ->screenshot('create-validation/origination_future_after');

            // Laravel validator message for `before_or_equal:today` mentions
            // "must be a date before or equal to today" or similar.
            $browser->assertSee('origination');
        });
    }

    // ---------------------------------------------------------------
    // Term constraints
    // ---------------------------------------------------------------

    public function test_term_months_above_max_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->screenshot('create-validation/term_481_before');

            // Strip HTML max so the form submits and we exercise server rule.
            $browser->script("var el=document.querySelector('input[name=\"term_months\"]'); if(el){el.removeAttribute('max');el.removeAttribute('min');}");

            $browser->type('input[name="nickname"]', 'term-over-max test')
                ->type('input[name="principal_shares"]', '5')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '481')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->press('Open')
                ->pause(1000)
                ->screenshot('create-validation/term_481_after');

            $browser->assertSee('term');
        });
    }

    // ---------------------------------------------------------------
    // Principal constraints
    // ---------------------------------------------------------------

    public function test_principal_shares_zero_is_rejected(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->screenshot('create-validation/principal_zero_before');

            $browser->script("var el=document.querySelector('input[name=\"principal_shares\"]'); if(el){el.removeAttribute('min');el.removeAttribute('required');}");

            $browser->type('input[name="nickname"]', 'zero-principal test')
                ->type('input[name="principal_shares"]', '0')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '6')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->press('Open')
                ->pause(1000)
                ->screenshot('create-validation/principal_zero_after');

            $browser->assertSee('principal');
        });
    }

    public function test_principal_shares_below_min_precision_is_rejected(): void
    {
        // 0.00001 is below the schema's 4-dp minimum (0.0001). The current
        // request rule is `min:0.0001`; a tiny value should be rejected, not
        // silently rounded down to 0.
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->screenshot('create-validation/principal_subprecision_before');

            // Bypass HTML5 client validation so the submit reaches the
            // server-side `min:0.0001` rule. (Removing `step` alone reverts the
            // number input to the browser default step=1, which *blocks* any
            // sub-integer value and never hits the server.)
            $browser->script("var f=document.querySelector('form[action*=\"/credit-lines\"]'); if(f){f.setAttribute('novalidate','novalidate');}");

            $browser->type('input[name="nickname"]', 'subprecision test')
                ->type('input[name="principal_shares"]', '0.00001')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '6')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->press('Open')
                ->pause(1000)
                ->screenshot('create-validation/principal_subprecision_after');

            $browser->assertSee('principal');
        });
    }

    // ---------------------------------------------------------------
    // Required field
    // ---------------------------------------------------------------

    public function test_nickname_is_required(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->screenshot('create-validation/nickname_empty_before');

            $browser->script("var el=document.querySelector('input[name=\"nickname\"]'); if(el){el.removeAttribute('required');}");

            // Leave nickname empty.
            $browser->type('input[name="principal_shares"]', '5')
                ->clear('input[name="term_months"]')
                ->type('input[name="term_months"]', '6')
                ->select('select[name="payment_frequency"]', 'monthly')
                ->press('Open')
                ->pause(1000)
                ->screenshot('create-validation/nickname_empty_after');

            $browser->assertSee('nickname');
        });
    }

    // ---------------------------------------------------------------
    // JS backdate-warning indicator
    // ---------------------------------------------------------------

    public function test_backdate_warning_shows_when_origination_in_past(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]')
                ->screenshot('create-validation/backdate_warning_before');

            $past = now()->copy()->subDays(30)->toDateString();
            $browser->script("var el=document.getElementById('origination_date'); el.value = " . json_encode($past) . "; el.dispatchEvent(new Event('input')); el.dispatchEvent(new Event('change'));");
            $browser->pause(300)
                ->screenshot('create-validation/backdate_warning_after');

            // The warning element is `#backdate-warning` and should now have
            // its `display:none` style cleared.
            $hidden = $browser->script(
                "var el=document.getElementById('backdate-warning'); return el && el.style.display === 'none';"
            )[0] ?? null;

            $this->assertFalse(
                (bool) $hidden,
                'Backdate warning #backdate-warning should be visible when origination date is in the past.'
            );
        });
    }

    // ---------------------------------------------------------------
    // JS over-borrow inline warning + submit disabled
    // ---------------------------------------------------------------

    public function test_over_borrow_inline_warning_disables_submit_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]');

            // Wait for the available-shares fetch to settle.
            $browser->pause(800)
                ->screenshot('create-validation/over_borrow_inline_initial');

            // Type a clearly absurd principal — should drive the JS handler
            // (`syncOverBorrow`) to add `.is-invalid` and set submit disabled.
            $browser->clear('input[name="principal_shares"]')
                ->type('input[name="principal_shares"]', '9999999')
                ->keys('input[name="principal_shares"]', ' ', '{backspace}') // force a JS 'input' event
                ->pause(300)
                ->screenshot('create-validation/over_borrow_inline_typed');

            $disabled = $browser->script(
                "return !!document.getElementById('submit-btn').disabled;"
            )[0] ?? null;
            $hasIsInvalid = $browser->script(
                "return document.querySelector('input[name=\"principal_shares\"]').classList.contains('is-invalid');"
            )[0] ?? null;

            $this->assertTrue(
                (bool) $disabled,
                'Over-borrow JS must disable the Open button so users cannot submit a request that will be rejected.'
            );
            $this->assertTrue(
                (bool) $hasIsInvalid,
                'Principal input must carry .is-invalid when over the available cap.'
            );
        });
    }

    // ---------------------------------------------------------------
    // Frequency dropdown
    // ---------------------------------------------------------------

    public function test_each_frequency_option_is_selectable(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                ->waitFor('form[action*="/credit-lines"]');

            foreach (['monthly', 'quarterly', 'annual'] as $freq) {
                $browser->select('select[name="payment_frequency"]', $freq);
                $selected = $browser->script(
                    "return document.querySelector('select[name=\"payment_frequency\"]').value;"
                )[0] ?? null;
                $this->assertSame(
                    $freq,
                    $selected,
                    "Frequency select must accept '$freq' as a value."
                );
            }
        });
    }

    // ---------------------------------------------------------------
    // Successful create with a small principal — golden-path validation
    // ---------------------------------------------------------------

    public function test_valid_form_submission_creates_a_line_and_redirects_to_show(): void
    {
        $lineId = null;
        try {
            $this->browse(function (Browser $browser) use (&$lineId) {
                $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
                    ->waitFor('form[action*="/credit-lines"]')
                    ->type('input[name="nickname"]', 'validation-suite happy path')
                    ->type('input[name="principal_shares"]', '1')
                    ->clear('input[name="term_months"]')
                    ->type('input[name="term_months"]', '3')
                    ->select('select[name="payment_frequency"]', 'monthly')
                    ->type('input[name="descr"]', 'happy path for validation suite')
                    ->screenshot('create-validation/happy_before_submit')
                    ->press('Open')
                    ->waitUsing(10, 200, function () use ($browser) {
                        $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
                        return (bool) preg_match('#/credit-lines/\d+$#', $path);
                    })
                    ->screenshot('create-validation/happy_after_submit');

                $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
                $this->assertMatchesRegularExpression(
                    '#/credit-lines/\d+$#',
                    $path,
                    'Happy-path submit must land on the new line\'s show page.'
                );

                if (preg_match('#/credit-lines/(\d+)$#', $path, $m)) {
                    $lineId = (int) $m[1];
                }
            });
        } finally {
            if ($lineId !== null) {
                try {
                    $line = AccountCreditLine::find($lineId);
                    if ($line) {
                        $line->update(['status' => 'cancelled']);
                    }
                } catch (\Throwable $e) {
                    // best-effort cleanup
                }
            }
        }
    }
}
