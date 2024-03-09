<?php namespace Tests\GoldenData;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Log;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\Transaction;
use App\Models\AccountBalance;
use App\Models\Utils;

class TransactionApiGoldenDataTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verbose = false;
    }

    /**
     * @test
     */
    public function test_transaction_share_value()
    {
        $fixValues = true;

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

            if ($this->verbose) print_r("TRAN: " . json_encode($transaction->toArray()) . "\n");

            $account = $transaction->account()->first();
            $fund = $account->fund()->first();
            $asOf = substr($transaction->created_at,0,10);
            $shareValue = Utils::currency($fund->shareValueAsOf($asOf));
            $totalShares = Utils::shares($fund->sharesAsOf($asOf));

            if ($this->verbose) {
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
            $assets = $portfolio->valueAsOf($asOf, $this->verbose);
            if ($transaction->type != TransactionExt::TYPE_INITIAL) {
                if ($fixValues) {
                    if ($shareValue > 0) {
                        if (Utils::shares($transaction->value / $shareValue) != $transaction->shares) {
                            $this->fixTransaction($transaction, $shareValue);
                            continue;
                        }
                    }
                    if ($transaction->value != Utils::currency($shareValue * $transaction->shares)) {
                        $this->fixTransaction($transaction, $shareValue);
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
                if ($this->verbose) {
                    Log::debug("bo " . implode(', ', $bals[$key]));
                    Log::debug("bn " . implode(', ', $a));
                }
                $old_shares = $bals[$key][4];
                if ($bal_type == 'OWN') {
                    if (! ($tran_type == TransactionExt::TYPE_PURCHASE || $tran_type == 'REP' || $tran_type == 'MAT')) {
                        $tran_shares *= -1;
                    }
                } elseif ($bal_type == 'BOR') {
                    if ($tran_type == TransactionExt::TYPE_PURCHASE || $tran_type == 'REP' || $tran_type == 'MAT') {
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

    private function fixTransaction(mixed $transaction, float $shareValue): void
    {
        print_r("FIX: " . json_encode([
                $transaction->id,
                $transaction->account_id,
                Utils::shares($transaction->value / $shareValue),
                $transaction->value,
                $shareValue,
                $transaction->shares,
            ]) . "\n");
    }
}
