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

    public function createAccountResponse($account, $asOf)
    {
        $this->perfObject = $account;

        $rss = new AccountResource($account);
        $arr = $rss->toArray(NULL);

        $fund = $account->fund()->first();
        $arr['fund'] = [
            'id' => $fund->id,
            'name' => $fund->name,
        ];
        $user = $account->user()->first();
        if ($user) {
            $arr['user'] = [
                'id' => $user->id,
                'name' => $user->name,
            ];
        } else {
            $arr['user'] = [
                'id' => 0,
                'name' => 'N/A',
            ];
        }

        $shareValue = $fund->shareValueAsOf($asOf);
        $accountBalance = $account->allSharesAsOf($asOf);
        $arr['balances'] = array();
        foreach ($accountBalance as $ab) {
            $balance = array();
            $balance['type'] = $ab['type'];
            $balance['shares'] = Utils::shares($ab['shares']);
            $balance['market_value'] = Utils::currency($shareValue * $ab['shares']);
            array_push($arr['balances'], $balance);
        }

        return $arr;
    }

    public function createTransactionsResponse($account, $asOf)
    {
        // TODO: move this to a more appropriate place: model? AB controller?

        $fund = $account->fund()->first();
        $shareValue = $fund->shareValueAsOf($asOf);

        $transactions = $account->transactions()->get();
        $arr = array();
        foreach ($transactions as $transaction) {
            $tran = array();
            if ($transaction->timestamp->gte(Carbon::createFromFormat('Y-m-d', $asOf)))
                continue;
            $tran['id']     = $transaction->id;
            $tran['type']   = $transaction->type;
            $tran['status'] = $transaction->status;
            $tran['shares'] = Utils::shares($transaction->shares);
            $tran['value']  = Utils::currency($value = $transaction->value);
            $tran['share_price'] = Utils::currency($transaction->shares ? $transaction->value / $transaction->shares : 0);
//            $tran['calculated_share_price'] = Utils::currency($fund->shareValueAsOf($transaction->timestamp));
            $tran['current_value'] = Utils::currency($current = $transaction->shares * $shareValue);
            $tran['current_performance'] = Utils::percent($current/$value - 1);
            $tran['timestamp'] = $transaction->timestamp;

            $matching = $transaction->transactionMatching()->first();
            if ($matching) {
                Log::debug('tran id: ' . $transaction->id . ' matching id: ' . $matching->id);
                $tran['reference_transaction'] = $matching->referenceTransaction()->first()->id;
                $tran['current_value'] = Utils::currency(0);
                $tran['current_performance'] = Utils::percent(0);
            }

            $refMatch = $transaction->referenceTransactionMatching()->first();
            if ($refMatch) {
                Log::debug('tran id: ' . $transaction->id . ' ref matching id: ' . $refMatch->id);
                $refTrans = $refMatch->transaction()->first();
                $current = ($refTrans->shares + $transaction->shares) * $shareValue;
                Log::debug('ref tran id: ' . $refTrans->id . ' ref shares: ' . $refTrans->shares . ' current: ' . $transaction->shares);
                Log::debug('ref tran id: ' . $refTrans->id . ' current: ' . $current);
                $tran['current_value'] = Utils::currency($current);
                $tran['current_performance'] = Utils::percent(($current)/$value - 1);
            }

            $bals = [];
            foreach ($transaction->accountBalance()->get() as $balance) {
                $bals[$balance->type] = $balance->shares;
            }
            $tran['balances'] = $bals;

            array_push($arr, $tran);
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
        $arr = $api->createAccountResponse($account, $asOf);
        $arr['monthly_performance'] = $api->createMonthlyPerformanceResponse($asOf);
        $arr['yearly_performance'] = $api->createYearlyPerformanceResponse($asOf);
        $arr['disbursable'] = $api->createDisbursableResponse($arr, $asOf);
        $arr['transactions'] = $api->createTransactionsResponse($account, $asOf);
        $arr['matching_rules'] = $api->createAccountMatchingResponse($account, $asOf);
        $arr['matching_available'] = $this->getTotalAvailableMatching($arr['matching_rules']);
        $arr['sp500_monthly_performance'] = $api->createAssetMonthlyPerformanceResponse(AssetExt::getSP500Asset(), $asOf, $arr['transactions'], true);
        $arr['cash'] = $api->createCashMonthlyPerformanceResponse($asOf, $arr['transactions']);

        $arr['as_of'] = $asOf;
        return $arr;
    }

    protected function createAccountMatchingResponse($account, $asOf): array
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
