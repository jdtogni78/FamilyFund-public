<?php

namespace Tests\Unit;

use App\Models\TransactionExt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for TransactionExt model
 */
class TransactionExtTest extends TestCase
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

    public function test_type_constants_are_defined()
    {
        $this->assertEquals('PUR', TransactionExt::TYPE_PURCHASE);
        $this->assertEquals('INI', TransactionExt::TYPE_INITIAL);
        $this->assertEquals('SAL', TransactionExt::TYPE_SALE);
        $this->assertEquals('MAT', TransactionExt::TYPE_MATCHING);
        $this->assertEquals('BOR', TransactionExt::TYPE_BORROW);
        $this->assertEquals('REP', TransactionExt::TYPE_REPAY);
    }

    public function test_status_constants_are_defined()
    {
        $this->assertEquals('P', TransactionExt::STATUS_PENDING);
        $this->assertEquals('C', TransactionExt::STATUS_CLEARED);
        $this->assertEquals('S', TransactionExt::STATUS_SCHEDULED);
    }

    public function test_flags_constants_are_defined()
    {
        $this->assertEquals('A', TransactionExt::FLAGS_ADD_CASH);
        $this->assertEquals('C', TransactionExt::FLAGS_CASH_ADDED);
        $this->assertEquals('U', TransactionExt::FLAGS_NO_MATCH);
    }

    public function test_type_map_contains_all_types()
    {
        $this->assertArrayHasKey(TransactionExt::TYPE_PURCHASE, TransactionExt::$typeMap);
        $this->assertArrayHasKey(TransactionExt::TYPE_INITIAL, TransactionExt::$typeMap);
        $this->assertArrayHasKey(TransactionExt::TYPE_SALE, TransactionExt::$typeMap);
        $this->assertArrayHasKey(TransactionExt::TYPE_MATCHING, TransactionExt::$typeMap);
        $this->assertArrayHasKey(TransactionExt::TYPE_BORROW, TransactionExt::$typeMap);
        $this->assertArrayHasKey(TransactionExt::TYPE_REPAY, TransactionExt::$typeMap);
    }

    public function test_status_map_contains_all_statuses()
    {
        $this->assertArrayHasKey(TransactionExt::STATUS_PENDING, TransactionExt::$statusMap);
        $this->assertArrayHasKey(TransactionExt::STATUS_CLEARED, TransactionExt::$statusMap);
        $this->assertArrayHasKey(TransactionExt::STATUS_SCHEDULED, TransactionExt::$statusMap);
    }

    public function test_status_string_returns_label()
    {
        $transaction = $this->factory->createTransaction(
            500,
            $this->factory->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED
        );

        $this->assertEquals('Cleared', $transaction->status_string());
    }

    public function test_type_string_returns_label()
    {
        $transaction = $this->factory->createTransaction(
            500,
            $this->factory->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED
        );

        $this->assertEquals('Purchase', $transaction->type_string());
    }

    public function test_flags_map_contains_null_option()
    {
        $this->assertArrayHasKey(null, TransactionExt::$flagsMap);
        $this->assertEquals('No Flags', TransactionExt::$flagsMap[null]);
    }

    public function test_create_balance_throws_exception_for_zero_shares()
    {
        $transaction = $this->factory->createTransaction(
            500,
            $this->factory->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Balances need transactions with shares');

        $transaction->createBalance(0, '2022-01-15');
    }

    public function test_create_balance_throws_exception_for_null_shares()
    {
        $transaction = $this->factory->createTransaction(
            500,
            $this->factory->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Balances need transactions with shares');

        $transaction->createBalance(null, '2022-01-15');
    }

    public function test_create_balance_throws_exception_when_balance_exists()
    {
        $transaction = $this->factory->createTransaction(
            500,
            $this->factory->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED
        );

        // Create first balance
        $this->factory->createBalance(50, $transaction, $this->factory->userAccount, '2022-01-15');

        // Try to create second balance
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transaction already has a balance associated');

        $transaction->createBalance(50, '2022-01-15');
    }

    public function test_pending_status_type_string()
    {
        $transaction = $this->factory->createTransaction(
            500,
            $this->factory->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING
        );

        $this->assertEquals('Pending', $transaction->status_string());
    }

    public function test_scheduled_status_type_string()
    {
        $transaction = $this->factory->createTransaction(
            500,
            $this->factory->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_SCHEDULED
        );

        $this->assertEquals('Scheduled', $transaction->status_string());
    }

    public function test_sale_type_string()
    {
        $transaction = $this->factory->createTransaction(
            500,
            $this->factory->userAccount,
            TransactionExt::TYPE_SALE,
            TransactionExt::STATUS_CLEARED
        );

        $this->assertEquals('Sale', $transaction->type_string());
    }
}
