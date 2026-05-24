<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\PortfolioAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for PortfolioAssetControllerExt
 * Target: Get coverage from 7% to 50%+
 */
class PortfolioAssetControllerExtTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $user;
    protected Asset $assetA;
    protected Asset $assetB;
    protected PortfolioAsset $portfolioAssetA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();
        $this->user = $this->df->user;

        $this->assetA = $this->df->createAssetWithPrice();
        $this->portfolioAssetA = $this->df->portfolioAsset;
        $this->assetB = $this->df->createAssetWithPrice();

        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->user->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Index Tests ====================

    public function test_index_displays_portfolio_assets()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets');

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAssets');
        $response->assertViewHas('api');
    }

    public function test_index_filters_by_fund()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets?fund_id=' . $this->df->fund->id);

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAssets');
    }

    public function test_index_filters_by_asset()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets?asset_id=' . $this->assetA->id);

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAssets');
    }

    public function test_index_filters_by_date_range()
    {
        $startDt = '2022-01-01';
        $endDt = '2023-12-31';

        $response = $this->actingAs($this->user)->get("/portfolioAssets?start_dt={$startDt}&end_dt={$endDt}");

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAssets');
    }

    public function test_index_handles_multiple_asset_filter()
    {
        $response = $this->actingAs($this->user)
            ->get('/portfolioAssets?asset_id[]=' . $this->assetA->id . '&asset_id[]=' . $this->assetB->id);

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAssets');
    }

    public function test_index_sorts_by_fund()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets?sort=fund&dir=asc');

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAssets');
    }

    public function test_index_sorts_by_asset()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets?sort=asset&dir=desc');

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAssets');
    }

    public function test_index_sorts_by_position()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets?sort=position&dir=desc');

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAssets');
    }

    public function test_index_sorts_by_end_dt()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets?sort=end_dt&dir=asc');

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAssets');
    }

    public function test_index_with_fund_filter_shows_chart_data()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets?fund_id=' . $this->df->fund->id);

        $response->assertStatus(200);
        $response->assertViewHas('chartData');
    }

    public function test_index_handles_none_filter_values()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets?fund_id=none&asset_id=none');

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAssets');
    }

    // ==================== Create Tests ====================

    public function test_create_displays_form_with_api_data()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets/create');

        $response->assertStatus(200);
        $response->assertViewHas('api');
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form_with_api_data()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets/' . $this->portfolioAssetA->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAsset');
        $response->assertViewHas('api');
    }

    public function test_edit_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets/99999/edit');

        $response->assertRedirect(route('portfolioAssets.index'));
    }

    // ==================== Base Controller Tests ====================

    public function test_show_displays_portfolio_asset()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets/' . $this->portfolioAssetA->id);

        $response->assertStatus(200);
        $response->assertViewHas('portfolioAsset');
    }

    public function test_show_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/portfolioAssets/99999');

        $response->assertRedirect(route('portfolioAssets.index'));
    }

    public function test_store_creates_new_portfolio_asset()
    {
        $newAsset = $this->df->createAsset(null);

        $response = $this->actingAs($this->user)->post('/portfolioAssets', [
            'portfolio_id' => $this->df->portfolio->id,
            'asset_id' => $newAsset->id,
            'position' => 100.5,
            'start_dt' => '2024-01-01',
            'end_dt' => '9999-12-31',
        ]);

        $response->assertRedirect(route('portfolioAssets.index'));
        $this->assertDatabaseHas('portfolio_assets', [
            'portfolio_id' => $this->df->portfolio->id,
            'asset_id' => $newAsset->id,
        ]);
    }

    public function test_update_modifies_portfolio_asset()
    {
        $portfolioAsset = $this->portfolioAssetA;
        $newPosition = 999.99;

        $response = $this->actingAs($this->user)->put('/portfolioAssets/' . $portfolioAsset->id, [
            'portfolio_id' => $portfolioAsset->portfolio_id,
            'asset_id' => $portfolioAsset->asset_id,
            'position' => $newPosition,
            'start_dt' => $portfolioAsset->start_dt->format('Y-m-d'),
            'end_dt' => $portfolioAsset->end_dt->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('portfolioAssets.index'));
        $this->assertDatabaseHas('portfolio_assets', [
            'id' => $portfolioAsset->id,
            'position' => $newPosition,
        ]);
    }

    public function test_update_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->put('/portfolioAssets/99999', [
            'portfolio_id' => 1,
            'asset_id' => 1,
            'position' => 100,
            'start_dt' => '2024-01-01',
            'end_dt' => '9999-12-31',
        ]);

        $response->assertRedirect(route('portfolioAssets.index'));
    }

    public function test_destroy_returns_redirect()
    {
        $pa = PortfolioAsset::create([
            'portfolio_id' => $this->df->portfolio->id,
            'asset_id' => $this->assetA->id,
            'position' => 1,
            'start_dt' => '2024-01-01',
            'end_dt' => '9999-12-31',
        ]);

        $response = $this->actingAs($this->user)->delete('/portfolioAssets/' . $pa->id);

        $response->assertRedirect(route('portfolioAssets.index'));
    }

    public function test_destroy_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->delete('/portfolioAssets/99999');

        $response->assertRedirect(route('portfolioAssets.index'));
    }

    // ==================== Chart Data Branch Tests ====================

    /** Create a closed (end_dt != 9999) portfolio-asset position in the fund's portfolio. */
    protected function createClosedPosition(Asset $asset, $position = 25): PortfolioAsset
    {
        return PortfolioAsset::create([
            'portfolio_id' => $this->df->portfolio->id,
            'asset_id' => $asset->id,
            'position' => $position,
            'start_dt' => '2024-01-01',
            'end_dt' => '2024-06-30',
        ]);
    }

    public function test_index_single_asset_chart_with_fund_and_closed_position()
    {
        $assetC = $this->df->createAsset(null);
        $this->createClosedPosition($assetC);

        $response = $this->actingAs($this->user)->get(route('portfolioAssets.index', [
            'asset_id' => $assetC->id,
            'fund_id' => $this->df->fund->id,
            'start_dt' => '2023-01-01',
            'end_dt' => '2024-12-31',
        ]));

        $response->assertStatus(200);
        $chartData = $response->viewData('chartData');
        $this->assertNotNull($chartData);
        $this->assertArrayHasKey('data', $chartData);
    }

    public function test_index_multi_asset_chart_with_fund_and_closed_positions()
    {
        $assetC = $this->df->createAsset(null);
        $assetD = $this->df->createAsset(null);
        $this->createClosedPosition($assetC);
        $this->createClosedPosition($assetD);

        $response = $this->actingAs($this->user)->get(route('portfolioAssets.index', [
            'asset_id' => [$assetC->id, $assetD->id],
            'fund_id' => $this->df->fund->id,
            'start_dt' => '2023-01-01',
            'end_dt' => '2024-12-31',
        ]));

        $response->assertStatus(200);
        $chartData = $response->viewData('chartData');
        $this->assertNotNull($chartData);
        $this->assertTrue($chartData['multiAsset']);
    }

    public function test_index_fund_only_chart_with_closed_position_and_end_date()
    {
        $assetC = $this->df->createAsset(null);
        $this->createClosedPosition($assetC);

        // fund_id set, no specific asset -> getMultiAssetChartData (fund has <= 8 assets)
        $response = $this->actingAs($this->user)->get(route('portfolioAssets.index', [
            'fund_id' => $this->df->fund->id,
            'start_dt' => '2023-01-01',
            'end_dt' => '2024-12-31',
        ]));

        $response->assertStatus(200);
        $chartData = $response->viewData('chartData');
        $this->assertNotNull($chartData);
        $this->assertTrue($chartData['multiAsset']);
    }

    public function test_index_fund_chart_skipped_for_more_than_8_assets()
    {
        for ($i = 0; $i < 9; $i++) {
            $a = $this->df->createAsset(null);
            PortfolioAsset::create([
                'portfolio_id' => $this->df->portfolio->id,
                'asset_id' => $a->id,
                'position' => 1,
                'start_dt' => '2024-01-01',
                'end_dt' => '9999-12-31',
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('portfolioAssets.index', ['fund_id' => $this->df->fund->id]));

        $response->assertStatus(200);
        $this->assertNull($response->viewData('chartData'));
    }

    public function test_index_single_asset_chart_null_when_no_positions()
    {
        $loneAsset = $this->df->createAsset(null); // asset with no portfolio_asset rows

        $response = $this->actingAs($this->user)
            ->get(route('portfolioAssets.index', ['asset_id' => $loneAsset->id]));

        $response->assertStatus(200);
        $this->assertNull($response->viewData('chartData'));
    }

    public function test_index_multi_asset_chart_null_for_unknown_assets()
    {
        $response = $this->actingAs($this->user)
            ->get(route('portfolioAssets.index', ['asset_id' => [999998, 999999]]));

        $response->assertStatus(200);
        $this->assertNull($response->viewData('chartData'));
    }
}
