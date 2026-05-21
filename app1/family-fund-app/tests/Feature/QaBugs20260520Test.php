<?php

namespace Tests\Feature;

use App\Models\AccountBalance;
use App\Models\Asset;
use App\Models\TransactionExt;
use App\Models\User;
use App\Services\CreditLine\Cancel\CancelService;
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

    /**
     * #3: a massive overpay on one CL must not desync borrowedSharesAsOf
     * from the sum of active CLs' outstanding_shares. Structurally closed
     * by #1's rejection of shares > outstanding (no overpayment, no ghost).
     */
    public function test_bug_3_excess_does_not_cascade_or_ghost_across_lines(): void
    {
        $account = $this->df->userAccount;

        // Two active CLs on the same account.
        $lineA = $this->draw->open($account, 100.0, 12, 'monthly', null, Carbon::today()->subMonths(2));
        $lineB = $this->draw->open($account, 50.0,  12, 'monthly', null, Carbon::today()->subMonths(2));

        $aBefore = round((float) $lineA->refresh()->outstanding_shares, 4);
        $bBefore = round((float) $lineB->refresh()->outstanding_shares, 4);
        $borrowedBefore = round((float) $account->borrowedSharesAsOf(Carbon::today()->toDateString()), 4);
        $this->assertEquals($aBefore + $bBefore, $borrowedBefore, 'pre-condition: borrowedSharesAsOf == sum of outstandings');

        $repCountBefore = TransactionExt::where('account_id', $account->id)
            ->where('type', TransactionExt::TYPE_REPAY)->count();

        // 10x overpay on lineA — repro E4 from the QA doc. Must be rejected.
        $resp = $this->actingAs($this->admin)->post(
            route('credit_lines.repay', ['line' => $lineA->id]),
            [
                'account_credit_line_id' => $lineA->id,
                'shares'                 => $aBefore * 10,
                'date'                   => Carbon::today()->toDateString(),
            ]
        );
        $resp->assertRedirect();

        // Both lines untouched; no REP rows added; no ghost vs borrowedSharesAsOf.
        $this->assertEquals($aBefore, round((float) $lineA->refresh()->outstanding_shares, 4),
            'lineA outstanding must be unchanged after a rejected overpay');
        $this->assertEquals($bBefore, round((float) $lineB->refresh()->outstanding_shares, 4),
            'lineB (another active CL on the same account) must not be touched');
        $this->assertEquals(
            $repCountBefore,
            TransactionExt::where('account_id', $account->id)->where('type', TransactionExt::TYPE_REPAY)->count(),
            'a rejected overpay must not append any REP transaction'
        );
        $borrowedAfter = round((float) $account->borrowedSharesAsOf(Carbon::today()->toDateString()), 4);
        $sumActive = round(
            (float) \App\Models\AccountCreditLine::where('account_id', $account->id)
                ->where('status', 'active')->sum('outstanding_shares'),
            4
        );
        $this->assertEquals($sumActive, $borrowedAfter,
            'borrowedSharesAsOf must still match sum of active CL outstandings (no ghost shares)');
    }

    /**
     * #4: cancellation must refresh the BOR aggregate ledger so a cancelled
     * CL's contribution stops being counted by borrowedSharesAsOf(). Today
     * the controller blocks cancel-with-outstanding, but the seed baseline
     * contains a CL in state status=cancelled+outstanding=108 — i.e. the
     * inconsistency is reachable somehow. Test exercises a force/writeoff
     * bypass into CancelService and asserts:
     *   - borrowedSharesAsOf(now) == 0 immediately after cancel
     *   - borrowedSharesAsOf(yesterday) still reflects the active period
     *   - the line is normalized: status=cancelled, outstanding_shares=0
     *   - no synthetic REP / BOR transaction is written on the account
     */
    public function test_bug_4_force_cancel_refreshes_bor_ledger_and_zeroes_outstanding(): void
    {
        $account = $this->df->userAccount;

        $origination = Carbon::today()->subDays(7);
        $line = $this->draw->open($account, 100.0, 12, 'monthly', null, $origination);

        $borrowedBefore = round((float) $account->borrowedSharesAsOf(Carbon::today()->toDateString()), 4);
        $this->assertEquals(100.0, $borrowedBefore, 'pre-condition: BOR ledger shows the 100-share draw');

        $txCountBefore = TransactionExt::where('account_id', $account->id)->count();

        // Force-cancel with outstanding > 0 (the write-off path).
        $cancelService = app(CancelService::class);
        $cancelService->cancel($line, force: true);

        $line->refresh();
        $this->assertEquals('cancelled', $line->status, 'cancel must mark status=cancelled');
        $this->assertEquals(
            0.0,
            round((float) $line->outstanding_shares, 4),
            'a cancelled line must have outstanding_shares=0 to stay consistent with status'
        );

        $this->assertEquals(
            0.0,
            round((float) $account->borrowedSharesAsOf(Carbon::today()->toDateString()), 4),
            'borrowedSharesAsOf(now) must drop to 0 immediately after cancellation, not wait for the next BOR/REP'
        );

        $this->assertEquals(
            100.0,
            round((float) $account->borrowedSharesAsOf($origination->copy()->addDay()->toDateString()), 4),
            'historical state (during the line\'s active period) must be preserved by the ledger'
        );

        $this->assertEquals(
            $txCountBefore,
            TransactionExt::where('account_id', $account->id)->count(),
            'cancellation must not synthesize a phantom REP/BOR transaction on the account'
        );
    }

    /**
     * #5: a same-day BOR → full REP cycle must leave at least one BOR row in
     * account_balances so the audit trail "this account was borrowed against
     * on this date" is recoverable from the ledger. Wave-2's in-place
     * collapse used to delete the row when start_dt==asOf, leaving zero
     * historical rows. The row is now kept as a zero-length closed record
     * (start_dt==end_dt) — invisible to asOf queries, but discoverable.
     */
    public function test_bug_5_same_day_bor_then_full_rep_preserves_audit_row(): void
    {
        $account = $this->df->userAccount;
        $today = Carbon::today();

        $line = $this->draw->open($account, 300.0, 12, 'monthly', null, $today);
        // Two REPs that together fully clear the line on the same day.
        $repay = app(\App\Services\CreditLine\Repay\RepayService::class);
        $repay->repay($line, 30.0, $today);
        $repay->repay($line, 270.0, $today);

        $borRows = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->get();

        $this->assertGreaterThanOrEqual(
            1,
            $borRows->count(),
            'audit trail: at least one BOR row must survive same-day BOR→full REP'
        );

        // No row should be open after a full repay.
        $open = $borRows->filter(fn ($r) => $r->end_dt && $r->end_dt->toDateString() === '9999-12-31');
        $this->assertCount(0, $open, 'No open BOR row should remain after a full repay');

        // The surviving row(s) must not interfere with asOf reads.
        $this->assertEquals(
            0.0,
            round((float) $account->borrowedSharesAsOf($today->toDateString()), 4),
            'borrowedSharesAsOf(today) must be 0 after full same-day repay'
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
