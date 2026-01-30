<?php

namespace Tests\Feature;

use App\Http\Controllers\Traits\TransactionTrait;
use App\Models\AccountExt;
use App\Models\Asset;
use App\Models\AssetPrice;
use App\Models\TransactionExt;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for transaction preview value calculations.
 *
 * These tests verify that the projected account value calculations
 * in the transaction preview are accurate for financial reporting.
 *
 * Key scenarios:
 * - Simple deposit without matching
 * - Deposit with single matching rule
 * - Deposit with multiple matching rules
 * - Withdrawals
 */
class TransactionPreviewCalculationTest extends TestCase
{
    use DatabaseTransactions, TransactionTrait;

    protected DataFactory $factory;
    protected TransactionRepository $transactionRepository;
    protected string $fundDate = '2022-01-01';
    protected string $transactionDate = '2022-01-15';

    protected function setUp(): void
    {
        parent::setUp();

        // Create CASH asset required by DataFactory - use firstOrCreate to avoid duplicates
        $cashAsset = Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        // Only create AssetPrice for CASH if none exists (to avoid overlapping date ranges)
        if (AssetPrice::where('asset_id', $cashAsset->id)->count() === 0) {
            AssetPrice::factory()->create([
                'asset_id' => $cashAsset->id,
                'price' => 1.0,
                'start_dt' => '2020-01-01',
                'end_dt' => '9999-12-31',
            ]);
        }

        $this->factory = new DataFactory();
        $this->transactionRepository = app(TransactionRepository::class);
    }

    /**
     * Test: Simple deposit should increase account value by deposit amount.
     */
    public function test_simple_deposit_value_change()
    {
        $this->factory->createFund(1000, 1000, $this->fundDate);
        $this->factory->createUser();

        $account = $this->factory->userAccount;
        $depositAmount = 200;
        $timestamp = Carbon::parse($this->transactionDate);

        $valueBeforeDeposit = $account->valueAsOf($timestamp);

        DB::beginTransaction();
        $transaction = $this->factory->makeTransaction(
            $depositAmount,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            $timestamp
        );
        $transaction->save();

        $transactionData = $transaction->processPending();
        $api = $this->getPreviewData($transactionData);

        // Verify no matching transactions
        $this->assertEmpty(
            $transactionData['matches'] ?? [],
            "Simple deposit should have no matching contributions"
        );

        // Value change should equal deposit amount (with same share price)
        $valueChange = $api['value_today'] - $valueBeforeDeposit;
        $this->assertEqualsWithDelta(
            $depositAmount,
            $valueChange,
            1.00,
            "Simple deposit value change should equal deposit amount"
        );

        DB::rollBack();
    }

    /**
     * Test: Deposit with single 100% matching rule should double the value change.
     */
    public function test_deposit_with_single_matching_rule()
    {
        $this->factory->createFund(1000, 1000, $this->fundDate);
        $this->factory->createUser();

        // Create 100% matching rule up to $250
        $this->factory->createMatching(
            dollar_end: 250,
            match: 100,
            start: $this->fundDate
        );

        $account = $this->factory->userAccount;
        $depositAmount = 200;
        $timestamp = Carbon::parse($this->transactionDate);

        $valueBeforeDeposit = $account->valueAsOf($timestamp);

        DB::beginTransaction();
        $transaction = $this->factory->makeTransaction(
            $depositAmount,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            $timestamp
        );
        $transaction->save();

        $transactionData = $transaction->processPending();
        $api = $this->getPreviewData($transactionData);

        // Verify matching was created
        $matches = $transactionData['matches'] ?? [];
        $this->assertCount(1, $matches, "Should have exactly one matching transaction");

        // Verify matching amount equals deposit (100% match)
        // Note: matches now contain ['transaction' => $tran, 'rule' => $rule, 'remaining' => $amt]
        $matchTran = $matches[0]['transaction'] ?? $matches[0];
        $matchAmount = $matchTran->value ?? 0;
        $this->assertEquals(
            $depositAmount,
            $matchAmount,
            "100% match should equal deposit amount"
        );

        // Total value change should be deposit + matching = $400
        $valueChange = $api['value_today'] - $valueBeforeDeposit;
        $expectedChange = $depositAmount * 2; // 100% match doubles it
        $this->assertEqualsWithDelta(
            $expectedChange,
            $valueChange,
            1.00,
            "Total value change should be deposit + matching"
        );

        DB::rollBack();
    }

