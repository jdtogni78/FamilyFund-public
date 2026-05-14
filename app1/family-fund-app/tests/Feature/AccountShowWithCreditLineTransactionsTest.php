<?php

namespace Tests\Feature;

use App\Models\AccountBalance;
use App\Models\Asset;
use App\Models\TransactionExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Regression test for bug #1 in credit_lines_bugs_found.md:
 *
 * Loading the account show page crashed with DivisionByZeroError when the
 * account had any BOR or REP transaction (value=0, deferred cash leg).
 *
 * Fix: AccountTrait::createTransactionsResponse guards against $value=0.
 */
class AccountShowWithCreditLineTransactionsTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->df = new DataFactory();
        $this->df->createFund(1000, 1000, '2022-01-01');
        $this->df->createUser();
        $this->admin = $this->df->user;

        if (method_exists($this->admin, 'assignRole')) {
            try { $this->admin->assignRole('system-admin'); } catch (\Throwable $e) { /* role optional in test */ }
        }
    }

    public function test_account_show_does_not_crash_when_account_has_bor_transaction_with_zero_value(): void
    {
        $account = $this->df->userAccount;

        // OWN balance so the account is in a sensible state.
        $ownTx = $this->df->createTransaction(
            value: 1000,
            account: $account,
            type: TransactionExt::TYPE_PURCHASE,
            status: TransactionExt::STATUS_CLEARED,
            flags: null,
            timestamp: '2022-01-15',
        );
        $this->df->createBalance(100, $ownTx, $account, '2022-01-15');

        // The reproducer: a BOR transaction with value=0 (credit-line draw).
        TransactionExt::create([
            'type'       => TransactionExt::TYPE_BORROW,
            'status'     => TransactionExt::STATUS_CLEARED,
            'value'      => 0,
            'shares'     => 10,
            'timestamp'  => '2026-05-14',
            'account_id' => $account->id,
            'descr'      => 'regression: BOR with value=0',
        ]);

        // Same for REP.
        TransactionExt::create([
            'type'       => TransactionExt::TYPE_REPAY,
            'status'     => TransactionExt::STATUS_CLEARED,
            'value'      => 0,
            'shares'     => 2,
            'timestamp'  => '2026-05-14',
            'account_id' => $account->id,
            'descr'      => 'regression: REP with value=0',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/accounts/' . $account->id);

        // Before the fix: 500 DivisionByZeroError. After: 200 OK.
        $response->assertStatus(200);
        $response->assertSee($account->nickname);

        // The account-show view leaves an output buffer open (existing app
        // behavior); close it so PHPUnit doesn't mark this test risky.
        while (ob_get_level() > 1) {
            @ob_end_clean();
        }
    }
}
