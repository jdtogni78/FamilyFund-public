<?php namespace Tests\Repositories;

use App\Models\TradePortfolioItem;
use App\Repositories\TradePortfolioItemRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class TradePortfolioItemRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var TradePortfolioItemRepository
     */
    protected $tradePortfolioItemRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->tradePortfolioItemRepo = \App::make(TradePortfolioItemRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_trade_portfolio_item()
    {
        $tradePortfolioItem = TradePortfolioItem::factory()->make()->toArray();

        $createdTradePortfolioItem = $this->tradePortfolioItemRepo->create($tradePortfolioItem);

        $createdTradePortfolioItem = $createdTradePortfolioItem->toArray();
        $this->assertArrayHasKey('id', $createdTradePortfolioItem);
        $this->assertNotNull($createdTradePortfolioItem['id'], 'Created TradePortfolioItem must have id specified');
        $this->assertNotNull(TradePortfolioItem::find($createdTradePortfolioItem['id']), 'TradePortfolioItem with given id must be in DB');
        $this->assertModelData($tradePortfolioItem, $createdTradePortfolioItem);
    }

    /**
     * @test read
     */
    public function test_read_trade_portfolio_item()
    {
        $tradePortfolioItem = TradePortfolioItem::factory()->create();

        $dbTradePortfolioItem = $this->tradePortfolioItemRepo->find($tradePortfolioItem->id);

        $dbTradePortfolioItem = $dbTradePortfolioItem->toArray();
        $this->assertModelData($tradePortfolioItem->toArray(), $dbTradePortfolioItem);
    }

    /**
     * @test update
     */
    public function test_update_trade_portfolio_item()
    {
        $tradePortfolioItem = TradePortfolioItem::factory()->create();
        $fakeTradePortfolioItem = TradePortfolioItem::factory()->make()->toArray();

        $updatedTradePortfolioItem = $this->tradePortfolioItemRepo->update($fakeTradePortfolioItem, $tradePortfolioItem->id);

        $this->assertModelData($fakeTradePortfolioItem, $updatedTradePortfolioItem->toArray());
        $dbTradePortfolioItem = $this->tradePortfolioItemRepo->find($tradePortfolioItem->id);
        $this->assertModelData($fakeTradePortfolioItem, $dbTradePortfolioItem->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_trade_portfolio_item()
    {
        $tradePortfolioItem = TradePortfolioItem::factory()->create();

        $resp = $this->tradePortfolioItemRepo->delete($tradePortfolioItem->id);

        $this->assertTrue($resp);
        $this->assertNull(TradePortfolioItem::find($tradePortfolioItem->id), 'TradePortfolioItem should not exist in DB');
    }
}
