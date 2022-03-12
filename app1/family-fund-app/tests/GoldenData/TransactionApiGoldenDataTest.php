<?php namespace Tests\GoldenData;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Transaction;
use App\Models\AccountBalance;
use App\Models\Utils;

class TransactionApiGoldenDataTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_transaction_share_value()
    {
        $verbose = false;
        $fixValues = false;

        $transactions = Transaction::
            orderBy('created_at')
            ->orderBy('account_id')
            // ->orderBy('matching_rule_id')
            ->get();

        $accts = array();
        foreach($transactions as $transaction) {
            if (!array_key_exists($transaction->account_id, $accts))
                $accts[$transaction->account_id] = 0;
            $accts[$transaction->account_id] += $transaction->shares;

            if ($verbose) print_r(json_encode($transaction->toArray()) . "\n");

            $account = $transaction->account()->first();
            $fund = $account->fund()->first();
            $asOf = substr($transaction->created_at,0,10);
            $shareValue = $fund->shareValueAsOf($asOf);
            $totalShares = $fund->sharesAsOf($asOf);

            if ($verbose) {
                $arr = array();
                $arr['tc'] = $transaction->account()->count();
                $arr['ac'] = $account->fund()->count();
                $arr['id'] = $transaction->id;
                $arr['type'] = $transaction->type;
                $arr['value'] = $transaction->value;
                $arr['shares'] = $transaction->shares;
                $arr['share_value'] = $shareValue;
                $arr['totalShares'] = $totalShares;
                $arr['account_id'] = $account->id;
                $arr['fund_id'] = $fund->id;
                $arr['asOf'] = $asOf;
                print_r($arr);
            }
            
            $portfolio = $fund->portfolio();
            $assets = $portfolio->valueAsOf($asOf, $verbose);
            if ($transaction->type != 'INI') {
                if ($fixValues) {
                    if ($shareValue > 0) {
                        if (Utils::shares($transaction->value / $shareValue) != $transaction->shares) {
                            print_r(json_encode([$transaction->id, Utils::shares($transaction->value / $shareValue)])."\n");
                        }
                    }
                    if ($transaction->value != Utils::currency($shareValue * $transaction->shares)) {
                        print_r(json_encode([$transaction->id, Utils::shares($transaction->value / $shareValue)])."\n");
                    }
                } else {
                    if ($shareValue > 0) {
                        $this->assertEquals(Utils::shares($transaction->value / $shareValue), $transaction->shares);
                    }
                    $this->assertEquals($transaction->value, Utils::currency($shareValue * $transaction->shares));
                }
            }
        }
    }

    /**
     * @test
     */
    public function test_balance_share_value()
    {
        $verbose = false;
        $fixValues = false;

        $balances = AccountBalance::
            orderBy('account_id')
            ->orderBy('start_dt')
            ->orderBy('shares')
            ->get();

        print("\n");
        $bals = array();
        foreach($balances as $balance) {
            $a = array();
            $a[] = $balance->id;
            $a[] = $account_id = $balance->account_id;
            $a[] = $balance->transaction_id;
            $a[] = $bal_type = $balance->type;
            $a[] = $shares = $balance->shares;
            $tran = $balance->transaction()->first();
            $a[] = $tran_type = $tran->type;
            $a[] = $tran_shares = $tran->shares;
            $a[] = substr($balance->start_dt,0,10);
            
            $key = $bal_type . $account_id;
            if (array_key_exists($key, $bals)) {
                if ($verbose) {
                    print("bo " . implode(', ', $bals[$key]) . "\n");
                    print("bn " . implode(', ', $a)."\n");
                }
                $old_shares = $bals[$key][4];
                if ($bal_type == 'OWN') {
                    if (! ($tran_type == 'PUR' || $tran_type == 'REP')) {
                        $tran_shares *= -1;
                    }
                } elseif ($bal_type == 'BOR') {
                    if ($tran_type == 'PUR' || $tran_type == 'REP') {
                        $tran_shares *= -1;
                    }
                }
                if ($fixValues) {
                    if ($shares != $old_shares + $tran_shares) {
                        print_r(json_encode([$balance->id, $old_shares + $tran_shares])."\n");
                    }
                } else {
                    $this->assertTrue(abs($old_shares + $tran_shares - $shares) < 0.001);
                }
            }
            $bals[$key] = $a;
        }
    }
}
