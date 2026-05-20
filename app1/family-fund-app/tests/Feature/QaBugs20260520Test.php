<?php

namespace Tests\Feature;

use App\Models\AccountBalance;
use App\Models\Asset;
use App\Models\TransactionExt;
use App\Models\User;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Regression coverage for the open items in docs/QA_BUGS_2026-05-20.md.
 *
 * One test per bug number; each one asserts the post-fix behavior so the
 * file as a whole serves as the reproducer and the guard against regression.
 */
class QaBugs20260520Test extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $df;
    private User $admin;
    private DrawService $draw;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->df = new DataFactory();
        $this->df->createFund(1_000_000, 1_000_000, '2020-01-01');
        $this->df->createUser();
        $this->admin = $this->df->user;

        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->admin->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);

        $this->draw = new DrawService(
            new AmortizationScheduleBuilder(),
            new OutstandingCalculator()
        );

        $this->seedOwnBalance($this->df->userAccount, 500);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    /**
     * #2: repay endpoint rejects dates outside [origination_date, today].
     *
     * Backdated or future-dated REP transactions shift the account_balances
     * ledger out of order and create phantom rows that become the active
     * row at that date.
     */
    public function test_bug_2_repay_rejects_dates_outside_origination_to_today(): void
    {
        $account = $this->df->userAccount;
        // Origination 6 months ago so we have a meaningful "before-origination" date.
        $origination = Carbon::today()->subMonths(6);
        $line = $this->draw->open($account, 100.0, 12, 'monthly', null, $origination);

        $route = route('credit_lines.repay', ['line' => $line->id]);
        $countReps = fn () => TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_REPAY)
            ->count();

        // -- backdated (before origination) is rejected, no REP row ---------
        $before = $countReps();
        $resp = $this->actingAs($this->admin)->post($route, [
            'account_credit_line_id' => $line->id,
            'shares'                 => 5,
            'date'                   => $origination->copy()->subDay()->toDateString(),
        ]);
        $resp->assertRedirect();
        $this->assertEquals($before, $countReps(), 'backdated repay must not create a REP row');

        // -- future-dated (after today) is rejected, no REP row -------------
        $before = $countReps();
        $resp = $this->actingAs($this->admin)->post($route, [
            'account_credit_line_id' => $line->id,
            'shares'                 => 5,
            'date'                   => Carbon::today()->addDay()->toDateString(),
        ]);
        $resp->assertRedirect();
        $this->assertEquals($before, $countReps(), 'future-dated repay must not create a REP row');

        // -- far-future (years out) is rejected ----------------------------
        $before = $countReps();
        $resp = $this->actingAs($this->admin)->post($route, [
            'account_credit_line_id' => $line->id,
            'shares'                 => 5,
            'date'                   => Carbon::today()->addYears(20)->toDateString(),
        ]);
        $resp->assertRedirect();
        $this->assertEquals($before, $countReps(), 'far-future repay must not create a REP row');

        // -- valid date (origination_date itself) is accepted --------------
        $before = $countReps();
        $resp = $this->actingAs($this->admin)->post($route, [
            'account_credit_line_id' => $line->id,
            'shares'                 => 5,
            'date'                   => $origination->toDateString(),
        ]);
        $resp->assertRedirect();
        $this->assertEquals($before + 1, $countReps(), 'repay on origination_date should be accepted');

        // -- valid date (today) is accepted --------------------------------
        $before = $countReps();
        $resp = $this->actingAs($this->admin)->post($route, [
            'account_credit_line_id' => $line->id,
            'shares'                 => 5,
            'date'                   => Carbon::today()->toDateString(),
        ]);
        $resp->assertRedirect();
        $this->assertEquals($before + 1, $countReps(), 'repay dated today should be accepted');

        // -- ledger invariants: no transactions or balance rows in the future
        $this->assertEquals(
            0,
            TransactionExt::where('account_id', $account->id)
                ->where('timestamp', '>', Carbon::today()->endOfDay())
                ->count(),
            'no transactions should be dated in the future'
        );
        $this->assertEquals(
            0,
            AccountBalance::where('account_id', $account->id)
                ->where('start_dt', '>', Carbon::today()->toDateString())
                ->count(),
            'no account_balances row should start in the future'
        );
    }

    private function seedOwnBalance($account, float $shares): void
    {
        $tran = $this->df->createTransaction(
            $shares * 10,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            Carbon::today()->subYear()->toDateString()
        );
        $tran->shares = $shares;
        $tran->save();

        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => Carbon::today()->subYear()->toDateString(),
            'end_dt'         => '9999-12-31',
        ]);
    }
}
