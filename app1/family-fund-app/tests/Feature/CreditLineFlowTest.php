<?php

namespace Tests\Feature;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\TransactionExt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * End-to-end happy path for the credit-line feature (Phase 2 wiring).
 *
 * Flow: admin opens a line  →  records a repayment  →  readjusts term  →  reverses the repayment.
 */
class CreditLineFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // CASH asset must exist for fund setup.
        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->df = new DataFactory();
        $this->df->createFund(1000, 1000, '2022-01-01');
        $this->df->createUser();
        $this->admin = $this->df->user;

        // Assign system-admin role at team_id=0 (matches existing pattern).
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->admin->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);

        // Seed OWN balance so the account can borrow.
        $this->seedOwnBalance($this->df->userAccount, 500);

        Mail::fake();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_admin_can_open_repay_readjust_and_reverse_a_credit_line(): void
    {
        $account = $this->df->userAccount;

        // --- 1. Open ----------------------------------------------------
        $openResponse = $this->actingAs($this->admin)->post(
            route('credit_lines.store', ['account' => $account->id]),
            [
                'account_id'        => $account->id,
                'principal_shares'  => 100,
                'term_months'       => 12,
                'payment_frequency' => 'monthly',
                'descr'             => 'Feature-test line',
            ]
        );
        $openResponse->assertRedirect();
        $line = AccountCreditLine::where('account_id', $account->id)->latest('id')->first();
        $this->assertNotNull($line);
        $this->assertEquals(100.0, (float) $line->principal_shares);
        $this->assertEquals('active', $line->status);

        // --- 2. Repay ---------------------------------------------------
        $repayResponse = $this->actingAs($this->admin)->post(
            route('credit_lines.repay', ['line' => $line->id]),
            [
                'account_credit_line_id' => $line->id,
                'shares'                 => 25,
            ]
        );
        $repayResponse->assertRedirect();
        $line->refresh();
        $this->assertEquals(75.0, round((float) $line->outstanding_shares, 4));

        $repTran = TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_REPAY)
            ->latest('id')
            ->first();
        $this->assertNotNull($repTran);

        // --- 3. Readjust ------------------------------------------------
        $readjustResponse = $this->actingAs($this->admin)->post(
            route('credit_lines.readjust', ['line' => $line->id]),
            [
                'account_credit_line_id' => $line->id,
                'new_term_months'        => 24,
                'reason'                 => 'extending term',
            ]
        );
        $readjustResponse->assertRedirect();
        $line->refresh();
        $this->assertEquals(24, (int) $line->term_months);

        // --- 3b. Verify show page surfaces the adjustment timeline actions
        // (Phase 4: schedule snapshot + trajectory-through-this-point modals).
        $showResponse = $this->actingAs($this->admin)->get(
            route('credit_lines.show', ['line' => $line->id])
        );
        $showResponse->assertOk();
        $showResponse->assertSee('View schedule at this point', false);
        $showResponse->assertSee('View trajectory through this point', false);
        $showResponse->assertSee('schedule-snapshot-', false);
        $showResponse->assertSee('trajectory-through-', false);

        // --- 4. Reverse repayment --------------------------------------
        $reverseResponse = $this->actingAs($this->admin)->post(
            route('credit_lines.reverse', ['transaction' => $repTran->id]),
            [
                'transaction_id' => $repTran->id,
                'reason'         => 'data entry error',
            ]
        );
        $reverseResponse->assertRedirect();
        $repTran->refresh();
        $this->assertTrue((bool) $repTran->reversed);
        $line->refresh();
        $this->assertEquals(100.0, round((float) $line->outstanding_shares, 4));
    }

    /**
     * UC-20: admin can edit per-line notification settings via PUT /credit-lines/{line}.
     */
    public function test_admin_can_update_credit_line_notification_settings(): void
    {
        $account = $this->df->userAccount;

        // Open a line first.
        $this->actingAs($this->admin)->post(
            route('credit_lines.store', ['account' => $account->id]),
            [
                'account_id'        => $account->id,
                'principal_shares'  => 50,
                'term_months'       => 6,
                'payment_frequency' => 'monthly',
                'descr'             => 'Settings-test line',
            ]
        );
        $line = AccountCreditLine::where('account_id', $account->id)->latest('id')->first();
        $this->assertNotNull($line);

        // Defaults from the migration.
        $this->assertSame(7, (int) $line->reminder_lead_days);
        $this->assertTrue((bool) $line->reminder_enabled);

        // PUT with new settings (UC-20).
        $response = $this->actingAs($this->admin)->put(
            route('credit_lines.update', ['line' => $line->id]),
            [
                'reminder_lead_days'              => 14,
                // reminder_enabled deliberately omitted → checkbox off → false
                'delay_notification_grace_days'   => 5,
                'delay_notification_repeat_days'  => 30,
                'delay_notification_max_repeats'  => 3,
                'transaction_email_enabled'       => '1',
                'mismatch_alert_enabled'          => '1',
            ]
        );
        $response->assertRedirect(route('credit_lines.show', ['line' => $line->id]));

        $line->refresh();
        $this->assertSame(14, (int) $line->reminder_lead_days);
        $this->assertFalse((bool) $line->reminder_enabled);
        $this->assertSame(5, (int) $line->delay_notification_grace_days);
        $this->assertSame(30, (int) $line->delay_notification_repeat_days);
        $this->assertSame(3, (int) $line->delay_notification_max_repeats);
        $this->assertTrue((bool) $line->transaction_email_enabled);
        $this->assertTrue((bool) $line->mismatch_alert_enabled);

        // Verify the edit form renders all 7 fields.
        $edit = $this->actingAs($this->admin)->get(
            route('credit_lines.edit', ['line' => $line->id])
        );
        $edit->assertOk();
        $edit->assertSee('reminder_lead_days', false);
        $edit->assertSee('reminder_enabled', false);
        $edit->assertSee('delay_notification_grace_days', false);
        $edit->assertSee('delay_notification_repeat_days', false);
        $edit->assertSee('delay_notification_max_repeats', false);
        $edit->assertSee('transaction_email_enabled', false);
        $edit->assertSee('mismatch_alert_enabled', false);
    }

    /**
     * UC-47: account closure must be blocked while an active line remains.
     */
    public function test_account_closure_blocked_when_active_credit_line_exists(): void
    {
        $account = $this->df->userAccount;

        $this->actingAs($this->admin)->post(
            route('credit_lines.store', ['account' => $account->id]),
            [
                'account_id'        => $account->id,
                'principal_shares'  => 30,
                'term_months'       => 6,
                'payment_frequency' => 'monthly',
                'descr'             => 'Closure-block-test line',
            ]
        );
        $line = AccountCreditLine::where('account_id', $account->id)->latest('id')->first();
        $this->assertNotNull($line);
        $this->assertSame('active', $line->status);

        $response = $this->actingAs($this->admin)->delete(
            route('accounts.destroy', ['account' => $account->id])
        );
        // Either a redirect with flash error or a 4xx — both are acceptable;
        // what matters is that the account row survives.
        $this->assertTrue(
            $response->isRedirect() || $response->status() >= 400,
            'Expected redirect or 4xx; got ' . $response->status()
        );
        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    private function seedOwnBalance($account, float $shares): void
    {
        $tran = $this->df->createTransaction(
            $shares * 10,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            Carbon::today()->toDateString()
        );
        $tran->shares = $shares;
        $tran->save();

        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => Carbon::today()->toDateString(),
            'end_dt'         => '9999-12-31',
        ]);
    }
}