    /**
     * Test: Deposit with multiple matching rules - expiring first has priority.
     *
     * When multiple matching rules exist, the rule expiring soonest (by date_end)
     * applies first. Rules expiring later only apply if the earlier-expiring
     * rule doesn't fully cover the deposit amount.
     *
     * For a $200 deposit with two 100% rules:
     * - Expiring-first rule matches $200 (100% of deposit)
     * - Later-expiring rule gets $0 (no remaining value to match)
     * - Total matching = $200
     */
    public function test_deposit_with_multiple_matching_rules_expiring_first()
    {
        $this->factory->createFund(1000, 1000, $this->fundDate);
        $this->factory->createUser();

        // Create TWO matching rules - expiring soonest should have priority
        // Rule 1: expires 2022-12-31, 100% up to $250
        $this->factory->createMatchingRule(dollar_end: 250, match: 100, start: $this->fundDate, end: '2022-12-31');
        $this->factory->createAccountMatching();

        // Rule 2: expires 2023-12-31 (later), 100% up to $350
        $this->factory->createMatchingRule(dollar_end: 350, match: 100, start: $this->fundDate, end: '2023-12-31');
        $this->factory->createAccountMatching();

        $account = $this->factory->userAccount;
        $depositAmount = 200;
        $timestamp = Carbon::parse($this->transactionDate);

        $valueBeforeDeposit = $account->valueAsOf($timestamp);

        DB::beginTransaction();
        $transaction = $this->factory->makeTransaction(
            $depositAmount,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            $timestamp
        );
        $transaction->save();

        $transactionData = $transaction->processPending();
        $api = $this->getPreviewData($transactionData);

        $matches = $transactionData['matches'] ?? [];
        $matchCount = count($matches);

        $totalMatchAmount = 0;
        foreach ($matches as $match) {
            $matchTran = $match['transaction'] ?? $match;
            $totalMatchAmount += $matchTran->value ?? 0;
        }

        \Log::info("Multiple matching (expiring first): deposit=$depositAmount, matches=$matchCount, totalMatch=$totalMatchAmount");

        // Only ONE matching rule should apply (expiring-first rule covers full deposit)
        $this->assertEquals(1, $matchCount, "Only expiring-first matching rule should apply");
        $this->assertEquals(
            $depositAmount,  // Expiring-first rule matches 100% = $200
            $totalMatchAmount,
            "Total matching equals deposit (100% from expiring-first rule)"
        );

        // Total value change = deposit + matching = $200 + $200 = $400
        $valueChange = $api['value_today'] - $valueBeforeDeposit;
        $this->assertEqualsWithDelta(
            400,  // $200 deposit + $200 matching
            $valueChange,
            1.00,
            "Total value change is deposit + single matching"
        );

        DB::rollBack();
    }

    /**
     * Test: Matching should respect dollar cap.
     */
    public function test_matching_respects_dollar_cap()
    {
        $this->factory->createFund(1000, 1000, $this->fundDate);
        $this->factory->createUser();

        // Create 100% matching rule with $150 cap
        $this->factory->createMatching(dollar_end: 150, match: 100, start: $this->fundDate);

        $account = $this->factory->userAccount;
        $depositAmount = 200;
        $timestamp = Carbon::parse($this->transactionDate);

        DB::beginTransaction();
        $transaction = $this->factory->makeTransaction(
            $depositAmount,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            $timestamp
        );
        $transaction->save();

        $transactionData = $transaction->processPending();

        $matches = $transactionData['matches'] ?? [];
        $this->assertCount(1, $matches, "Should have one matching transaction");

        $matchTran = $matches[0]['transaction'] ?? $matches[0];
        $matchAmount = $matchTran->value ?? 0;
        $this->assertEquals(150, $matchAmount, "Match should be capped at dollar_end limit");

        DB::rollBack();
    }

    /**
     * Test: Withdrawal should decrease account value.
     */
    public function test_withdrawal_decreases_value()
    {
        $this->factory->createFund(1000, 1000, $this->fundDate);
        $this->factory->createUser();

        $account = $this->factory->userAccount;
        $timestamp = Carbon::parse($this->transactionDate);

        DB::beginTransaction();

        // First add shares
        $deposit = $this->factory->makeTransaction(
            500,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            $timestamp
        );
        $deposit->save();
        $deposit->processPending();

        $valueAfterDeposit = $account->valueAsOf($timestamp);

        // Withdraw (sale transactions require FLAGS_NO_MATCH)
        $withdrawalAmount = 200;
        $withdrawal = $this->factory->makeTransaction(
            -$withdrawalAmount,
            $account,
            TransactionExt::TYPE_SALE,
            TransactionExt::STATUS_PENDING,
            TransactionExt::FLAGS_NO_MATCH,
            $timestamp
        );
        $withdrawal->save();

        $transactionData = $withdrawal->processPending();
        $api = $this->getPreviewData($transactionData);

        $valueAfterWithdrawal = $api['value_today'];
        $actualChange = $valueAfterWithdrawal - $valueAfterDeposit;

        $this->assertEqualsWithDelta(
            -$withdrawalAmount,
            $actualChange,
            1.00,
            "Withdrawal should decrease account value"
        );

        DB::rollBack();
    }

