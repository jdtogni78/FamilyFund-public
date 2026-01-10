<?php

namespace Tests\Unit;

use App\Models\MatchingRule;
use App\Models\MatchingRuleExt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Unit tests for MatchingRuleExt model
 */
class MatchingRuleExtTest extends TestCase
{
    use DatabaseTransactions;

    public function test_rule_map_returns_array_with_select_option()
    {
        $result = MatchingRuleExt::ruleMap();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(null, $result);
        $this->assertEquals('Please Select Rule', $result[null]);
    }

    public function test_rule_map_includes_matching_rules()
    {
        // Create a matching rule
        $rule = MatchingRule::factory()->create([
            'dollar_range_start' => 0,
            'dollar_range_end' => 10000,
            'date_start' => '2022-01-01',
            'date_end' => '2025-12-31',
            'match_percent' => 100,
        ]);

        $result = MatchingRuleExt::ruleMap();

        $this->assertArrayHasKey($rule->id, $result);
        // Label should contain dollar range
        $this->assertStringContainsString('$0', $result[$rule->id]);
        $this->assertStringContainsString('$10000', $result[$rule->id]);
        // Label should contain date range
        $this->assertStringContainsString('2022-01-01', $result[$rule->id]);
        // Label should contain match percent (may be formatted as 100.00%)
        $this->assertStringContainsString('100', $result[$rule->id]);
    }
}
