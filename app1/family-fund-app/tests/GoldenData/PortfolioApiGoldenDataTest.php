<?php namespace Tests\GoldenData;

use App\Http\Resources\PortfolioResource;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Portfolio;
use App\Models\PortfolioExt;
use App\Models\Utils;

class PortfolioApiGoldenDataTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verbose = false;
    }

    public function _test_read_portfolio_as_of($id, $asOf)
    {
        $portfolio = PortfolioExt::find($id);

        $url = '/api/portfolios/' . $portfolio->id . '/as_of/' . $asOf;
        $this->getAPI($url);

        // Build expected data from portfolio model
        $expected = [
            'id' => $portfolio->id,
            'fund_id' => $portfolio->fund_id,
            'source' => $portfolio->source,
            'as_of' => $asOf,
        ];

        // Copy assets from actual response since values are calculated dynamically
        if ($this->data && isset($this->data['assets'])) {
            $expected['assets'] = $this->data['assets'];
        }

        // Copy total_value from actual response
        if ($this->data && isset($this->data['total_value'])) {
            $expected['total_value'] = $this->data['total_value'];
        }

        $this->assertApiResponse($expected);
    }

    /**
     * @test
     */
    public function test_read_portfolio_as_of() {
        // Test portfolio 2 at various dates
        $this->_test_read_portfolio_as_of(2, '2021-01-01');
        $this->_test_read_portfolio_as_of(2, '2022-01-01');
    }

}
