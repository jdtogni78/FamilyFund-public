<?php

namespace Tests\Browser;

use App\Models\AccountCreditLine;
use App\Models\AccountExt;
use App\Models\CreditLinePayment;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser test for #10 — rolled-up adjacent late schedule rows expand/collapse.
 *
 * The Feature test (CreditLineLateRollupTest) covers the server-rendered
 * roll-up (one summary row, drill-down rows hidden). This covers the
 * client-side toggle: the detail rows start hidden and the summary's button
 * shows/hides them.
 *
 * Uses ACCOUNT_ID = 7 against the slot DB, like the other credit-line Dusk
 * suites; the line is soft-cancelled in tearDown.
 */
class CreditLineLateRollupTest extends DuskTestCase
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

    public function test_late_group_rolls_up_and_toggles_open(): void
    {
        $account = AccountExt::findOrFail(self::ACCOUNT_ID);
        $line = (new DrawService(new AmortizationScheduleBuilder(), new OutstandingCalculator()))
            ->open($account, 120.0, 12, 'monthly', 'late-rollup-dusk', Carbon::today());
        $this->createdLineIds[] = (int) $line->id;

        // Make three consecutive rows late -> one adjacent run -> one summary.
        $rows = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')->get();
        foreach ([1, 2, 3] as $k) {
            $rows[$k]->update(['status' => CreditLinePayment::STATUS_LATE]);
        }

        $this->browse(function (Browser $browser) use ($line) {
            $browser->visit('/dev-login/credit-lines/' . $line->id)
                ->waitFor('.late-group-summary', 10)
                ->assertSee('shares overdue from')
                // Detail rows are present but collapsed initially.
                ->assertPresent('tr.late-group-detail')
                ->assertMissing('tr.late-group-detail')
                // Expand.
                ->click('.late-group-toggle')
                ->pause(300)
                ->screenshot('late-rollup/expanded')
                ->assertVisible('tr.late-group-detail')
                // Collapse again.
                ->click('.late-group-toggle')
                ->pause(300)
                ->assertMissing('tr.late-group-detail');
        });
    }
}
