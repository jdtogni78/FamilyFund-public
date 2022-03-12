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

    public function create_transaction($account_id, $tran_id, $shares, $value, $currentValue, $ref_tran_id, $date)
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
        $transaction['type'] = 'PUR';
        $transaction['current_value'] = Utils::currency($currentValue);
        
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
            [$tran_id, $shares, $value, $ref_tran_id, $date] = $transaction;
            $currentValue = $currentValues[$i++];
            $arr['transactions'][] = $this->create_transaction($id, $tran_id, $shares, $value, $currentValue, $ref_tran_id, $date);
        }
        // var_dump($arr);
        // print($this->response->getContent());
        $this->assertApiResponse($arr);
    }

    public function account7_balances()
    {
        return [2000, 2567.49, 3533.84, 3456.18];
    }
    
    public function compareFloats($a, $b)
    {
        return abs($a - $b) < 0.0001;
    }

    /**
     * @test
     */
    public function test_account_balance_as_of()
    {
        // $this->verbose = true;
        $balance = $this->account7_balances();
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
        $t1 = [46,     2000, 2000, null, '2021-01-01' . $time];
        $t2 = [42, 132.3049,  150, null, '2021-07-01' . $time];
        $t3 = [44, 132.3049,  150,   42, '2021-07-01' . $time];
        $time = 'T02:35:00.000000Z';
        $t4 = [20,  37.0496,   50, null, '2022-01-09' . $time];
        $t6 = [21,  37.0496,   50,   20, '2022-01-09' . $time];
        $t5 = [22, 111.1489,  150, null, '2022-01-09' . $time];
        $t7 = [23, 111.1489,  150,   22, '2022-01-09' . $time];

        $balances = $this->account7_balances();
        $this->_test_account_transactions_as_of(7, '2021-01-01', [$t1], [$balances[0]]);
        $this->_test_account_transactions_as_of(7, '2021-07-01', [$t1, $t2, $t3], 
            $values = [2267.49, 150, 150]);
        $this->compareFloats(array_sum($values), $balances[1]); 

        $this->_test_account_transactions_as_of(7, '2022-01-01', [$t1, $t2, $t3], 
            $values = [3120.92, 206.46, 206.46]);
        $this->compareFloats(array_sum($values), $balances[2]); 
        $this->_test_account_transactions_as_of(7, '2022-01-15', [$t1, $t2, $t3, $t4, $t6, $t5, $t7], 
            $values = [2699.08, 178.55, 178.55, 50, 50, 150, 150]);
        $this->compareFloats(array_sum($values), $balances[3]); 
    }

    // TODO test account performance
}
