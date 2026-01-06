<?php

namespace App\Http\Controllers\Traits;

use App\Http\Resources\AccountResource;
use App\Mail\AccountQuarterlyReport;
use App\Models\AccountExt;
use App\Models\AssetExt;
use App\Models\Utils;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\GoalExt;
trait AccountTrait
{
    use PerformanceTrait, VerboseTrait, MailTrait;

    public function createAccountArray($account)
    {
        $this->perfObject = $account;

        $arr = array();
        $arr['nickname'] = $account->nickname;
        $arr['id'] = $account->id;
        return $arr;
    }

    public function createAccountResponse(AccountExt $account, $asOf)
    {
        $this->perfObject = $account;

        $fund = $account->fund;
        $shareValue = $fund->shareValueAsOf($asOf);
        $accountBalance = $account->allSharesAsOf($asOf);
        foreach ($accountBalance as $balance) {
            $balance->market_value = Utils::currency($shareValue * $balance->shares);
        }
        $account->balances = $accountBalance;
        return $account;
    }

    public function createTransactionsResponse($account, $asOf)
    {
        // TODO: move this to a more appropriate place: model? AB controller?

        $fund = $account->fund;
        $shareValue = $fund->shareValueAsOf($asOf);

        $transactions = $account->transactions;
        $arr = array();
        foreach ($transactions as $transaction) {
            if ($transaction->timestamp->gte(Carbon::createFromFormat('Y-m-d', $asOf)))
                continue;
            $value = $transaction->value;
            $transaction->share_price = Utils::currency($transaction->shares ? $transaction->value / $transaction->shares : 0);
            $transaction->current_value = Utils::currency($current = $transaction->shares * $shareValue);
            $transaction->current_performance = Utils::percent($current/$value - 1);
            $transaction->balance?->id;

            $matching = $transaction->transactionMatching;
            if ($matching) {
                $this->debug('tran id: ' . $transaction->id . ' matching id: ' . $matching->id);
                $transaction->current_value = Utils::currency(0);
                $transaction->current_performance = Utils::percent(0);
            }

            $refMatch = $transaction->referenceTransactionMatching;
            if ($refMatch) {
                $this->debug('tran id: ' . $transaction->id . ' ref matching id: ' . $refMatch->id);
                $refTrans = $refMatch->transaction;
                $current = ($refTrans->shares + $transaction->shares) * $shareValue;
                $this->debug('ref tran id: ' . $refTrans->id . ' ref shares: ' . $refTrans->shares . ' current: ' . $transaction->shares);
                $this->debug('ref tran id: ' . $refTrans->id . ' current: ' . $current);
                $transaction->current_value = Utils::currency($current);
                $transaction->current_performance = Utils::percent(($current)/$value - 1);
            }

            array_push($arr, $transaction);
        }

        return $arr;
    }

    /**
     * @param mixed $asOf
     * @param $account
     * @return array
     */
    protected function createAccountViewData(mixed $asOf, $account): array
    {
        if ($asOf == null) $asOf = date('Y-m-d');

        $api = $this;//new AccountAPIControllerExt($this->accountRepository);
        $arr = [];
        $arr['account'] = $api->createAccountResponse($account, $asOf);
        $arr['goals'] = $api->createGoalsResponse($account, $asOf);
        $arr['monthly_performance'] = $api->createMonthlyPerformanceResponse($asOf);
        $arr['yearly_performance'] = $api->createYearlyPerformanceResponse($asOf);
        $arr['disbursable'] = $api->createDisbursableResponse($arr, $asOf);
        $arr['transactions'] = $api->createTransactionsResponse($account, $asOf);
        $arr['matching_rules'] = $api->createAccountMatchingResponse($account, $asOf);
        $arr['matching_available'] = $this->getTotalAvailableMatching($arr['matching_rules']);
        $arr['sp500_monthly_performance'] = $api->createAssetMonthlyPerformanceResponse(AssetExt::getSP500Asset(), $asOf, $arr['transactions'], true);
        $arr['cash'] = $api->createCashMonthlyPerformanceResponse($asOf, $arr['transactions']);

        // Add trade portfolios from the fund's portfolio
        $portfolio = $account->fund->portfolios;
        $arr['tradePortfolios'] = $portfolio ? $portfolio->tradePortfolios()->with('tradePortfolioItems')->orderBy('start_dt')->get() : collect();

        $arr['as_of'] = $asOf;
        return $arr;
    }

