<?php

namespace Tests\Unit;

use App\Models\GoalExt;
use Tests\TestCase;

/**
 * Unit tests for GoalExt model
 */
class GoalExtTest extends TestCase
{
    public function test_target_type_constants_defined()
    {
        $this->assertEquals('TOTAL', GoalExt::TARGET_TYPE_TOTAL);
        $this->assertEquals('4PCT', GoalExt::TARGET_TYPE_4PCT);
    }

    public function test_target_type_map_returns_array()
    {
        $result = GoalExt::targetTypeMap();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(GoalExt::TARGET_TYPE_TOTAL, $result);
        $this->assertArrayHasKey(GoalExt::TARGET_TYPE_4PCT, $result);
        $this->assertEquals('Total', $result[GoalExt::TARGET_TYPE_TOTAL]);
        $this->assertEquals('4%', $result[GoalExt::TARGET_TYPE_4PCT]);
    }
}
