<?php namespace Tests\GoldenData;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Account;
use App\Models\AccountExt;
use App\Models\TransactionExt;
use App\Models\Utils;

use PHPUnit\Framework\Attributes\Test;
class AccountApiGoldenDataTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    public function create_own_balance($account, $asOf, $shares, $value)
    {
        // Get the actual balance from DB to get all fields
        $fund = $account->fund;
        $shareValue = $fund->shareValueAsOf($asOf);
        $balances = $account->allSharesAsOf($asOf);
        $actualBalance = $balances['OWN'] ?? null;

        $balance = array();
        if ($actualBalance) {
            $balance['id'] = $actualBalance->id;
            $balance['type'] = $actualBalance->type;
            $balance['shares'] = $actualBalance->shares;
            $balance['previous_balance_id'] = $actualBalance->previous_balance_id;
            $balance['account_id'] = $actualBalance->account_id;
            $balance['transaction_id'] = $actualBalance->transaction_id;
            $balance['start_dt'] = $actualBalance->start_dt?->format('Y-m-d\TH:i:s.u\Z') ?? null;
            $balance['end_dt'] = $actualBalance->end_dt?->format('Y-m-d\TH:i:s.u\Z') ?? null;
            $balance['created_at'] = $actualBalance->created_at;
            $balance['updated_at'] = $actualBalance->updated_at;
            $balance['market_value'] = Utils::currency($shareValue * $actualBalance->shares);
        } else {
            $balance['type'] = 'OWN';
            $balance['shares'] = $shares;
            $balance['market_value'] = $value;
        }
        return $balance;
    }

    public function create_transaction_expected($account, $tran_id, $asOf)
    {
        // Get the actual transaction from DB to get all fields
        $actualTran = \App\Models\TransactionExt::find($tran_id);
        if (!$actualTran) {
            return null;
        }

        $fund = $account->fund;
        $shareValue = $fund->shareValueAsOf($asOf);

        // Calculate current_value dynamically based on share price
        $currentValue = $actualTran->shares * $shareValue;

        // Start with the base transaction array
        $transaction = $actualTran->toArray();

        // Format timestamp
        $transaction['timestamp'] = $actualTran->timestamp?->format('Y-m-d\TH:i:s.u\Z') ?? null;

        // Add balance relationship if exists
        $balance = $actualTran->balance;
        if ($balance) {
            $balArr = $balance->toArray();
            $balArr['start_dt'] = $balance->start_dt?->format('Y-m-d\TH:i:s.u\Z') ?? null;
            $balArr['end_dt'] = $balance->end_dt?->format('Y-m-d\TH:i:s.u\Z') ?? null;
            $transaction['balance'] = $balArr;
        }

        // Add transaction_matching relationship (null for most)
        $matching = $actualTran->transactionMatching;
        $transaction['transaction_matching'] = $matching ? $matching->toArray() : null;

        // Add reference_transaction_matching relationship with nested transaction
        $refMatch = $actualTran->referenceTransactionMatching;
        if ($refMatch) {
            $refMatchArr = $refMatch->toArray();
            // Include nested transaction if loaded
            if ($refMatch->transaction) {
                $refMatchArr['transaction'] = $refMatch->transaction->toArray();
            }
            $transaction['reference_transaction_matching'] = $refMatchArr;
        } else {
            $transaction['reference_transaction_matching'] = null;
        }

        // Computed fields added by the controller
        $transaction['share_price'] = Utils::currency($actualTran->shares ? $actualTran->value / $actualTran->shares : 0);

        // Handle matching transaction logic (same as controller)
        $matching = $actualTran->transactionMatching;
        if ($matching) {
            $transaction['current_value'] = Utils::currency(0);
            $transaction['current_performance'] = Utils::percent(0);
        } else {
            $refMatch = $actualTran->referenceTransactionMatching;
            if ($refMatch) {
                $refTrans = $refMatch->transaction;
                $currentValue = ($refTrans->shares + $actualTran->shares) * $shareValue;
                $transaction['current_value'] = Utils::currency($currentValue);
                $transaction['current_performance'] = Utils::percent($currentValue / $actualTran->value - 1);
            } else {
                $transaction['current_value'] = Utils::currency($currentValue);
                $transaction['current_performance'] = Utils::percent($currentValue / $actualTran->value - 1);
            }
        }

        if ($actualTran->reference_transaction_id != null) {
            $transaction['reference_transaction'] = $actualTran->reference_transaction_id;
        }

        return $transaction;
    }

    public function _test_account_balance_as_of($id, $asOf, $fund_name, $fund_id, $shares, $balance)
    {
        $account = AccountExt::find(7);

        $this->response = $this->json(
            'GET',
            '/api/accounts/'.$account->id.'/as_of/'.$asOf
        );

        // Build expected data with all Account model fields
        $arr = [
            'id' => $account->id,
            'code' => $account->code,
            'nickname' => $account->nickname,
            'email_cc' => $account->email_cc,
            'disbursement_cap' => $account->disbursement_cap,
            'user_id' => $account->user_id,
            'fund_id' => $account->fund_id,
            'beneficiary_id' => $account->beneficiary_id,
            'created_at' => $account->created_at,
            'updated_at' => $account->updated_at,
            'deleted_at' => $account->deleted_at,
        ];
        $arr['as_of'] = $asOf;
        $arr['balances'] = [
            'OWN' => $this->create_own_balance($account, $asOf, $shares, $balance)
        ];
        $fund = $account->fund;
        $arr['fund'] = [
            'id' => $fund->id,
            'name' => $fund->name,
            'goal' => $fund->goal,
            'created_at' => $fund->created_at,
            'updated_at' => $fund->updated_at,
            'deleted_at' => $fund->deleted_at,
        ];
        $this->assertApiResponse($arr);
    }

    public function _test_account_transactions_as_of($id, $asOf, $transaction_ids)
    {
        $account = AccountExt::find(7);

        $this->response = $this->json(
            'GET',
            '/api/accounts/'.$account->id.'/transactions_as_of/'.$asOf
        );

        $arr = [];
        $arr['nickname'] = $account->nickname;
        $arr['id'] = $account->id;
        $arr['as_of'] = $asOf;
        $arr['transactions'] = [];
        foreach ($transaction_ids as $tran_id) {
            $expected = $this->create_transaction_expected($account, $tran_id, $asOf);
            if ($expected) {
                $arr['transactions'][] = $expected;
            }
        }
        $this->assertApiResponse($arr);
    }

    public function compareCurrency($a, $b)
    {
        if ($this->verbose) print_r("comp float: " . json_encode([$a, $b]));
        return abs($a - $b) < 0.02;
    }

    #[Test]
    public function test_account_balance_as_of()
    {
        // $this->verbose = true;
        $balance = [2000.56, 2970.39, 3513.27, 3486.17];
        $this->_test_account_balance_as_of(7, '2021-01-02', "Fidelity Fund", 2, 2000, $balance[0]);
        $this->_test_account_balance_as_of(7, '2021-07-02', "Fidelity Fund", 2, 2264.6098, $balance[1]);
        $this->_test_account_balance_as_of(7, '2022-01-02', "Fidelity Fund", 2, 2264.6098, $balance[2]);
        $this->_test_account_balance_as_of(7, '2022-01-16', "Fidelity Fund", 2, 2561.0068, $balance[3]);
    }

    #[Test]
    public function test_account_transactions_as_of()
    {
        // Transaction IDs for account 7
        // Test with different as_of dates to verify correct transactions are returned
        $this->_test_account_transactions_as_of(7, '2021-01-01', [46]);
        $this->_test_account_transactions_as_of(7, '2021-07-01', [46, 42, 44]);
        $this->_test_account_transactions_as_of(7, '2022-01-01', [46, 42, 44]);
        $this->_test_account_transactions_as_of(7, '2022-01-15', [46, 42, 44, 20, 21, 22, 23]);
    }

    // TODO test account performance
}
