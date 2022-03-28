<?php

namespace App\Http\Controllers\Traits;

use App\Http\Resources\AccountResource;
use App\Mail\AccountQuarterlyReport;
use App\Models\AccountExt;
use App\Models\Utils;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Nette\Utils\DateTime;

trait AccountTrait
{
    use PerformanceTrait;
    protected $verbose=false;

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
            if ($transaction->created_at->gte(Carbon::createFromFormat('Y-m-d', $asOf)))
                continue;
            $tran['id']     = $transaction->id;
            $tran['type']   = $transaction->type;
            $tran['shares'] = Utils::shares($transaction->shares);
            $tran['value']  = Utils::currency($value = $transaction->value);
            $tran['share_price'] = Utils::currency($transaction->shares ? $transaction->value / $transaction->shares : 0);
//            $tran['calculated_share_price'] = Utils::currency($fund->shareValueAsOf($transaction->timestamp));

            $matching = $transaction->transactionMatching()->first();
            if ($matching) {
                $tran['reference_transaction'] = $matching->referenceTransaction()->first()->id;
            }
            $tran['current_value'] = Utils::currency($current = $transaction->shares * $shareValue);
            $tran['current_performance'] = Utils::percent($current/$value - 1);
            $tran['timestamp'] = $transaction->timestamp;

            $bals = [];
            foreach ($transaction->accountBalances()->get() as $balance) {
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
        $arr['transactions'] = $api->createTransactionsResponse($account, $asOf);
        $arr['matching_rules'] = $api->createAccountMatchingResponse($account, $asOf);
        $arr['matching_available'] = $this->getTotalAvailableMatching($arr['matching_rules']);

        $arr['as_of'] = $asOf;
        return $arr;
    }

    protected function createAccountMatchingResponse($account, $asOf): array
    {
//        $account = AccountExt::find($account->id);
            //$this->accountRepository->with(['accountMatchingRules.matchingRule.transactionMatchings.transaction'])->find($account->id);
//        print_r("data: " . json_encode($account) . "\n");
//        print_r($account);
        $tsAsOf = (new DateTime($asOf))->getTimestamp();
        $arr = [];
        foreach ($account->accountMatchingRules()->get() as $amr) {
            foreach ($amr->matchingRule()->get() as $mr) {
                $used = 0;
                if ($tsAsOf >= $mr->date_start->getTimestamp()) {
                    foreach ($mr->transactionMatchings()->get() as $tm) {
                        foreach ($tm->transaction()->get() as $transaction) {
                            if ($tsAsOf >= $transaction->timestamp->getTimestamp() &&
                                $transaction->account_id == $account->id) {
                                if ($this->verbose)
                                    print_r("vals: " . json_encode([$tsAsOf, $transaction->toArray()]) . "\n");
                                $used += $transaction->value;
                            }
                        }
                    }
                    $mrA = $mr->toArray();
                    $mrA['available'] = 0;
                    $mrA['used'] = $used;

                    if ($tsAsOf < $mr->date_end->getTimestamp()) {
                        $range = $mr->dollar_range_end - $mr->dollar_range_start;
                        $mrA['available'] = ($range * $mr->match_percent / 100.0) - $used;
                    }

                    $mrA['date_start'] = substr($mrA['date_start'],0,10);
                    $mrA['date_end'] = substr($mrA['date_end'],0,10);
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
//        $isAdmin = 'ADM' === $accountReport->type;

        $arr = $this->createAccountViewData($asOf, $account);
        $pdf = new AccountPDF($arr, $asOf);

        $this->emailReport($account, $pdf, $asOf);
        return $accountReport;
    }

    protected function emailReport($account, AccountPDF $pdf, $asOf): void
    {
        $err = [];
        $msgs = [];
        $sendCount = 0;
        if (empty($account->email_cc)) {
            $msg = "Account " . $account->nickname . " has no email configured";
            $err[] = $msg;
            Log::error($msg);
        } else {
            $sendCount++;
            $msg = "Sending email to " . $account->email_cc;
            Log::info($msg);
            $msgs[] = $msg;
            $pdfFile = $pdf->file();
            if ($this->verbose) Log::debug("pdfFile: " . json_encode($pdfFile) . "\n");
            if ($this->verbose) Log::debug("account: " . json_encode($account) . "\n");
            $reportData = new AccountQuarterlyReport($account, $asOf, $pdfFile);
            Mail::to($account->email_cc)->send($reportData);
        }
        if ($sendCount == 0) {
            $msg = "No emails sent";
            Log::error($msg);
            $err[] = $msg;
        }
        $this->err = $err;
        $this->msgs = $msgs;
    }

}
