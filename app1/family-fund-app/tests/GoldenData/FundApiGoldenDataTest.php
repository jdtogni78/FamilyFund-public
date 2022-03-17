<?php namespace Tests\GoldenData;

use App\Http\Resources\FundResource;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\TimestampTestTrait;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Fund;
use App\Models\Utils;

class FundApiGoldenDataTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;
    use TimestampTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verbose = false;
    }

    private function _test_fund_as_of($id, $asOf, $value, $shares, $unallocated)
    {
        $fund = Fund::find($id);
        $url = '/api/funds/'.$fund->id.'/as_of/'.$asOf;
        $this->getAPI($url);

        $calc = array();
        $calc['value'] = Utils::currency($value);
        $calc['shares'] = Utils::shares($shares);
        $calc['share_value'] = Utils::currency($value/$shares);
        $allocated = $shares - $unallocated;
        $calc['allocated_shares'] = Utils::shares($allocated);
        $calc['unallocated_shares'] = Utils::shares($unallocated);
        $calc['allocated_shares_percent'] = Utils::percent($allocated/$shares);
        $calc['unallocated_shares_percent'] = Utils::percent($unallocated/$shares);

        $rss = new FundResource($fund);
        $expected = $rss->toArray(null);
        $expected['summary'] = $calc;
        $expected['as_of'] = $asOf;

        // $this->verbose = true;
        $this->assertApiResponse($expected);
    }

    public static function fund2_shares()
    {
        return [25000, 25529.2196];
    }

    public static function fund2_values(): array
    {
        return [
            25006.96,   // 1
            25716.03,   // 2
            25242.48,   // 3
            26152.55,   // 4
            27329.28,   // 5
            27067.45,   // 6
            32538.5,    // 7
            34345.16,   // 8
            36150.96,   // 9
            34210.62,   // 10
            37387.16,   // 11
            37834.05,   // 12
            39605.46,   // 13 - 2022
            34751.68,   // 14
        ];
    }

    /**
     * @test
     */
    public function test_fund_as_of()
    {
        $shares = $this->fund2_shares();
        $values = $this->fund2_values();
        $this->_test_fund_as_of(2, '2021-01-02', $values[0],  $shares[0], 10000);
        $this->_test_fund_as_of(2, '2021-07-01', $values[6],  $shares[0], 10000);
        $value = 33485.53;
        $this->_test_fund_as_of(2, '2021-07-02', $value,  $shares[1], 10000);
        $this->_test_fund_as_of(2, '2022-01-02', $values[12], $shares[1], 10000);
        $this->_test_fund_as_of(2, '2022-01-16', $values[13],  $shares[1], 7110.1289);
    }


    private function _test_fund_performance_as_of($id, $asOf, $performances)
    {
        $fund = Fund::find($id);

        $api = '/api/funds/' . $fund->id . '/performance_as_of/' . $asOf;
        $this->getAPI($api);

        $expected = array();
        $expected['id'] = $id;
        $expected['name'] = $fund->name;
        $expected['as_of'] = $asOf;

        $perf = array();
        if ($this->verbose) print_r("performances: " . json_encode($performances) . "\n");
        foreach ($performances as $performance) {
            [$year, $yp, $sv, $s, $v] = $performance;
            $p = array();
            $p['performance']   = Utils::percent($yp);
            $p['share_value']   = Utils::currency($sv);
            $p['shares']        = Utils::shares($s);
            $p['value']         = Utils::currency($v);
            $perf[$year] = $p;
        }

        $expected['monthly_performance'] = $perf;
        $this->assertApiResponse($expected);
    }

    /**
     * @test
     */
    public function test_fund_performance_as_of()
    {
        $shares = $this->fund2_shares();
        $values = $this->fund2_values();

        $this->setupTimestampTest("2021-01-01");
        $ts = $this->date();
        $data = [];
        $i = 0;
        $pv = $values[0];
        foreach ($values as $v) {
            $sId = $i < 7 ? 0 : 1;
            $sv = $v/$shares[$sId];
            $yp = ($v/$pv)-1;
            $data[$i]  = [$ts, $yp, $sv, $shares[$sId], $v];
            $this->nextMonth();
            $ts = $this->date();
            $pv = $v;
            $i++;
        }
        $data[$i-1][0] = "2022-01-15";

        $this->_test_fund_performance_as_of(2, '2021-01-01', array_slice($data, 0,1));
        $this->_test_fund_performance_as_of(2, '2021-07-01', array_slice($data, 0,7));
        $this->_test_fund_performance_as_of(2, '2022-01-01', array_slice($data, 0,13));
        $this->_test_fund_performance_as_of(2, '2022-01-15', array_slice($data, 0,14));
    }


    private function _test_fund_balances_as_of($fundId, $asOf, $sharePrice, $balances)
    {
        $fund = Fund::find($fundId);

        $api = '/api/funds/' . $fund->id . '/account_balances_as_of/' . $asOf;
        $this->getAPI($api);

        $expected = array();
        $expected['id'] = $fundId;
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

        $this->loginWithFakeUser();
        $sharePrice = 2000.56 / $b21[0][4] - 0.000002;
        $this->_test_fund_balances_as_of(2, '2021-01-02', $sharePrice, $b21);
        $sharePrice = 2970.39 / $b2107[0][4];
        $this->_test_fund_balances_as_of(2, '2021-07-02', $sharePrice, $b2107);
        $sharePrice = 3513.27 / $b2107[0][4];
        $this->_test_fund_balances_as_of(2, '2022-01-02', $sharePrice, $b2107);
        $sharePrice = 3486.17 / $b2201[0][4];
        $this->_test_fund_balances_as_of(2, '2022-01-16', $sharePrice, $b2201);
    }
}
