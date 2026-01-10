<?php

namespace Tests\Unit;

use App\Models\FundReport;
use App\Models\FundReportExt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for FundReportExt model
 */
class FundReportExtTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
    }

    public function test_type_constants_defined()
    {
        $this->assertEquals('ADM', FundReportExt::TYPE_ADMIN);
        $this->assertEquals('ALL', FundReportExt::TYPE_ALL);
        $this->assertEquals('TRADING_BANDS', FundReportExt::TYPE_TRADING_BANDS);
    }

    public function test_type_map_contains_all_types()
    {
        $this->assertArrayHasKey(FundReportExt::TYPE_ADMIN, FundReportExt::$typeMap);
        $this->assertArrayHasKey(FundReportExt::TYPE_ALL, FundReportExt::$typeMap);
        $this->assertArrayHasKey(FundReportExt::TYPE_TRADING_BANDS, FundReportExt::$typeMap);
    }

    public function test_email_subjects_defined()
    {
        $this->assertArrayHasKey(FundReportExt::TYPE_ADMIN, FundReportExt::$emailSubjects);
        $this->assertArrayHasKey(FundReportExt::TYPE_ALL, FundReportExt::$emailSubjects);
        $this->assertArrayHasKey(FundReportExt::TYPE_TRADING_BANDS, FundReportExt::$emailSubjects);
    }

    public function test_email_subjects_values()
    {
        $this->assertEquals('Fund Admin Report', FundReportExt::$emailSubjects[FundReportExt::TYPE_ADMIN]);
        $this->assertEquals('Fund Report', FundReportExt::$emailSubjects[FundReportExt::TYPE_ALL]);
        $this->assertEquals('Trading Bands Report', FundReportExt::$emailSubjects[FundReportExt::TYPE_TRADING_BANDS]);
    }

    public function test_is_admin_returns_true_for_admin_type()
    {
        $fundReport = FundReport::factory()->create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ADMIN,
        ]);

        $fundReportExt = FundReportExt::find($fundReport->id);

        $this->assertTrue($fundReportExt->isAdmin());
    }

    public function test_is_admin_returns_false_for_non_admin_type()
    {
        $fundReport = FundReport::factory()->create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
        ]);

        $fundReportExt = FundReportExt::find($fundReport->id);

        $this->assertFalse($fundReportExt->isAdmin());
    }
}
