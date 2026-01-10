<?php

namespace Tests\Unit;

use App\Models\CashDepositExt;
use Tests\TestCase;

/**
 * Unit tests for CashDepositExt model
 */
class CashDepositExtTest extends TestCase
{
    public function test_status_constants_defined()
    {
        $this->assertEquals('PENDING', CashDepositExt::STATUS_PENDING);
        $this->assertEquals('DEPOSITED', CashDepositExt::STATUS_DEPOSITED);
        $this->assertEquals('ALLOCATED', CashDepositExt::STATUS_ALLOCATED);
        $this->assertEquals('COMPLETED', CashDepositExt::STATUS_COMPLETED);
        $this->assertEquals('CANCELLED', CashDepositExt::STATUS_CANCELLED);
    }

    public function test_status_map_returns_all_statuses()
    {
        $result = CashDepositExt::statusMap();

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertArrayHasKey(CashDepositExt::STATUS_PENDING, $result);
        $this->assertArrayHasKey(CashDepositExt::STATUS_DEPOSITED, $result);
        $this->assertArrayHasKey(CashDepositExt::STATUS_ALLOCATED, $result);
        $this->assertArrayHasKey(CashDepositExt::STATUS_COMPLETED, $result);
        $this->assertArrayHasKey(CashDepositExt::STATUS_CANCELLED, $result);
    }

    public function test_status_map_returns_labels()
    {
        $result = CashDepositExt::statusMap();

        $this->assertEquals('Pending', $result[CashDepositExt::STATUS_PENDING]);
        $this->assertEquals('Deposited', $result[CashDepositExt::STATUS_DEPOSITED]);
        $this->assertEquals('Allocated', $result[CashDepositExt::STATUS_ALLOCATED]);
        $this->assertEquals('Completed', $result[CashDepositExt::STATUS_COMPLETED]);
        $this->assertEquals('Cancelled', $result[CashDepositExt::STATUS_CANCELLED]);
    }
}