    protected function getGoalPct($value, $start, $target, $pct)
    {
        return [
            'value' => $value,
            'value_4pct' => $value * $pct,
            'final_value' => $target,
            'final_value_4pct' => $target * $pct,
            'completed_pct' => min(100, (($value - $start) / ($target - $start)) * 100),
        ];
    }
    protected function createGoalsResponse(AccountExt $account, $asOf)
    {
        $goals = $account->goals;
        foreach ($goals as $goal) {
            $goal->as_of = $asOf;
            $start_value = $account->valueAsOf($goal->start_dt);
            
            $targetValue = 0;
            if ($goal->target_type == GoalExt::TARGET_TYPE_TOTAL) {
                $targetValue = $goal->target_amount;
            } elseif ($goal->target_type == GoalExt::TARGET_TYPE_4PCT) {
                $targetValue = $goal->target_amount / $goal->target_pct;
            }
            $value = $account->balances['OWN']->market_value;
            $totalDays = $goal->start_dt->diffInDays($goal->end_dt);
            $currentDays = $goal->start_dt->diffInDays(Carbon::now());
            
            $valuePerDay = max(0, ($targetValue - $start_value)) / $totalDays;
            $expectedValue = $start_value + ($valuePerDay * $currentDays);
            // Log::debug(json_encode([$goal->id, $goal->target_type, $value, $targetValue, $goal->target_pct, $start_value, $totalDays, $currentDays, $valuePerDay, $expectedValue]));

            $g = [];
            $g['period'] = [$currentDays, $totalDays, ($currentDays / $totalDays) * 100.0];
            $g['start_value'] = $this->getGoalPct($start_value, $start_value, $targetValue, $goal->target_pct);
            $g['current'] = $this->getGoalPct($value, $start_value, $targetValue, $goal->target_pct);
            $g['expected'] = $this->getGoalPct($expectedValue, $start_value, $targetValue, $goal->target_pct);
            $goal->progress = $g;

        }

        return $goals;
    }

    protected function createAccountMatchingResponse(AccountExt $account, $asOf): array
    {
        $this->debug("amresp: " . json_encode($account) . " " . $asOf);
        $tsAsOf = (new Carbon($asOf))->getTimestamp();
        $arr = [];
        foreach ($account->accountMatchingRules()->get() as $amr) {
            // TODO: filter deleted / ignore pending trans
            foreach ($amr->matchingRule()->get() as $mr) {
                if ($this->verbose) print_r("mr: " . $mr->name . "\n");
                if ($tsAsOf >= $mr->date_start->getTimestamp()) {
                    $mrA = $mr->toArray();
                    $mrA['available'] = 0;
                    $range = $mr->dollar_range_end - $mr->dollar_range_start;
                    $granted = $amr->getMatchGrantedAsOf(new Carbon($asOf)); // may be more than range with a big tran
                    $mrA['granted'] = $granted;
                    $considered = $amr->getMatchConsideredAsOf(new Carbon($asOf)); // may be more than range with a big tran
                    $mrA['used'] = min($considered, $range);

                    if ($tsAsOf < $mr->date_end->getTimestamp()) { // dont remove old matchings
                        $mrA['available'] = $range - $mrA['used'];
                    }

                    $mrA['date_start'] = substr($mrA['date_start'], 0, 10);
                    $mrA['date_end'] = substr($mrA['date_end'], 0, 10);
                    unset($mrA['transaction_matchings']);
                    $arr[] = $mrA;
                }
            }
        }
        return $arr;
    }

    protected function getTotalAvailableMatching($arr) {
        $available = 0;
        foreach ($arr as $mr) {
            $available += $mr['available'];
        }
        return $available;
    }

    public function sendAccountReport($accountReport)
    {
        $account = $accountReport->account()->first();
        $account = AccountExt::find($account->id);
        $asOf = $accountReport->as_of->format('Y-m-d');

        $arr = $this->createAccountViewData($asOf, $account);
        $pdf = new AccountPDF($arr, $asOf);

        $this->accountEmailReport($account, $pdf, $asOf);
        return $accountReport;
    }

    protected function accountEmailReport($account, AccountPDF $pdf, $asOf): void
    {
        $err = [];
        $msgs = [];
        $sendCount = 0;
        $msg = $account->validateHasEmail();
        if ($msg != null) {
            $err[] = $msg;
            Log::error($msg);
        } else {
            $msg = "Sending account report email to " . $account->email_cc;
            Log::info($msg);
            $msgs[] = $msg;
            $pdfFile = $pdf->file();
            $this->debug("pdfFile: " . json_encode($pdfFile));
            $this->debug("account: " . json_encode($account));
            $reportData = new AccountQuarterlyReport($account, $asOf, $pdfFile);

            $sentMsg = $this->sendMail($reportData, $account->email_cc);
            if (null == $sentMsg) {
                $sendCount++;
            } else {
                $err[] = $sentMsg;
            }
        }
        if ($sendCount == 0) {
            $msg = "No emails sent";
            Log::error($msg);
            $err[] = $msg;
        }
        $this->err = $err;
        $this->msgs = $msgs;
    }

    protected function createDisbursableResponse($arr, $asOf) {
        $cap = 0.02;
        $year = Carbon::parse($asOf)->startOfYear();
        $yearNow = $year->format('Y-m-d');

        $perf = $arr['yearly_performance'];
//        Log::debug($perf);
        $disb = 0;
        $perfValue = 0;
        if (array_key_exists($yearNow, $perf)) {
            $data = $perf[$yearNow];
//            Log::debug("Found $yearNow " . json_encode($data));
            $value = $data['value'];
            $perfValue = $data['performance'];
            $disb = $value * max(0.0, min($cap, $perfValue));
        }

//        Log::debug("Disb: " . $disb);
        return [
            'year' => $yearNow,
            'performance' => $perfValue,
            'limit' => $cap * 100.0,
            'value' => $disb
        ];
    }
}
