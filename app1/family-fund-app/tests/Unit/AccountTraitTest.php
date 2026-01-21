<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\AccountTrait;
use App\Models\AccountExt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for AccountTrait methods
 */
class AccountTraitTest extends TestCase
{
    use DatabaseTransactions;

    private $traitObject;
    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous class that uses the trait
        $this->traitObject = new class {
            use AccountTrait;
            public $verbose = false;
            // Note: $perfObject is defined in PerformanceTrait (used by AccountTrait)
            public $err = [];
            public $msgs = [];

            // Expose protected methods for testing
            public function testGetGoalPct($value, $start, $target, $pct)
            {
                return $this->getGoalPct($value, $start, $target, $pct);
            }

            public function testGetTotalAvailableMatching($arr)
            {
                return $this->getTotalAvailableMatching($arr);
            }

            public function testCreateDisbursableResponse($arr, $asOf)
            {
                return $this->createDisbursableResponse($arr, $asOf);
            }

            // Setter for protected perfObject
            public function setPerfObject($obj)
            {
                $this->perfObject = $obj;
            }
        };

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    public function test_create_account_array_returns_correct_structure()
    {
        $account = $this->factory->userAccount;

        $result = $this->traitObject->createAccountArray($account);

        $this->assertArrayHasKey('nickname', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals($account->id, $result['id']);
        $this->assertEquals($account->nickname, $result['nickname']);
    }

    public function test_get_goal_pct_calculates_correctly()
    {
        // Start: $0, Target: $10000, Current value: $5000, pct: 0.04
        $result = $this->traitObject->testGetGoalPct(5000, 0, 10000, 0.04);

        $this->assertEquals(5000, $result['value']);
        $this->assertEquals(200, $result['value_4pct']); // 5000 * 0.04
        $this->assertEquals(10000, $result['final_value']);
        $this->assertEquals(400, $result['final_value_4pct']); // 10000 * 0.04
        $this->assertEquals(50, $result['completed_pct']); // (5000-0)/(10000-0) * 100
    }

    public function test_get_goal_pct_caps_at_100_percent()
    {
        // Value exceeds target
        $result = $this->traitObject->testGetGoalPct(15000, 0, 10000, 0.04);

        $this->assertEquals(100, $result['completed_pct']); // Capped at 100
    }

    public function test_get_goal_pct_handles_partial_progress()
    {
        // Start: $1000, Target: $5000, Current value: $2000
        $result = $this->traitObject->testGetGoalPct(2000, 1000, 5000, 0.04);

        // (2000-1000)/(5000-1000) * 100 = 1000/4000 * 100 = 25%
        $this->assertEquals(25, $result['completed_pct']);
    }

    public function test_get_total_available_matching_sums_correctly()
    {
        $matchingRules = [
            ['available' => 100],
            ['available' => 200],
            ['available' => 50],
        ];

        $result = $this->traitObject->testGetTotalAvailableMatching($matchingRules);

        $this->assertEquals(350, $result);
    }

    public function test_get_total_available_matching_returns_zero_for_empty()
    {
        $result = $this->traitObject->testGetTotalAvailableMatching([]);
        $this->assertEquals(0, $result);
    }

    public function test_create_disbursable_response_structure()
    {
        // Set up the trait's perfObject (account)
        $account = $this->factory->userAccount;
        $account->disbursement_cap = 0.02;
        $this->traitObject->setPerfObject($account);

        // Method looks at previous year: for asOf='2022-06-01', it checks '2021-01-01 to 2022-01-01'
        $asOf = '2022-06-01';

        // Create account object with balances structure
        $accountData = new \stdClass();
        $accountData->balances = ['OWN' => (object)['market_value' => 10000]];

        $arr = [
            'account' => $accountData,
            'yearly_performance' => [
                '2021-01-01 to 2022-01-01' => [
                    'value' => 10000,
                    'performance' => 10, // 10% return (stored as percentage)
                ],
            ],
        ];

        $result = $this->traitObject->testCreateDisbursableResponse($arr, $asOf);

        $this->assertArrayHasKey('year', $result);
        $this->assertArrayHasKey('performance', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertEquals('2021-01-01', $result['year']);
        $this->assertEquals(2, $result['limit']); // 0.02 * 100
    }

    public function test_create_disbursable_response_caps_at_disbursement_cap()
    {
        $account = $this->factory->userAccount;
        $account->disbursement_cap = 0.02; // 2% cap
        $this->traitObject->setPerfObject($account);

        // Method looks at previous year: for asOf='2022-06-01', it checks '2021-01-01 to 2022-01-01'
        $asOf = '2022-06-01';

        // Create account object with balances structure
        $accountData = new \stdClass();
        $accountData->balances = ['OWN' => (object)['market_value' => 10000]];

        $arr = [
            'account' => $accountData,
            'yearly_performance' => [
                '2021-01-01 to 2022-01-01' => [
                    'value' => 10000,
                    'performance' => 10, // 10% return, but cap is 2% (stored as percentage)
                ],
            ],
        ];

        $result = $this->traitObject->testCreateDisbursableResponse($arr, $asOf);

        // Should be capped: 10000 * 0.02 = 200
        $this->assertEquals(200, $result['value']);
    }

    public function test_create_disbursable_response_handles_negative_performance()
    {
        $account = $this->factory->userAccount;
        $account->disbursement_cap = 0.02;
        $this->traitObject->setPerfObject($account);

        // Method looks at previous year: for asOf='2022-06-01', it checks '2021-01-01 to 2022-01-01'
        $asOf = '2022-06-01';

        // Create account object with balances structure
        $accountData = new \stdClass();
        $accountData->balances = ['OWN' => (object)['market_value' => 10000]];

        $arr = [
            'account' => $accountData,
            'yearly_performance' => [
                '2021-01-01 to 2022-01-01' => [
                    'value' => 10000,
                    'performance' => -5, // -5% return (stored as percentage)
                ],
            ],
        ];

        $result = $this->traitObject->testCreateDisbursableResponse($arr, $asOf);

        // Negative performance should result in 0 disbursable (max(0, ...))
        $this->assertEquals(0, $result['value']);
    }

    public function test_create_disbursable_response_uses_default_cap()
    {
        $account = $this->factory->userAccount;
        $account->disbursement_cap = null; // No cap set
        $this->traitObject->setPerfObject($account);

        // Method looks at previous year: for asOf='2022-06-01', it checks '2021-01-01 to 2022-01-01'
        $asOf = '2022-06-01';

        // Create account object with balances structure
        $accountData = new \stdClass();
        $accountData->balances = ['OWN' => (object)['market_value' => 10000]];

        $arr = [
            'account' => $accountData,
            'yearly_performance' => [
                '2021-01-01 to 2022-01-01' => [
                    'value' => 10000,
                    'performance' => 10, // 10% return (stored as percentage)
                ],
            ],
        ];

        $result = $this->traitObject->testCreateDisbursableResponse($arr, $asOf);

        // Default cap is 0.02 (2%)
        $this->assertEquals(2, $result['limit']);
        $this->assertEquals(200, $result['value']); // 10000 * 0.02
    }

    public function test_create_account_response_adds_market_value()
    {
        $account = $this->factory->userAccount;

        // Create a transaction and balance for the user
        $transaction = $this->factory->createTransaction(500, $account);
        $this->factory->createBalance(50, $transaction, $account, '2022-01-01');

        $asOf = '2022-06-01';
        $result = $this->traitObject->createAccountResponse($account, $asOf);

        $this->assertInstanceOf(AccountExt::class, $result);
        $this->assertNotNull($result->balances);
    }

    public function test_create_transactions_response()
    {
        $account = $this->factory->userAccount;

        // Create a transaction
        $this->factory->createTransaction(500, $account, \App\Models\TransactionExt::TYPE_PURCHASE,
            \App\Models\TransactionExt::STATUS_CLEARED, null, '2022-01-15');

        $asOf = '2022-06-01';
        $result = $this->traitObject->createTransactionsResponse($account, $asOf);

        $this->assertIsArray($result);
    }
}
