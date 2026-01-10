<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\PerformanceTrait;
use App\Models\AssetExt;
use App\Models\Utils;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for PerformanceTrait methods
 */
class PerformanceTraitTest extends TestCase
{
    use DatabaseTransactions;

    private $traitObject;
    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous class that uses the trait
        $this->traitObject = new class {
            use PerformanceTrait;
            public $verbose = false;

            // Expose protected methods for testing
            public function testRemoveEmptyStart(array $arr): array
            {
                return $this->removeEmptyStart($arr);
            }

            public function testAddValueChangeToArray(array $arr): array
            {
                return $this->addValueChangeToArray($arr);
            }
        };

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    public function test_set_perf_object()
    {
        $account = $this->factory->userAccount;
        $this->traitObject->setPerfObject($account);

        // Should not throw exception
        $this->assertTrue(true);
    }

    public function test_remove_empty_start_removes_leading_zeros()
    {
        $arr = [
            '2022-01-01' => ['value' => 0],
            '2022-02-01' => ['value' => 0],
            '2022-03-01' => ['value' => 100],
            '2022-04-01' => ['value' => 200],
        ];

        $result = $this->traitObject->testRemoveEmptyStart($arr);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('2022-03-01', $result);
        $this->assertArrayHasKey('2022-04-01', $result);
    }

    public function test_remove_empty_start_keeps_non_zero_values()
    {
        $arr = [
            '2022-01-01' => ['value' => 100],
            '2022-02-01' => ['value' => 0],
            '2022-03-01' => ['value' => 200],
        ];

        $result = $this->traitObject->testRemoveEmptyStart($arr);

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('2022-01-01', $result);
    }

    public function test_remove_empty_start_handles_empty_array()
    {
        $result = $this->traitObject->testRemoveEmptyStart([]);
        $this->assertEquals([], $result);
    }

    public function test_add_value_change_to_array_calculates_percentage()
    {
        $arr = [
            '2022-01-01' => ['value' => '$100.00'],
            '2022-02-01' => ['value' => '$150.00'],
            '2022-03-01' => ['value' => '$200.00'],
        ];

        $result = $this->traitObject->testAddValueChangeToArray($arr);

        // First entry has no previous value
        $this->assertEquals(0, $result['2022-01-01']['value_change']);
        // Second entry: (150-100)/100 = 50%
        // Utils::percent returns formatted string
        $this->assertStringContainsString('50', (string) $result['2022-02-01']['value_change']);
        // Third entry: (200-150)/150 = 33.33%
        $this->assertStringContainsString('33', (string) $result['2022-03-01']['value_change']);
    }

    public function test_add_value_change_to_array_handles_empty_array()
    {
        $result = $this->traitObject->testAddValueChangeToArray([]);
        $this->assertEquals([], $result);
    }

    public function test_prep_cash_accumulates_values()
    {
        $trans = [
            ['timestamp' => '2022-01-15', 'value' => 1000],
            ['timestamp' => '2022-02-15', 'value' => 500],
            ['timestamp' => '2022-03-15', 'value' => -200],
        ];

        $result = $this->traitObject->prepCash('2022-12-31', $trans);

        $this->assertArrayHasKey('2022-01-15', $result);
        $this->assertEquals(1000, $result['2022-01-15']);
        $this->assertArrayHasKey('2022-02-15', $result);
        $this->assertEquals(1500, $result['2022-02-15']);
        $this->assertArrayHasKey('2022-03-15', $result);
        $this->assertEquals(1300, $result['2022-03-15']);
    }

    public function test_prep_cash_respects_as_of_date()
    {
        $trans = [
            ['timestamp' => '2022-01-15', 'value' => 1000],
            ['timestamp' => '2022-06-15', 'value' => 500],
        ];

        $result = $this->traitObject->prepCash('2022-03-01', $trans);

        $this->assertArrayHasKey('2022-01-15', $result);
        $this->assertArrayNotHasKey('2022-06-15', $result);
    }

    public function test_create_cash_performance_array()
    {
        $cash = [
            '2022-01-01' => 1000,
            '2022-02-01' => 1500,
            '2022-03-01' => 2000,
        ];

        $result = $this->traitObject->createCashPeformanceArray('2022-01-01', '2022-02-15', $cash, null);

        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('price', $result);
        // Should find latest value before 2022-02-15
        $this->assertEquals(Utils::currency(1500), $result['value']);
    }

    public function test_create_linear_regression_response_with_insufficient_data()
    {
        $monthly_performance = [
            '2022-01-01' => ['value' => 10000],
        ];

        $result = $this->traitObject->createLinearRegressionResponse($monthly_performance, '2022-06-01');

        $this->assertEquals(0, $result['m']);
        $this->assertEquals(0, $result['intercept']);
        $this->assertEmpty($result['predictions']);
    }

    public function test_create_linear_regression_response_with_data()
    {
        $monthly_performance = [
            '2022-01-01' => ['value' => 10000],
            '2022-02-01' => ['value' => 10500],
            '2022-03-01' => ['value' => 11000],
            '2022-04-01' => ['value' => 11500],
            '2022-05-01' => ['value' => 12000],
        ];

        $result = $this->traitObject->createLinearRegressionResponse($monthly_performance, '2022-06-01', 12500);

        $this->assertArrayHasKey('m', $result);
        $this->assertArrayHasKey('intercept', $result);
        $this->assertArrayHasKey('predictions', $result);
        $this->assertCount(10, $result['predictions']); // 10 years of predictions

        // Should have comparison data
        $this->assertArrayHasKey('comparison', $result);
        $this->assertArrayHasKey('starting', $result['comparison']);
        $this->assertArrayHasKey('expected', $result['comparison']);
        $this->assertArrayHasKey('current', $result['comparison']);
    }

    public function test_create_performance_array()
    {
        $account = $this->factory->userAccount;

        // Create transaction and balance for the account
        $transaction = $this->factory->createTransaction(500, $account);
        $this->factory->createBalance(50, $transaction, $account, '2022-01-01');

        $this->traitObject->setPerfObject($account);

        $result = $this->traitObject->createPerformanceArray('2022-01-01', '2022-06-01');

        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('shares', $result);
        $this->assertArrayHasKey('share_value', $result);
        $this->assertArrayHasKey('performance', $result);
    }
}
