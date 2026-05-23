<?php

namespace Tests\Browser;

use App\Models\AccountCreditLine;
use App\Models\AccountExt;
use App\Models\CreditLinePayment;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\LateDetector;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser test for the #9 "Catch up" helper button on the credit-line show page.
 *
 * The Feature test (CreditLineCatchUpButtonTest) covers the server-rendered
 * contract (button presence + total). This covers the client-side behaviour:
 * clicking "Catch up" opens the payment modal with its Shares field already
 * pre-filled with the total shares due across all late rows.
 *
 * Uses ACCOUNT_ID = 7 against the slot DB, mirroring the other credit-line
 * Dusk suites; the line created here is soft-cancelled in tearDown.
 */
class CreditLineCatchUpButtonTest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;
    private array $createdLineIds = [];

    protected function tearDown(): void
    {
        foreach ($this->createdLineIds as $id) {
            try {
                AccountCreditLine::find($id)?->update(['status' => 'cancelled']);
            } catch (\Throwable $e) {
                // best-effort cleanup
            }
        }
        parent::tearDown();
    }

    public function test_catch_up_button_prefills_payment_modal_with_total_late_shares(): void
    {
        // Arrange: a backdated line on account 7 with a late backlog.
        $account = AccountExt::findOrFail(self::ACCOUNT_ID);
        $line = (new DrawService(new AmortizationScheduleBuilder(), new OutstandingCalculator()))
            ->open($account, 30.0, 12, 'monthly', 'catchup-dusk', Carbon::today()->subMonths(9));
        $this->createdLineIds[] = (int) $line->id;

        (new LateDetector())->detectAll();

        $lateShares = (float) CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_LATE)
            ->sum('shares_due');
        $this->assertGreaterThan(0.0, $lateShares, 'fixture should produce a late backlog');
        $expected = number_format($lateShares, 4, '.', '');

        $this->browse(function (Browser $browser) use ($line, $expected) {
            $browser->visit('/dev-login/credit-lines/' . $line->id)
                ->waitFor('#catchUpBtn', 10)
                ->assertSee('Catch up')
                ->click('#catchUpBtn')
                ->pause(700) // modal fade-in + show.bs.modal handler
                ->screenshot('catchup/prefill')
                ->assertValue('#makePaymentShares', $expected);
        });
    }
}
