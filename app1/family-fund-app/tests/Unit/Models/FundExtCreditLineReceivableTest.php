<?php

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\FundExt;
use App\Services\CreditLine\Reporting\FundReceivableCalculator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Issue #6 — FundExt::creditLineReceivableValueAsOf used to swallow every
 * \Throwable and return 0, hiding real bugs (bad as-of date, missing
 * relationship, schema drift) behind a zero receivable on the fund show page.
 *
 * The fix narrows the swallow to production only and logs in all environments,
 * so non-prod callers (tests, dev, staging) see the real exception.
 */
class FundExtCreditLineReceivableTest extends TestCase
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
    }

    public function test_propagates_calculator_failure_in_non_production(): void
    {
        $this->app->bind(FundReceivableCalculator::class, function () {
            return new class extends FundReceivableCalculator {
                public function receivableValue($fund, $asOf = null): float
                {
                    throw new RuntimeException('simulated calculator failure');
                }
            };
        });

        $fund = FundExt::find($this->factory->fund->id);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('simulated calculator failure');

        $fund->creditLineReceivableValueAsOf('2026-05-01');
    }

    public function test_returns_zero_and_swallows_in_production(): void
    {
        $this->app->bind(FundReceivableCalculator::class, function () {
            return new class extends FundReceivableCalculator {
                public function receivableValue($fund, $asOf = null): float
                {
                    throw new RuntimeException('simulated calculator failure');
                }
            };
        });

        $this->app->detectEnvironment(fn () => 'production');

        $fund = FundExt::find($this->factory->fund->id);
        $this->assertSame(0.0, $fund->creditLineReceivableValueAsOf('2026-05-01'));
    }
}
