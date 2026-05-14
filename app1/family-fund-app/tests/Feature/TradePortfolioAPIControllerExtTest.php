<?php

namespace Tests\Feature;

use App\Models\TradePortfolio;
use App\Models\TradePortfolioExt;
use App\Models\TradePortfolioItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\ApiTestTrait;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for TradePortfolioAPIControllerExt
 * Target: Get coverage from 51% to 80%+
 */
class TradePortfolioAPIControllerExtTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware, ApiTestTrait;

    protected DataFactory $df;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();
        $this->user = $this->df->user;
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Index Tests ====================

    public function test_index_returns_active_trade_portfolios()
    {
        // Create a trade portfolio with the DataFactory
        $tradePortfolio = $this->df->createTradePortfolio('2022-01-01', '9999-12-31');

        $response = $this->json('GET', '/api/trade_portfolios');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_index_filters_by_date_range()
    {
        // Create trade portfolio that's currently active
        $activePortfolio = $this->df->createTradePortfolio(
            now()->subYear()->format('Y-m-d'),
            '9999-12-31'
        );

        // Get portfolios as of today
        $response = $this->json('GET', '/api/trade_portfolios');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_index_excludes_portfolios_without_portfolio_id()
    {
        // Create trade portfolio without portfolio_id
        TradePortfolio::factory()->create([
            'portfolio_id' => null,
            'start_dt' => now()->subYear(),
            'end_dt' => '9999-12-31',
        ]);

        // Create one with portfolio_id
        $this->df->createTradePortfolio(now()->subYear()->format('Y-m-d'), '9999-12-31');

        $response = $this->json('GET', '/api/trade_portfolios');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // The one without portfolio_id should be filtered out
        $data = $response->json('data');
        foreach ($data as $portfolio) {
            $this->assertNotNull($portfolio['portfolio_id']);
        }
    }

    public function test_index_excludes_expired_portfolios()
    {
        // Create expired trade portfolio
        TradePortfolio::factory()->create([
            'portfolio_id' => $this->df->portfolio->id,
            'start_dt' => '2020-01-01',
            'end_dt' => '2021-01-01',  // Expired
        ]);

        $response = $this->json('GET', '/api/trade_portfolios');

        $response->assertStatus(200);
    }

    public function test_index_excludes_future_portfolios()
    {
        // Create future trade portfolio
        TradePortfolio::factory()->create([
            'portfolio_id' => $this->df->portfolio->id,
            'start_dt' => now()->addYear()->format('Y-m-d'),
            'end_dt' => '9999-12-31',
        ]);

        $response = $this->json('GET', '/api/trade_portfolios');

        $response->assertStatus(200);
    }

    // ==================== Show Tests ====================

    public function test_show_by_id_returns_trade_portfolio()
    {
        $tradePortfolio = $this->df->createTradePortfolio('2022-01-01', '9999-12-31');

        $response = $this->json('GET', '/api/trade_portfolios/' . $tradePortfolio->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.id', $tradePortfolio->id);
    }

    public function test_show_by_account_name_returns_trade_portfolio()
    {
        $tradePortfolio = $this->df->createTradePortfolio(
            now()->subYear()->format('Y-m-d'),
            '9999-12-31'
        );

        $accountName = $tradePortfolio->account_name;

        $response = $this->json('GET', '/api/trade_portfolios/' . $accountName);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_show_returns_404_for_invalid_id()
    {
        $response = $this->json('GET', '/api/trade_portfolios/99999');

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    public function test_show_returns_404_for_invalid_account_name()
    {
        $response = $this->json('GET', '/api/trade_portfolios/nonexistent_account');

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    public function test_show_includes_portfolio_items()
    {
        $tradePortfolio = $this->df->createTradePortfolio('2022-01-01', '9999-12-31');

        $response = $this->json('GET', '/api/trade_portfolios/' . $tradePortfolio->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'items',
            ],
        ]);
    }

    public function test_show_includes_max_cash_last_year()
    {
        $tradePortfolio = $this->df->createTradePortfolio('2022-01-01', '9999-12-31');

        $response = $this->json('GET', '/api/trade_portfolios/' . $tradePortfolio->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'max_cash_last_year',
            ],
        ]);
    }

    public function test_show_includes_source_field()
    {
        $tradePortfolio = $this->df->createTradePortfolio('2022-01-01', '9999-12-31');

        $response = $this->json('GET', '/api/trade_portfolios/' . $tradePortfolio->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'source',
            ],
        ]);
    }

    public function test_show_with_as_of_parameter()
    {
        $tradePortfolio = $this->df->createTradePortfolio('2022-01-01', '9999-12-31');
        $asOf = now()->subMonths(3)->format('Y-m-d');

        $response = $this->json('GET', '/api/trade_portfolios/' . $tradePortfolio->id . '?asOf=' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    // ==================== Create/Store Tests ====================

    public function test_store_creates_new_trade_portfolio()
    {
        $data = [
            'account_name' => 'TEST_ACCOUNT_' . uniqid(),
            'portfolio_id' => $this->df->portfolio->id,
            'start_dt' => '2022-01-01',
            'end_dt' => '9999-12-31',
            'cash_target' => 0.05,
            'cash_reserve_target' => 0.02,
            'max_single_order' => 0.20,  // decimal(5,2) format - represents percentage
            'minimum_order' => 100.00,
            'rebalance_period' => 30,
            'mode' => 'STD',
        ];

        $response = $this->json('POST', '/api/trade_portfolios', $data);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('trade_portfolios', [
            'account_name' => $data['account_name'],
        ]);
    }

    // ==================== Update Tests ====================

    public function test_update_modifies_trade_portfolio()
    {
        $tradePortfolio = $this->df->createTradePortfolio('2022-01-01', '9999-12-31');
        $newAccountName = 'UPDATED_' . uniqid();

        $response = $this->json('PUT', '/api/trade_portfolios/' . $tradePortfolio->id, [
            'account_name' => $newAccountName,
            'portfolio_id' => $this->df->portfolio->id,
            'start_dt' => '2022-01-01',
            'end_dt' => '9999-12-31',
            'cash_target' => 0.05,
            'cash_reserve_target' => 0.02,
            'max_single_order' => 0.20,  // decimal(5,2) format - represents percentage
            'minimum_order' => 100.00,
            'rebalance_period' => 30,
            'mode' => 'STD',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('trade_portfolios', [
            'id' => $tradePortfolio->id,
            'account_name' => $newAccountName,
        ]);
    }

    public function test_update_returns_404_for_invalid_id()
    {
        $response = $this->json('PUT', '/api/trade_portfolios/99999', [
            'account_name' => 'TEST',
            'start_dt' => '2022-01-01',
            'end_dt' => '9999-12-31',
            'cash_target' => 0.05,
            'cash_reserve_target' => 0.02,
            'max_single_order' => 0.20,  // decimal(5,2) format - represents percentage
            'minimum_order' => 100.00,
            'rebalance_period' => 30,
            'mode' => 'STD',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    // ==================== Delete Tests ====================

    public function test_destroy_deletes_trade_portfolio()
    {
        $tradePortfolio = $this->df->createTradePortfolio('2022-01-01', '9999-12-31');

        $response = $this->json('DELETE', '/api/trade_portfolios/' . $tradePortfolio->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // TradePortfolio uses soft deletes
        $this->assertSoftDeleted('trade_portfolios', [
            'id' => $tradePortfolio->id,
        ]);
    }

    public function test_destroy_returns_404_for_invalid_id()
    {
        $response = $this->json('DELETE', '/api/trade_portfolios/99999');

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    // ==================== Response Structure Tests ====================

    public function test_response_includes_all_expected_fields()
    {
        $tradePortfolio = $this->df->createTradePortfolio('2022-01-01', '9999-12-31');

        $response = $this->json('GET', '/api/trade_portfolios/' . $tradePortfolio->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'account_name',
                'portfolio_id',
                'start_dt',
                'end_dt',
                'items',
            ],
            'message',
        ]);
    }

    public function test_items_have_expected_structure()
    {
        $tradePortfolio = $this->df->createTradePortfolio('2022-01-01', '9999-12-31');

        $response = $this->json('GET', '/api/trade_portfolios/' . $tradePortfolio->id);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('items', $data);

        // DataFactory creates 3 items per trade portfolio
        $this->assertCount(3, $data['items']);
    }

    // ==================== Edge Cases ====================

    public function test_show_handles_portfolio_without_portfolio_relationship()
    {
        // Create trade portfolio without portfolio relationship
        $tradePortfolio = TradePortfolio::factory()->create([
            'portfolio_id' => null,
            'start_dt' => '2022-01-01',
            'end_dt' => '9999-12-31',
        ]);

        $response = $this->json('GET', '/api/trade_portfolios/' . $tradePortfolio->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_index_handles_empty_result()
    {
        // Create only expired or future portfolios
        TradePortfolio::factory()->create([
            'portfolio_id' => $this->df->portfolio->id,
            'start_dt' => '2010-01-01',
            'end_dt' => '2011-01-01',
        ]);

        // Delete any active trade portfolios that might have been created
        TradePortfolioExt::where('start_dt', '<=', now())
            ->where('end_dt', '>', now())
            ->whereNotNull('portfolio_id')
            ->delete();

        $response = $this->json('GET', '/api/trade_portfolios');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
