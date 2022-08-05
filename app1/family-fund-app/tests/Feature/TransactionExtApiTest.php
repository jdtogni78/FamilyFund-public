<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Transaction;
use App\Models\TransactionMatching;
use DB;
use Tests\DataFactory;

class TransactionApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    public function testBasics()
    {
        $factory = new DataFactory();

        $factory->createFundWithMatching();

        $match = $factory->matchingRule;
        $matchTran = $factory->matchTransaction;
        $transaction = $factory->transaction;

        $this->assertNotNull($match);
        $this->assertNotNull($match);
        $this->assertNotNull($transaction);
        $this->assertNotNull($match->transaction()->first());
        $this->assertNotNull($match->referenceTransaction()->first());
        $this->assertNotNull($matchTran->transactionMatching()->first());
        $this->assertNotNull($transaction->referenceTransactionMatching()->first());
    }

    public function postTransaction($transaction)
    {
        $tranArr = $transaction->toArray();

        $this->response = $this->json(
            'POST',
            '/api/transactions', $tranArr
        );

        print_r($this->response->getContent());
        $this->assertApiResponse($tranArr);
    }

    public function validateTransaction($tran, $transaction, $value, $balance, $hasMatching=false)
    {
        $this->assertEquals($tran->value, $value);
        $this->assertEquals($tran->value, $transaction->value);
        $this->assertNull($tran->shares);

        $this->assertEquals($tran->transactionMatching()->count(), $hasMatching?1:0);
    }

    public function validateBalance($tran, $transaction, $value, $balance)
    {
        $balance = $tran->accountBalances()->first();
        $this->assertNotNull($balance);
        print_r($balance->toArray());
        $this->assertEquals($balance->shares, $balance);
    }


    public function test_transactions()
    {
        $data = [
            [
                'fund' => ['shares' => 100000, 'value' => 1000],
                'match' => ['limit' => 150, 'match' => 100],
                'trans' => [
                    ['value' => 100, 'balance' => 10000, 'match' => ['value' => 100, 'balance' => 20000]],
                    ['value' => 100, 'balance' => 30000, 'match' => ['value' =>  50, 'balance' => 35000]],
                ],
            ],
            [
                'fund' => ['shares' => 100000, 'value' => 1000],
                'trans' => [
                    ['value' => 100, 'balance' => 10000],
                    ['value' => 100, 'balance' => 20000],
                ],
            ],
        ];

        foreach ($data as $d) {
            DB::beginTransaction();
            $this->_test_create_transaction($d);
            DB::rollBack();
        }
    }

    public function _test_create_transaction($data)
    {
        $factory = new DataFactory();

        $fund = $factory->createFund($data['fund']['shares'], $data['fund']['value']);
        $user = $factory->createUser();
        if ($data['match']) {
            $matching = $factory->createMatching($data['match']['limit'], $data['match']['match']);
        }
        print_r(json_encode($fund->toArray())."\n");
        print_r(json_encode($user->toArray())."\n");
        print_r(json_encode($factory->userAccount->toArray())."\n");
        print_r(json_encode($matching->toArray())."\n");
        print_r(json_encode($matching->accountMatchingRules()->first()->toArray())."\n");

        foreach($data['trans'] as $dtran) {
            $transaction = $factory->makeTransaction($dtran['value']);
            $this->postTransaction($transaction);

            $response = json_decode($this->response->getContent(), true);
            $rdata = $response['data'];
            $tran = Transaction::find($rdata['id']);
            $this->assertNotNull($tran);

            print_r("\n");
            print_r(json_encode($tran->toArray())."\n");
            $hasMatching = array_key_exists('match', $dtran);
            $this->validateTransaction($tran, $transaction, $dtran['value'], $dtran['balance']);

            if ($hasMatching) {
                $tranMatch = $tran->referenceTransactionMatching()->first();
                print_r(json_encode($tranMatch->toArray())."\n");
                $matchTran = $tranMatch->transaction()->first();
                print_r(json_encode($matchTran->toArray())."\n");
                $this->validateTransaction($matchTran, $transaction, $dtran['match']['value'], $dtran['match']['balance'], $hasMatching);
            }

            // calculate shares & balances
            $this->validateBalance($tran, $transaction, $dtran['value'], $dtran['balance']);
            $this->validateBalance($matchTran, $transaction, $dtran['match']['value'], $dtran['match']['balance']);
        }
    }

    public function _test_create_fund_transaction($data) {
        $data = [
            [
                'fund' => ['shares' => 100000, 'value' => 1000],
                'trans' => [
                    ['value' => 100, 'balance' => 110000],
                    ['value' => 100, 'balance' => 120000],
                ],
            ],
            [
                'fund' => ['shares' => 100000, 'value' => 1000],
                'trans' => [
                    ['value' => 100, 'balance' => 10000],
                    ['value' => 100, 'balance' => 20000],
                ],
            ],
        ];

        // create transactions towards the fund account (not user account)
        // NO matching, source SPO, type DEP
        // increase balance
        // increase CASH position
    }

}
