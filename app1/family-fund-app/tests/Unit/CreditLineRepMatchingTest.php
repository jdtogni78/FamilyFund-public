<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\TransactionExt;
use App\Models\TransactionMatching;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Locks in the *current* behavior of the contribution-matching subsystem
 * against credit-line REP transactions, so we can detect any accidental
 * change when the future "applies_to_rep" toggle (see
 * docs/credit_lines/matching_on_repayment.md) is built.
 *
 * Today: REPs have value=0 (Phase 1a deferred cash leg), so even though
 * processPending() reaches createMatching() for every non-fund-account
 * non-NO_MATCH transaction, the math always returns 0 → no MAT row.
 */
class CreditLineRepMatchingTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    public function test_match_returns_zero_for_rep_with_zero_value(): void
    {
        $this->factory->createMatching(
            dollar_end: 1000,
            match: 100,
            start: '2026-01-01',
            end: '2027-01-01'
        );
        $account = $this->factory->userAccount;
        $amr = $account->accountMatchingRules()->first();

        $rep = $this->factory->createTransaction(
            value: 0,
            account: $account,
            type: TransactionExt::TYPE_REPAY,
            status: TransactionExt::STATUS_PENDING,
            flags: null,
            timestamp: '2026-06-15'
        );

        // The matcher's per-rule applicability — proportional to $tx->value.
        // With value=0, even a 100% rule returns 0.
        $matchValue = $amr->match($rep);

        $this->assertSame(0.0, (float) $matchValue,
            'Credit-line REPs have value=0; the contribution matcher must compute 0 match.');
    }

    public function test_match_returns_non_zero_for_pur_with_real_value(): void
    {
        $this->factory->createMatching(
            dollar_end: 1000,
            match: 100,
            start: '2026-01-01',
            end: '2027-01-01'
        );
        $account = $this->factory->userAccount;
        $amr = $account->accountMatchingRules()->first();

        $pur = $this->factory->createTransaction(
            value: 50,
            account: $account,
            type: TransactionExt::TYPE_PURCHASE,
            status: TransactionExt::STATUS_PENDING,
            flags: null,
            timestamp: '2026-06-15'
        );

        $matchValue = $amr->match($pur);

        $this->assertGreaterThan(0, (float) $matchValue,
            'PUR with non-zero value should produce a non-zero match.');
    }
}
