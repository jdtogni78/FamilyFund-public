<?php namespace Tests\GoldenData;

use App\Http\Resources\FundResource;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Account;
use App\Models\Fund;
use App\Models\Portfolio;
use App\Models\AccountExt;
use App\Models\PortfolioExt;
use App\Models\Utils;

class FundApiGoldenDataTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    private function _test_fund_as_of($id, $asOf, $value, $shares, $unallocated)
    {
        $fund = Fund::find($id);

        $this->response = $this->json(
            'GET',
            $url = '/api/funds/'.$fund->id.'/as_of/'.$asOf
        );
        // print_r($url);

        $calc = array();
        $calc['value'] = Utils::currency($value);
        $calc['shares'] = Utils::shares($shares);
        $calc['share_value'] = Utils::currency($value/$shares);
        $calc['unallocated_shares'] = $unallocated;

        $rss = new FundResource($fund);
        $expected = $rss->toArray(null);
        $expected['summary'] = $calc;
        $expected['as_of'] = $asOf;

        // $this->verbose = true;
        $this->assertApiResponse($expected);
    }
    
    public static function fund2_values()
    {
        return [25000, 28343.62, 28943.62, 39837.36, 34452.72];
    }

    public static function fund2_shares()
    {
        return [25000, 25529.2196];
    }
    
    /**
     * @test
     */
    public function test_fund_as_of()
    {
        $shares = $this->fund2_shares();
        $values = $this->fund2_values();
        $this->_test_fund_as_of(2, '2021-01-02', $values[0], $shares[0], 10000);
        $this->_test_fund_as_of(2, '2021-07-02', $values[2], $shares[1], 10000);
        $this->_test_fund_as_of(2, '2022-01-02', $values[3], $shares[1], 10000);
        $this->_test_fund_as_of(2, '2022-01-16', $values[4], $shares[1], 7110.1289);
    }


    private function _test_fund_performance_as_of($id, $asOf, $performances)
    {
        $fund = Fund::find($id);

        $this->response = $this->json(
            'GET',
            '/api/funds/'.$fund->id.'/performance_as_of/'.$asOf
        );

        // print($this->response->getContent());

        $expected = array();
        $expected['id'] = $id;
        $expected['name'] = $fund->name;
        $expected['as_of'] = $asOf;

        $perf = array();
        foreach ($performances as $performance) {
            [$year, $yp, $sv, $s, $v] = $performance;
            $p = array();
            $p['performance']   = Utils::percent($yp);
            $p['share_value']   = Utils::currency($sv);
            $p['shares']        = Utils::shares($s);
            $p['value']         = Utils::currency($v);
            $perf[$year] = $p;
        }

        $expected['performance'] = $perf;
        $this->assertApiResponse($expected);
    }

    /**
     * @test
     */
    public function test_fund_performance_as_of()
    {
        $shares = $this->fund2_shares();
        $values = $this->fund2_values();

        $p21     = ["2021",         0.0, 1.00, $shares[0], $values[0]];
        $p2107   = ["2021-07-01",  0.13, 1.13, $shares[0], $values[1]];
        $p22     = ["2022",         0.0, 1.56, $shares[1], $values[3]];
        $p220115 = ["2022-01-15", -0.14, 1.35, $shares[1], $values[4]];

        $this->_test_fund_performance_as_of(2, '2021-01-01', [$p21]);
        $this->_test_fund_performance_as_of(2, '2021-07-01', [$p21, $p2107]);
        $this->_test_fund_performance_as_of(2, '2022-01-01', [$p21, $p22]);
        $this->_test_fund_performance_as_of(2, '2022-01-15', [$p21, $p22, $p220115]);
    }


    private function _test_fund_balances_as_of($id, $asOf, $sharePrice, $balances)
    {
        $fund = Fund::find($id);

        $this->response = $this->json(
            'GET',
            '/api/funds/'.$fund->id.'/account_balances_as_of/'.$asOf
        );

        // print($this->response->getContent());

        $expected = array();
        $expected['id'] = $id;
        $expected['name'] = $fund->name;
        $expected['as_of'] = $asOf;

        $bals = array();
        foreach ($balances as $balance) {
            [$account_id, $user_id, $nickname, $name, $shares] = $balance;
            $b = array();
            $b['nickname'] = $nickname;
            $b['shares'] = $shares;
            $b['user'] = [
                'id' => $user_id,
                'name' => $name ? $name : "N/A", 
            ];
            $b['type'] = 'OWN';
            $b['account_id'] = $account_id;
            $b['value'] = Utils::currency($shares * $sharePrice);
            $bals[] = $b;
        }

        $expected['balances'] = $bals;
        $this->assertApiResponse($expected);
    }

    /**
     * @test
     */
    public function test_fund_balances_as_of()
    {
        $shares = $this->fund2_shares();
        $b21     = [
            [7,  1, "LT", "NieceA1", 2000],
            [8,  2, "GT", "NieceA2", 2000],
            [9,  3, "GG", "NieceB1", 2000],
            [10, 4, "PG", "NieceB2", 2000],
            [11, 5, "NB", "NephewC1", 5000],
            [12, 8, "VT", "Sister2", 2000],
            // [14, null, "F2", null, $shares[0]],
        ];
        $b2107     = [
            [7,  1, "LT", "NieceA1", 2264.6098],
            [8,  2, "GT", "NieceA2", 2264.6098],
            [9,  3, "GG", "NieceB1", 2000],
            [10, 4, "PG", "NieceB2", 2000],
            [11, 5, "NB", "NephewC1", 5000],
            [12, 8, "VT", "Sister2", 2000],
            // [14, null, "F2", null, $shares[1]],
        ];
        $b2201     = [
            [7,  1, "LT", "NieceA1", 2561.0068],
            [8,  2, "GT", "NieceA2", 2561.0068],
            [9,  3, "GG", "NieceB1", 2444.5954],
            [10, 4, "PG", "NieceB2", 2296.397],
            [11, 5, "NB", "NephewC1", 5000],
            [12, 8, "VT", "Sister2", 3556.0847],
            // [14, null, "F2", null, $shares[1]],
        ];

        $this->_test_fund_balances_as_of(2, '2021-01-02', 1, $b21);
        $this->_test_fund_balances_as_of(2, '2021-07-02', 1.1337448, $b2107);
        $this->_test_fund_balances_as_of(2, '2022-01-02', 1.56046133, $b2107);
        $this->_test_fund_balances_as_of(2, '2022-01-16', 1.34954066, $b2201);
    }}