    /**
     * Test: Projected value calculation is correct.
     */
    public function test_projected_value_calculation()
    {
        $this->factory->createFund(1000, 1000, $this->fundDate);
        $this->factory->createUser();

        $account = $this->factory->userAccount;
        $depositAmount = 100;
        $timestamp = Carbon::parse($this->transactionDate);

        DB::beginTransaction();
        $transaction = $this->factory->makeTransaction(
            $depositAmount,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            $timestamp
        );
        $transaction->save();

        $transactionData = $transaction->processPending();
        $api = $this->getPreviewData($transactionData);

        $this->assertArrayHasKey('share_value_today', $api);
        $this->assertArrayHasKey('shares_today', $api);
        $this->assertArrayHasKey('value_today', $api);

        // Value = shares * share_value
        $expectedValue = $api['shares_today'] * $api['share_value_today'];
        $this->assertEqualsWithDelta(
            $expectedValue,
            $api['value_today'],
            0.01,
            "Value should equal shares * share_value"
        );

        DB::rollBack();
    }

    /**
     * Test: Fund shares source calculation.
     */
    public function test_fund_shares_source_calculation()
    {
        $this->factory->createFund(1000, 1000, $this->fundDate);
        $this->factory->createUser();

        $account = $this->factory->userAccount;
        $depositAmount = 100;
        $timestamp = Carbon::parse($this->transactionDate);

        DB::beginTransaction();
        $transaction = $this->factory->makeTransaction(
            $depositAmount,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            $timestamp
        );
        $transaction->save();

        $transactionData = $transaction->processPending();
        $api = $this->getPreviewData($transactionData);

        $this->assertArrayHasKey('fundShares', $api);
        $fundShares = $api['fundShares'];

        // Fund loses shares when account receives deposit
        $this->assertLessThan(0, $fundShares['change']);
        $this->assertGreaterThan($fundShares['after'], $fundShares['before']);

        // Change equals negative of transaction shares
        $this->assertEqualsWithDelta(
            -$transaction->shares,
            $fundShares['change'],
            0.0001
        );

        DB::rollBack();
    }

    /**
     * Test: Available matching shows remaining capacity on applied rule.
     */
    public function test_available_matching_shows_remaining_on_applied_rule()
    {
        $this->factory->createFund(1000, 1000, $this->fundDate);
        $this->factory->createUser();

        // Create 100% matching rule with $350 cap
        $this->factory->createMatching(dollar_end: 350, match: 100, start: $this->fundDate);

        $account = $this->factory->userAccount;
        $depositAmount = 50;
        $timestamp = Carbon::parse($this->transactionDate);

        DB::beginTransaction();
        $transaction = $this->factory->makeTransaction(
            $depositAmount,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            $timestamp
        );
        $transaction->save();

        $transactionData = $transaction->processPending();
        $api = $this->getPreviewData($transactionData);

        // Verify matching was created
        $matches = $transactionData['matches'] ?? [];
        $this->assertCount(1, $matches, "Should have one matching transaction");

        // Verify availableMatching shows remaining capacity
        $this->assertArrayHasKey('availableMatching', $api);
        $this->assertCount(1, $api['availableMatching'], "Should show remaining on applied rule");

        $remaining = $api['availableMatching'][0]['remaining'] ?? 0;
        $this->assertEquals(300, $remaining, "Should have $300 remaining after $50 match on $350 cap");

        DB::rollBack();
    }

    /**
     * Test: Available matching shows capacity from multiple rules.
     */
    public function test_available_matching_shows_multiple_rules()
    {
        $this->factory->createFund(1000, 1000, $this->fundDate);
        $this->factory->createUser();

        // Rule 1: expires sooner (2026-12-31), 100% up to $100
        $this->factory->createMatchingRule(dollar_end: 100, match: 100, start: $this->fundDate, end: '2026-12-31');
        $this->factory->createAccountMatching();

        // Rule 2: expires later (2027-12-31), 100% up to $200
        $this->factory->createMatchingRule(dollar_end: 200, match: 100, start: $this->fundDate, end: '2027-12-31');
        $this->factory->createAccountMatching();

        $account = $this->factory->userAccount;
        $depositAmount = 50; // Only use $50 of the first $100 rule
        $timestamp = Carbon::parse($this->transactionDate);

        DB::beginTransaction();
        $transaction = $this->factory->makeTransaction(
            $depositAmount,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            $timestamp
        );
        $transaction->save();

        $transactionData = $transaction->processPending();
        $api = $this->getPreviewData($transactionData);

        // Verify matching was created from first rule only
        $matches = $transactionData['matches'] ?? [];
        $this->assertCount(1, $matches, "Should have one matching from expiring-first rule");

        // Verify availableMatching shows remaining from BOTH rules:
        // - First rule (expires 2026-12-31): $100 - $50 = $50 remaining
        // - Second rule (expires 2027-12-31): $200 remaining (not used, but still available)
        $this->assertArrayHasKey('availableMatching', $api);
        $this->assertCount(2, $api['availableMatching'], "Should show remaining from both rules");

        $totalRemaining = 0;
        foreach ($api['availableMatching'] as $avail) {
            $totalRemaining += $avail['remaining'];
        }
        $this->assertEquals(250, $totalRemaining, "Total remaining: $50 + $200 = $250");

        DB::rollBack();
    }
}
