<?php namespace Tests\GoldenData;

use App\Http\Resources\AccountResource;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Account;
use App\Models\Utils;

class AccountApiGoldenDataTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    public function create_own_balance($shares, $value)
    {
        $balance = array();
        $balance['type'] = 'OWN';
        $balance['shares'] = $shares;
        $balance['market_value'] = $value;
        return $balance;
    }

    public function create_transaction($account_id, $tran_id, $shares, $total_shares, $value, $currentValue, $ref_tran_id, $date)
    {
        $transaction = array();
        $transaction['id'] = $tran_id;
        $transaction['shares'] = Utils::shares($shares);
        $transaction['share_price'] = Utils::currency($shares ? $value / $shares : 0);
        $transaction['value'] = Utils::currency($value);
        if ($ref_tran_id != null)
            $transaction['reference_transaction'] = $ref_tran_id;
        $transaction['timestamp'] = $date;
        // $transaction['account_id'] = 1;//$account_id;
        $transaction['type'] = TransactionExt::TYPE_PURCHASE;
        $transaction['current_value'] = Utils::currency($currentValue);
        $transaction['current_performance'] = Utils::percent($currentValue/$value - 1);
        $transaction['balances'] = ['OWN' => $total_shares];

        return $transaction;
    }

    public function _test_account_balance_as_of($id, $asOf, $fund_name, $fund_id, $shares, $balance)
    {
        $account = Account::find(7);

        $this->response = $this->json(
            'GET',
            '/api/accounts/'.$account->id.'/as_of/'.$asOf
        );

        $rss = new AccountResource($account);
        $arr = $rss->toArray(null);
        $arr['as_of'] = $asOf;
        $arr['balances'][] = $this->create_own_balance($shares, $balance);
        $arr['fund'] = [
            'name' => $fund_name,
            'id' => $fund_id,
        ];
        $user = $account->user()->first();
        if ($user) {
            $arr['user'] = [
                'id' => $user->id,
                'name' => $user->name,
            ];
        }
        $this->assertApiResponse($arr);
    }

    public function _test_account_transactions_as_of($id, $asOf, $transactions, $currentValues)
    {
        $account = Account::find(7);

        $this->response = $this->json(
            'GET',
            '/api/accounts/'.$account->id.'/transactions_as_of/'.$asOf
        );

        $arr = array();
        $arr['nickname'] = $account->nickname;
        $arr['id'] = $id;
        $arr['as_of'] = $asOf;
        $i = 0;
        foreach ($transactions as $transaction) {
            [$tran_id, $shares, $total_shares, $value, $ref_tran_id, $date] = $transaction;
            $currentValue = $currentValues[$i++];
            $arr['transactions'][] = $this->create_transaction($id, $tran_id, $shares, $total_shares, $value, $currentValue, $ref_tran_id, $date);
        }
        // var_dump($arr);
        // print($this->response->getContent());
        $this->assertApiResponse($arr);
    }

    public function compareCurrency($a, $b)
    {
        if ($this->verbose) print_r("comp float: " . json_encode([$a, $b]) . "\n");
        return abs($a - $b) < 0.02;
    }

    /**
     * @test
     */
    public function test_account_balance_as_of()
    {
        // $this->verbose = true;
        $balance = [2000.56, 2970.39, 3513.27, 3486.17];
        $this->_test_account_balance_as_of(7, '2021-01-02', "Fidelity Fund", 2, 2000, $balance[0]);
        $this->_test_account_balance_as_of(7, '2021-07-02', "Fidelity Fund", 2, 2264.6098, $balance[1]);
        $this->_test_account_balance_as_of(7, '2022-01-02', "Fidelity Fund", 2, 2264.6098, $balance[2]);
        $this->_test_account_balance_as_of(7, '2022-01-16', "Fidelity Fund", 2, 2561.0068, $balance[3]);
    }

    /**
     * @test
     */
    public function test_account_transactions_as_of()
    {
        $time = 'T00:00:00.000000Z';
        $allShares = 0;
        $shares =     2000; $allShares += $shares; $t1 = [46, $shares, $allShares, 2000, null, '2021-01-01' . $time];
        $shares = 132.3049; $allShares += $shares; $t2 = [42, $shares, $allShares,  150, null, '2021-07-01' . $time];
        $shares = 132.3049; $allShares += $shares; $t3 = [44, $shares, $allShares,  150,   42, '2021-07-01' . $time];
        $time = 'T02:35:00.000000Z';
        $shares =  37.0496; $allShares += $shares; $t4 = [20, $shares, $allShares,   50, null, '2022-01-09' . $time];
        $shares =  37.0496; $allShares += $shares; $t6 = [21, $shares, $allShares,   50,   20, '2022-01-09' . $time];
        $shares = 111.1489; $allShares += $shares; $t5 = [22, $shares, $allShares,  150, null, '2022-01-09' . $time];
        $shares = 111.1489; $allShares += $shares; $t7 = [23, $shares, $allShares,  150,   22, '2022-01-09' . $time];

        $balances = [2000.56, 2947.48, 3513.27, 3486.17];
        $this->_test_account_transactions_as_of(7, '2021-01-01', [$t1], [$balances[0]]);
        $this->_test_account_transactions_as_of(7, '2021-07-01', [$t1, $t2, $t3],
            $values = [2603.08, 172.2, 172.2]);
        $this->assertTrue($this->compareCurrency(array_sum($values), $balances[1]));

        $this->_test_account_transactions_as_of(7, '2022-01-01', [$t1, $t2, $t3],
            $values = [3102.76, 205.25, 205.25]);
        $this->assertTrue($this->compareCurrency(array_sum($values), $balances[2]));
        $this->_test_account_transactions_as_of(7, '2022-01-15', [$t1, $t2, $t3, $t4, $t6, $t5, $t7],
            $values = [2722.5, 180.1, 180.1, 50.43, 50.43, 151.3, 151.3]);
        $this->assertTrue($this->compareCurrency(array_sum($values), $balances[3]));
    }

    // TODO test account performance
}
