<?php

namespace Tests\Unit\Services;

use App\Models\AccountBalance;
use App\Models\AccountExt;
use App\Models\GoalExt;
use App\Services\GoalCalculationService;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

class GoalCalculationServiceTest extends TestCase
{
    private GoalCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GoalCalculationService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeBalance(string $type, float $shares): AccountBalance
    {
        $b = new AccountBalance();
        $b->type = $type;
        $b->shares = $shares;
        return $b;
    }

    private function makeGoal(
        string $type,
        float $targetAmount,
        float $targetPct,
        string $start,
        string $end
    ): GoalExt {
        $goal = new GoalExt();
        $goal->target_type = $type;
        $goal->target_amount = $targetAmount;
        $goal->target_pct = $targetPct;
        $goal->start_dt = Carbon::parse($start);
        $goal->end_dt = Carbon::parse($end);
        return $goal;
    }

    private function makeAccount(
        float $shareValue,
        float $ownShares,
        float $borShares,
        float $valueAtGoalStart
    ): AccountExt {
        $account = Mockery::mock(AccountExt::class)->makePartial();
        $account->shouldReceive('shareValueAsOf')->andReturn($shareValue);
        $balances = ['OWN' => $this->makeBalance('OWN', $ownShares)];
        if ($borShares > 0) {
            $balances['BOR'] = $this->makeBalance('BOR', $borShares);
        }
        $account->shouldReceive('allSharesAsOf')->andReturn($balances);
        $account->shouldReceive('valueAsOf')->andReturn($valueAtGoalStart);
        return $account;
    }

    public function test_no_borrowing_current_equals_current_gross(): void
    {
        $account = $this->makeAccount(1.0, 1000, 0, 0);
        $goal = $this->makeGoal(GoalExt::TARGET_TYPE_TOTAL, 10000, 0.04, '2024-01-01', '2034-01-01');

        $result = $this->service->progressFor($account, $goal, '2025-01-01');

        $this->assertEquals(1000.0, $result['current']['value']);
        $this->assertEquals(1000.0, $result['current_gross']['value']);
        $this->assertEquals(0.0, $result['borrowed_value']);
        $this->assertEquals(0.0, $result['borrowed_shares']);
    }

    public function test_borrowing_makes_current_net_and_keeps_gross_separate(): void
    {
        // 1000 OWN @ $1 = $1000 gross; 200 BOR = $200 debt; net = $800
        $account = $this->makeAccount(1.0, 1000, 200, 0);
        $goal = $this->makeGoal(GoalExt::TARGET_TYPE_TOTAL, 10000, 0.04, '2024-01-01', '2034-01-01');

        $result = $this->service->progressFor($account, $goal, '2025-01-01');

        // current is net — borrowed shares aren't held
        $this->assertEquals(800.0, $result['current']['value']);
        // gross still exposed for legacy callers
        $this->assertEquals(1000.0, $result['current_gross']['value']);
        $this->assertEquals(200.0, $result['borrowed_value']);
        $this->assertEquals(200.0, $result['borrowed_shares']);
    }

    public function test_borrowing_lowers_completed_pct_on_current(): void
    {
        $account = $this->makeAccount(1.0, 1000, 200, 0);
        $goal = $this->makeGoal(GoalExt::TARGET_TYPE_TOTAL, 10000, 0.04, '2024-01-01', '2034-01-01');

        $result = $this->service->progressFor($account, $goal, '2025-01-01');

        // (800-0)/(10000-0) * 100 = 8% — net is the canonical figure
        $this->assertEquals(8.0, $result['current']['completed_pct']);
        // gross stays at 10% for the legacy view
        $this->assertEquals(10.0, $result['current_gross']['completed_pct']);
    }

    public function test_4pct_target_type_uses_yield_to_target_value(): void
    {
        // target_amount = $400 annual yield at 4% → underlying target = $10,000
        $account = $this->makeAccount(1.0, 5000, 0, 0);
        $goal = $this->makeGoal(GoalExt::TARGET_TYPE_4PCT, 400, 0.04, '2024-01-01', '2034-01-01');

        $result = $this->service->progressFor($account, $goal, '2025-01-01');

        $this->assertEquals(10000.0, $result['current']['final_value']);
        $this->assertEquals(400.0, $result['current']['final_value_4pct']);
        $this->assertEquals(50.0, $result['current']['completed_pct']);
    }

    public function test_completed_pct_caps_at_100(): void
    {
        $account = $this->makeAccount(1.0, 15000, 0, 0);
        $goal = $this->makeGoal(GoalExt::TARGET_TYPE_TOTAL, 10000, 0.04, '2024-01-01', '2034-01-01');

        $result = $this->service->progressFor($account, $goal, '2025-01-01');

        $this->assertEquals(100.0, $result['current']['completed_pct']);
    }

    public function test_borrowing_against_4pct_goal_reduces_current_yield(): void
    {
        // Net shares (1000-200)=800 → current value $800 → yield $32 at 4%
        $account = $this->makeAccount(1.0, 1000, 200, 0);
        $goal = $this->makeGoal(GoalExt::TARGET_TYPE_4PCT, 400, 0.04, '2024-01-01', '2034-01-01');

        $result = $this->service->progressFor($account, $goal, '2025-01-01');

        // current yield = net × 4% = $32
        $this->assertEquals(32.0, $result['current']['value_4pct']);
        // gross yield = 1000 × 4% = $40
        $this->assertEquals(40.0, $result['current_gross']['value_4pct']);
    }

    public function test_returns_expected_array_shape(): void
    {
        $account = $this->makeAccount(1.0, 1000, 100, 0);
        $goal = $this->makeGoal(GoalExt::TARGET_TYPE_TOTAL, 10000, 0.04, '2024-01-01', '2034-01-01');

        $result = $this->service->progressFor($account, $goal, '2025-01-01');

        foreach (['period', 'start_value', 'current', 'current_gross', 'expected',
                  'borrowed_value', 'borrowed_shares'] as $k) {
            $this->assertArrayHasKey($k, $result, "missing key $k");
        }
        foreach (['start_value', 'current', 'current_gross', 'expected'] as $k) {
            foreach (['value', 'value_4pct', 'final_value', 'final_value_4pct', 'completed_pct'] as $sub) {
                $this->assertArrayHasKey($sub, $result[$k], "missing $k.$sub");
            }
        }
        $this->assertCount(3, $result['period']);
    }
}
