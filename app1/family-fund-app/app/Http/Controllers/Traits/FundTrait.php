<?php

namespace App\Http\Controllers\Traits;

use App\Http\Controllers\APIv1\PortfolioAPIControllerExt;
use App\Http\Resources\FundResource;
use App\Mail\FundQuarterlyReport;
use App\Models\FundExt;
use App\Models\User;
use App\Models\Utils;
use App\Repositories\PortfolioRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

Trait FundTrait
{
    use PerformanceTrait;
    protected $err = [];
    protected $msgs = [];

    public function createAccountBalancesResponse(FundExt $fund, $asOf)
    {
        $bals = array();
        $sharePrice = $fund->shareValueAsOf($asOf);
        foreach ($fund->accountBalancesAsOf($asOf) as $balance) {
            $account = $balance->account()->first();
            $user = $account->user()->first();

            $bal = array();
            if ($user) {
                $bal['user'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            } else {
                continue;
                // $bal['user'] = [
                //     'id' => 0,
                //     'name' => 'N/A',
                // ];
            }
            $bal['account_id'] = $account->id;
            $bal['nickname'] = $account->nickname;
            $bal['type'] = $balance->type;
            $bal['shares'] = Utils::shares($balance->shares);
            $bal['value'] = Utils::currency($sharePrice * $balance->shares);
            $bals[] = $bal;
        }
        return $bals;
    }

    public function createFundArray($fund, $asOf)
    {
        $this->setPerfObject($fund);

        $arr = array();
        $arr['id'] = $fund->id;
        $arr['name'] = $fund->name;
        $arr['as_of'] = $asOf;

        return $arr;
    }

    public function createFundResponse($fund, $asOf)
    {
        $this->setPerfObject($fund);
        $rss = new FundResource($fund);
        $ret = $rss->toArray(NULL);

        $arr = array();
        $arr['value'] = Utils::currency($value = $fund->valueAsOf($asOf));
        $arr['shares'] = Utils::shares($shares = $fund->sharesAsOf($asOf));
        $arr['unallocated_shares'] = Utils::shares($unallocated = $fund->unallocatedShares($asOf));
        $arr['unallocated_shares_percent'] = Utils::percent($shares ? $unallocated / $shares : 0);
        $arr['allocated_shares'] = Utils::shares($allocated = $shares - $unallocated);
        $arr['allocated_shares_percent'] = Utils::percent($shares ? $allocated / $shares : 0);
        $arr['share_value'] = Utils::currency($shares ? $value / $shares : 0);
        $ret['summary'] = $arr;
        $ret['as_of'] = $asOf;

        return $ret;
    }

    public function isAdmin() {
        /** @var User $user */
        $user = Auth::user();
        if ($user != null) {
            return $user->email == "admin@dev.familyfund.local";
        }
        return false;
    }

    public function createFullFundResponse($fund, $asOf, $isAdmin = false) {
        if ($asOf == null) $asOf = date('Y-m-d');

        $api = $this;
        $arr = $api->createFundResponse($fund, $asOf);
        $arr['monthly_performance'] = $api->createMonthlyPerformanceResponse($asOf);
        $arr['yearly_performance'] = $api->createYearlyPerformanceResponse($asOf);
        if ($isAdmin) {
            $arr['admin'] = true;
            $arr['balances'] = $api->createAccountBalancesResponse($fund, $asOf);
        }
//        $arr['transactions'] = $api->createTransactionsResponse($account, $asOf);

        $portController = new PortfolioAPIControllerExt(\App::make(PortfolioRepository::class));
        $portfolio = $fund->portfolios()->first();
        $arr['portfolio'] = $portController->createPortfolioResponse($portfolio, $asOf);

        return $arr;
    }

    public function sendFundReport($fundReport)
    {
        $fund = $fundReport->fund()->first();
        $asOf = $fundReport->end_dt->format('Y-m-d');
        $isAdmin = 'ADM' === $fundReport->type;

        $arr = $this->createFullFundResponse($fund, $asOf, $isAdmin);
        $pdf = new FundPDF($arr, $isAdmin);

        $this->emailReport($fund, $isAdmin, $pdf, $asOf);
        return $fundReport;
    }

    protected function emailReport($fund, bool $isAdmin, FundPDF $pdf, $asOf): void
    {
        $err = [];
        $msgs = [];
        $sendCount = 0;
        $accounts = $fund->accounts()->get();
        foreach ($accounts as $account) {
            $user = $account->user()->get();
            if (($isAdmin && count($user) == 0) ||
                (!$isAdmin && count($user) == 1)
            ) {
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
                    if ($this->verbose) Log::debug("fund: " . json_encode($fund) . "\n");
                    $reportData = new FundQuarterlyReport($fund, $asOf, $pdfFile);
                    Mail::to($account->email_cc)->send($reportData);
                }
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

}
