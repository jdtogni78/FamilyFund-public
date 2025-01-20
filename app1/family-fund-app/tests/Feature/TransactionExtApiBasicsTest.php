<?php

namespace Tests\Feature;

use App\Mail\TransactionEmail;
use App\Models\TransactionExt;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Transaction;
use App\Models\TransactionMatching;
use DB;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;

class TransactionExtApiBasicsTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    public function testBasics()
    {
        $factory = new DataFactory();

        $factory->createFundWithMatching();

        $mr = $factory->matchingRule;
        $tranMatching = $mr->transactionMatchings()->first();
        $matchTran = $factory->matchTransaction;

        $tran = $tranMatching->transaction()->first();
        $account = $tran->account()->first();
        $amr = $account->accountMatchingRules()->first();

        $this->assertNotNull($tran);
        $this->assertNotNull($tranMatching);
        $this->assertNotNull($tranMatching->referenceTransaction()->first());
        $this->assertNotNull($mr);
        $this->assertNotNull($amr);
        $this->assertNotNull($matchTran->transactionMatching()->first());
    }

    public function postTransaction($transaction, $shares = 1000, $status = TransactionExt::STATUS_CLEARED)
    {
        $tranArr = $transaction->toArray();

        $url = '/api/transactions';
        if ($this->verbose) Log::debug("*** $url " .json_encode($tranArr));
        Mail::fake();
        $this->response = $this->json(
            'POST',
            $url, $tranArr
        );
        Mail::assertSent(TransactionEmail::class);
        if ($this->verbose) Log::debug($this->response->getContent());
        $tranArr['status'] = $status;
        $tranArr['shares'] = $shares;

        $this->assertApiResponse($tranArr, ['id', 'updated_at', 'created_at', 'deleted_at']);
    }

    public function validateTransaction($tran, $value, $balance, $hasMatching=false)
    {
        $this->assertEquals($value, $tran->value);
        $this->assertEquals($tran->transactionMatching()->count(), $hasMatching?1:0);
    }

    public function validateBalance($tran, $balance)
    {
        $bal = $tran->accountBalance()->first();
        $this->assertNotNull($bal);
        if ($this->verbose) Log::debug($bal->toArray());
        $this->assertEquals($bal->shares, $balance);
    }


    public function _test_create_transaction($data)
    {
        $factory = new DataFactory();

        $fund = $factory->createFund($data['fund']['shares'], $data['fund']['value']);
        $user = $factory->createUser();
        if (array_key_exists('match', $data)) {
            $matching = $factory->createMatching($data['match']['limit'], $data['match']['match']);
        }

        foreach($data['trans'] as $dtran) {
            if ($this->verbose) Log::debug("*** " .json_encode($dtran));
            $timestamp = Carbon::now()->subDays(1);
            $transaction = $factory->makeTransaction($dtran['value'], null, TransactionExt::TYPE_PURCHASE, 'P', null, $timestamp);
            $this->postTransaction($transaction, $dtran['shares']);

            $response = json_decode($this->response->getContent(), true);
            $rdata = $response['data'];
            $tran = Transaction::find($rdata['id']);
            $this->assertNotNull($tran);

            $hasMatching = array_key_exists('match', $dtran);
            $this->validateTransaction($tran, $dtran['value'], $dtran['balance']);

            if ($hasMatching) {
                $tranMatch = $tran->referenceTransactionMatching()->first();
                if ($this->verbose) Log::debug("TRAN: " . json_encode($tranMatch->toArray()));
                $matchTran = $tranMatch->transaction()->first();
                if ($this->verbose) Log::debug("MATCHTRAN:" . json_encode($matchTran->toArray()));
                $this->validateTransaction($matchTran, $dtran['match']['value'], $dtran['match']['balance'], $hasMatching);
                $this->validateBalance($matchTran, $dtran['match']['balance']);
            }

            // calculate shares & balances
            $this->validateBalance($tran, $dtran['balance']);
        }
    }

    public function test_transactions()
    {
        $data = [
            [
                'fund' => ['shares' => 100000, 'value' => 1000],
                'match' => ['limit' => 150, 'match' => 100],
                'trans' => [
                    ['value' => 100, 'balance' => 10000, 'shares' => 10000.0000, 'match' => ['value' => 100, 'balance' => 20000]],
                    ['value' => 100, 'balance' => 30000, 'shares' => 10000.0000, 'match' => ['value' =>  50, 'balance' => 35000]],
                ],
            ],
            [
                'fund' => ['shares' => 100000, 'value' => 1000],
                'trans' => [
                    ['value' => 100, 'balance' => 10000, 'shares' => 10000.0000],
                    ['value' => 100, 'balance' => 20000, 'shares' => 10000.0000],
                ],
            ],
        ];

        foreach ($data as $d) {
            DB::beginTransaction();
            $this->_test_create_transaction($d);
            DB::rollBack();
        }
    }
}
