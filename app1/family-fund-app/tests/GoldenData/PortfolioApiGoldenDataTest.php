<?php namespace Tests\GoldenData;

use App\Http\Resources\PortfolioResource;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Portfolio;
use App\Models\Utils;

class PortfolioApiGoldenDataTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verbose = false;
    }

    public function _test_read_portfolio_as_of($id, $asOf, $assets)
    {
        $verbose = false;
        $portfolio = Portfolio::find($id);

        $url = '/api/portfolios/' . $portfolio->id . '/as_of/' . $asOf;
        $this->getAPI($url);

        $as = array();
        $total = 0;
        foreach($assets as $asset) {
            [$asset_id, $name, $price, $shares] = $asset;
            $a = array();
            $a['id'] = $asset_id;
            $a['name'] = $name;
            $a['price']  = Utils::currency($price);
            $a['position'] = Utils::position($shares);
            $a['value']  = Utils::currency($price * $shares);
            $total += $price * $shares;
            $as[] = $a;
            if ($verbose) print_r(json_encode($a)."\n");
        }
        $rss = new PortfolioResource($portfolio);
        $arr = $rss->toArray(null);
        $arr['assets'] = $as;
        $arr['as_of'] = $asOf;
        $arr['total_value'] = Utils::currency($total);

        if ($verbose) print_r($arr);
        if ($verbose) print_r($this->response->getContent());

        // $this->verbose = true;
        $this->assertApiResponse($arr);
    }

    /**
     * @test
     */
    public function test_read_portfolio_as_of() {
        $a21 = [
            [1, 'SPXL',     72.25, 67.62800000],
            [2, 'TECL',     40.65, 40.42000000],
            [3, 'SOXL',     31.10, 54.58000000],
            [4, 'IAU',      36.26, 94.00000000],
            [6, 'FIPDX',    11.04, 598.5560000],
            [7, 'ETH',     731.80, 0.40050685],
            [8, 'BTC',   29302.75, 0.01752356],
            [9, 'LTC',     126.64, 1.76610087],
            [11,'XRP',       0.32, 885.16959200],
            [12,'XLM',       0.13, 911.52779760],
            [10,'CASH',      1.00, 5331.8372007],
        ];
        $a22 = [
            [1, 'SPXL',    143.41, 67.628],
            [2, 'TECL',     86.23, 40.42],
            [3, 'SOXL',     68.01, 54.58],
            [4, 'IAU',      34.81, 94],
            [6, 'FIPDX',    11.16, 598.556],
            [7, 'ETH',    3645.90, 0.40050685],
            [8, 'BTC',   45883.00, 0.01752356],
            [9, 'LTC',     144.22, 1.76610087],
            [11,'XRP',       0.81, 885.169592],
            [12,'XLM',       0.27, 911.5277976],
            [10,'CASH',      1.00, 9275.4572008],
        ];
        $this->_test_read_portfolio_as_of(2, '2021-01-01', $a21);
        $this->_test_read_portfolio_as_of(2, '2022-01-01', $a22);
    }

}
