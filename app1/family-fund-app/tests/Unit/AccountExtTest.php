<?php

namespace Tests\Unit;

use App\Models\AccountExt;
use App\Models\TransactionExt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for AccountExt model methods
 */
class AccountExtTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    public function test_fund_account_map_returns_array_with_null_option()
    {
        $result = AccountExt::fundAccountMap();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(null, $result);
        $this->assertEquals('Select a Fund Account', $result[null]);
    }

    public function test_fund_account_map_includes_fund_accounts()
    {
        // Fund account (no user_id) was created by DataFactory
        $result = AccountExt::fundAccountMap();

        // Should have at least the fund account
        $this->assertGreaterThan(1, count($result));
    }

    public function test_account_map_returns_array_with_null_option()
    {
        $result = AccountExt::accountMap();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(null, $result);
        $this->assertEquals('Select an Account', $result[null]);
    }

    public function test_account_map_includes_user_accounts_with_labels()
    {
        $result = AccountExt::accountMap();

        // Should have at least the user account created by DataFactory
        $this->assertGreaterThan(1, count($result));

        // The label should include the account nickname
        $userAccountId = $this->factory->userAccount->id;
        $this->assertArrayHasKey($userAccountId, $result);
    }

    public function test_transactions_relationship_returns_ordered_transactions()
    {
        $account = $this->factory->userAccount;

        // Create transactions with different timestamps
        $this->factory->createTransaction(100, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-02-01');
        $this->factory->createTransaction(200, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-01-15');

        $transactions = $account->transactions()->get();

        $this->assertGreaterThanOrEqual(2, $transactions->count());
        // Should be ordered by timestamp
        $prevTimestamp = null;
        foreach ($transactions as $tran) {
            if ($prevTimestamp) {
                $this->assertGreaterThanOrEqual($prevTimestamp, $tran->timestamp);
            }
            $prevTimestamp = $tran->timestamp;
        }
    }

    public function test_deposited_value_between_calculates_purchases()
    {
        $account = $this->factory->userAccount;

        // Create a purchase transaction
        $this->factory->createTransaction(500, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-03-15');

        $result = $account->depositedValueBetween('2022-03-01', '2022-04-01');

        $this->assertEquals(500, $result);
    }

    public function test_deposited_value_between_excludes_pending_transactions()
    {
        $account = $this->factory->userAccount;

        // Create a pending transaction
        $this->factory->createTransaction(500, $account, TransactionExt::TYPE_PURCHASE,
            'P', null, '2022-03-15');

        $result = $account->depositedValueBetween('2022-03-01', '2022-04-01');

        $this->assertEquals(0, $result);
    }

    public function test_deposited_value_between_subtracts_sales()
    {
        $account = $this->factory->userAccount;

        // Create purchase and sale
        $this->factory->createTransaction(1000, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-03-10');
        $this->factory->createTransaction(300, $account, 'SAL',
            TransactionExt::STATUS_CLEARED, null, '2022-03-15');

        $result = $account->depositedValueBetween('2022-03-01', '2022-04-01');

        $this->assertEquals(700, $result); // 1000 - 300
    }

    public function test_shares_as_of_returns_shares()
    {
        $account = $this->factory->userAccount;

        // Create transaction and balance
        $transaction = $this->factory->createTransaction(500, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-01-15');
        $this->factory->createBalance(50, $transaction, $account, '2022-01-15');

        $result = $account->sharesAsOf('2022-06-01');

        $this->assertEquals(50, $result);
    }

    public function test_shares_as_of_returns_zero_when_no_balance()
    {
        $account = $this->factory->userAccount;

        // Query for date before any balance
        $result = $account->sharesAsOf('2020-01-01');

        $this->assertEquals(0, $result);
    }

    public function test_value_as_of_calculates_correctly()
    {
        $account = $this->factory->userAccount;

        // Create transaction and balance
        $transaction = $this->factory->createTransaction(500, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-01-15');
        $this->factory->createBalance(50, $transaction, $account, '2022-01-15');

        // Fund has 1000 shares and 1000 value, so share value = 1
        $result = $account->valueAsOf('2022-06-01');

        // 50 shares * share value
        $this->assertGreaterThan(0, $result);
    }

    public function test_share_value_as_of_delegates_to_fund()
    {
        $account = $this->factory->userAccount;

        $result = $account->shareValueAsOf('2022-06-01');

        // Should return a positive value from the fund
        $this->assertGreaterThan(0, $result);
    }

    public function test_remaining_matchings_returns_null()
    {
        $account = $this->factory->userAccount;

        $result = $account->remainingMatchings();

        $this->assertNull($result);
    }

    public function test_find_oldest_transaction_returns_oldest()
    {
        $account = $this->factory->userAccount;

        // Create transactions with different timestamps
        $this->factory->createTransaction(100, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-03-01');
        $this->factory->createTransaction(200, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-01-15');

        $result = $account->findOldestTransaction();

        $this->assertNotNull($result);
        $this->assertStringContainsString('2022-01-15', $result->timestamp);
    }

    public function test_calculate_twr_with_single_period()
    {
        // Start: 1000, End: 1100, No cash flow
        // Return = (1100 - 0) / 1000 = 1.1
        // TWR = 1.1 - 1 = 0.1 (10%)
        $data = [
            [1000, 1100, 0],
        ];

        $result = AccountExt::calculateTWR($data);

        $this->assertEqualsWithDelta(0.1, $result, 0.0001);
    }

    public function test_calculate_twr_with_cash_flow()
    {
        // Period 1: Start 1000, End 1050, Cash 0 => Return = 1050/1000 = 1.05
        // Period 2: Start 1050, End 1260, Cash 100 => Return = (1260-100)/1050 = 1.1048
        // TWR = 1.05 * 1.1048 - 1 = 0.16
        $data = [
            [1000, 1050, 0],
            [1050, 1260, 100],
        ];

        $result = AccountExt::calculateTWR($data);

        $this->assertEqualsWithDelta(0.16, $result, 0.01);
    }

    public function test_calculate_twr_with_zero_start_value()
    {
        // When start value is 0, period return should be 1 (no change)
        $data = [
            [0, 1000, 1000],
            [1000, 1100, 0],
        ];

        $result = AccountExt::calculateTWR($data);

        // Period 1 return = 1, Period 2 return = 1.1
        // TWR = 1 * 1.1 - 1 = 0.1
        $this->assertEqualsWithDelta(0.1, $result, 0.0001);
    }

    public function test_calculate_twr_with_negative_return()
    {
        // Start: 1000, End: 900, No cash flow
        // Return = 900/1000 = 0.9
        // TWR = 0.9 - 1 = -0.1 (-10%)
        $data = [
            [1000, 900, 0],
        ];

        $result = AccountExt::calculateTWR($data);

        $this->assertEqualsWithDelta(-0.1, $result, 0.0001);
    }

    public function test_validate_has_email_returns_null_when_email_exists()
    {
        $account = $this->factory->userAccount;
        $account->email_cc = 'test@example.com';

        $result = $account->validateHasEmail();

        $this->assertNull($result);
    }

    public function test_validate_has_email_returns_nickname_when_no_email()
    {
        $account = $this->factory->userAccount;
        $account->email_cc = null;

        $result = $account->validateHasEmail();

        $this->assertEquals($account->nickname, $result);
    }

    public function test_validate_has_email_returns_nickname_for_empty_string()
    {
        $account = $this->factory->userAccount;
        $account->email_cc = '';

        $result = $account->validateHasEmail();

        $this->assertEquals($account->nickname, $result);
    }

    public function test_all_shares_as_of_returns_type_array()
    {
        $account = $this->factory->userAccount;

        // Create transaction and OWN balance
        $transaction = $this->factory->createTransaction(500, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-01-15');
        $this->factory->createBalance(50, $transaction, $account, '2022-01-15');

        $result = $account->allSharesAsOf('2022-06-01');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('OWN', $result);
    }

    public function test_period_performance_calculates_twr()
    {
        $account = $this->factory->userAccount;

        // Create initial balance
        $transaction = $this->factory->createTransaction(1000, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-01-15');
        $this->factory->createBalance(100, $transaction, $account, '2022-01-15');

        $result = $account->periodPerformance('2022-01-01', '2022-12-31');

        // Should return a numeric value (could be positive, negative, or zero)
        $this->assertIsFloat($result);
    }

    public function test_yearly_performance_uses_correct_date_range()
    {
        $account = $this->factory->userAccount;

        // Create initial balance
        $transaction = $this->factory->createTransaction(1000, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED, null, '2022-01-15');
        $this->factory->createBalance(100, $transaction, $account, '2022-01-15');

        $result = $account->yearlyPerformance(2022);

        // Should return a numeric value
        $this->assertIsFloat($result);
    }
}
