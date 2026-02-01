<?php namespace Tests\Repositories;

use App\Models\TradePortfolio;
use App\Repositories\TradePortfolioRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class TradePortfolioRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var TradePortfolioRepository
     */
    protected $tradePortfolioRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->tradePortfolioRepo = \App::make(TradePortfolioRepository::class);
    }
    public function test_create_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->make()->toArray();

        $createdTradePortfolio = $this->tradePortfolioRepo->create($tradePortfolio);

        $createdTradePortfolio = $createdTradePortfolio->toArray();
        $this->assertArrayHasKey('id', $createdTradePortfolio);
        $this->assertNotNull($createdTradePortfolio['id'], 'Created TradePortfolio must have id specified');
        $this->assertNotNull(TradePortfolio::find($createdTradePortfolio['id']), 'TradePortfolio with given id must be in DB');
        $this->assertModelData($tradePortfolio, $createdTradePortfolio);
    }
    public function test_read_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->create();

        $dbTradePortfolio = $this->tradePortfolioRepo->find($tradePortfolio->id);

        $dbTradePortfolio = $dbTradePortfolio->toArray();
        $this->assertModelData($tradePortfolio->toArray(), $dbTradePortfolio);
    }
    public function test_update_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->create();
        $fakeTradePortfolio = TradePortfolio::factory()->make()->toArray();

        $updatedTradePortfolio = $this->tradePortfolioRepo->update($fakeTradePortfolio, $tradePortfolio->id);

        $this->assertModelData($fakeTradePortfolio, $updatedTradePortfolio->toArray());
        $dbTradePortfolio = $this->tradePortfolioRepo->find($tradePortfolio->id);
        $this->assertModelData($fakeTradePortfolio, $dbTradePortfolio->toArray());
    }
    public function test_delete_trade_portfolio()
    {
        $tradePortfolio = TradePortfolio::factory()->create();

        $resp = $this->tradePortfolioRepo->delete($tradePortfolio->id);

        $this->assertTrue($resp);
        $this->assertNull(TradePortfolio::find($tradePortfolio->id), 'TradePortfolio should not exist in DB');
    }
}
