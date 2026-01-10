<?php

namespace App\Http\Controllers\Traits;

use App\Http\Controllers\APIv1\PortfolioAPIControllerExt;
use App\Http\Controllers\WebV1\AccountControllerExt;
use App\Http\Resources\FundReportResource;
use App\Http\Resources\FundResource;
use App\Jobs\SendAccountReport;
use App\Jobs\SendFundReport;
use App\Mail\FundReportEmail;
use App\Models\AccountExt;
use App\Models\AccountReport;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use App\Models\FundExt;
use App\Models\FundReportExt;
use App\Models\PortfolioAsset;
use App\Models\PortfolioExt;
use App\Models\ScheduledJob;
use App\Models\TradePortfolioExt;
use App\Models\User;
use App\Models\Utils;
use App\Repositories\AccountRepository;
use App\Repositories\PortfolioRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

Trait FundTrait
{
    use PerformanceTrait, MailTrait;
    protected $err = [];
    protected $msgs = [];
    private $noEmailMessage = "The following accounts have no email: ";

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
        $arr['share_value'] = Utils::currency($sharePrice = $shares ? $value / $shares : 0);
        $arr['unallocated_value'] = Utils::currency($unallocatedValue = $unallocated * $sharePrice);

        $prevYearAsOf = Utils::asOfAddYear($asOf, -1);
        $arr['max_cash_value'] = $fund->portfolio()->maxCashBetween($prevYearAsOf, $asOf);

        $ret['summary'] = $arr;
        $ret['as_of'] = $asOf;

        return $ret;
    }

    public function isAdmin() {
        // Allow forcing non-admin view with ?admin=0
        if (request()->has('admin') && request()->get('admin') === '0') {
            return false;
        }

        /** @var User $user */
        $user = Auth::user();
        if ($user != null) {
            return in_array($user->email, [
                "admin@dev.familyfund.local",
                "claude@test.local",
            ]);
        }
        return false;
    }

    public function createFundResponseTradeBands($fund, $asOf, $isAdmin = false, $startDate = null) {
        if ($asOf == null) $asOf = date('Y-m-d');
        $fund = FundExt::find($fund->id);

        $api = $this;
        $arr = $api->createFundResponse($fund, $asOf);
        $accountController = new AccountControllerExt(\App::make(AccountRepository::class));
        $account = $fund->fundAccount();
        $arr['transactions'] = $accountController->createTransactionsResponse($account, $asOf);

        /** @var PortfolioExt $portfolio */
        $portfolio = $fund->portfolios()->first();
        $portController = new PortfolioAPIControllerExt(\App::make(PortfolioRepository::class));
        $arr['portfolio'] = $portController->createPortfolioResponse($portfolio, $asOf);

        $fromDate = $startDate ?? Utils::asOfAddYear($asOf, -5);
        $tradePortfolios = $portfolio->tradePortfoliosBetween($fromDate, $asOf);
        $arr['tradePortfolios'] = $tradePortfolios;

        $assetPerf = $this->createMonthlyAssetBandsResponse($fund, $asOf, $arr);
        $arr['asset_monthly_bands'] = $assetPerf;

        /** @var TradePortfolioExt $tradePortfolio */
        foreach ($tradePortfolios as $tradePortfolio) {
            $items = $tradePortfolio->tradePortfolioItems()->get();
            $tradePortfolio->items = $items;
            $tradePortfolio->annotateAssetsAndGroups();
            $tradePortfolio->annotateTotalShares();
        }

        $arr['asOf'] = $asOf;
        return $arr;
    }


    public function createFullFundResponse($fund, $asOf, $isAdmin = false) {
        if ($asOf == null) $asOf = date('Y-m-d');
        $fund = FundExt::find($fund->id);

        $api = $this;
        $arr = $api->createFundResponse($fund, $asOf);
        $arr['monthly_performance'] = $api->createMonthlyPerformanceResponse($asOf);
        $arr['yearly_performance'] = $api->createYearlyPerformanceResponse($asOf);
        if ($isAdmin) {
            $arr['admin'] = true;
            $arr['balances'] = $api->createAccountBalancesResponse($fund, $asOf);
        }

        $accountController = new AccountControllerExt(\App::make(AccountRepository::class));
        $account = $fund->fundAccount();
        $arr['transactions'] = $accountController->createTransactionsResponse($account, $asOf);

        $arr['sp500_monthly_performance'] = $this->createAssetMonthlyPerformanceResponse(AssetExt::getSP500Asset(), $asOf, $arr['transactions'], true);
        $arr['cash'] = $this->createCashMonthlyPerformanceResponse($asOf, $arr['transactions']);


        $portController = new PortfolioAPIControllerExt(\App::make(PortfolioRepository::class));
        /** @var PortfolioExt $portfolio */
        $portfolio = $fund->portfolios()->first();
        $arr['portfolio'] = $portController->createPortfolioResponse($portfolio, $asOf);

        $yearAgo = Utils::asOfAddYear($asOf, -1);
        $tradePortfolios = $portfolio->tradePortfoliosBetween($yearAgo, $asOf);
        $arr['tradePortfolios'] = $tradePortfolios;

        $assetPerf = $this->createGroupMonthlyPerformanceResponse($fund, $asOf, $arr);
        $arr['asset_monthly_performance'] = $assetPerf;

        // create a linear regression projection for the next 10 years
        $arr['linear_regression'] = $this->createLinearRegressionResponse($arr['monthly_performance'], $asOf);

        /** @var TradePortfolioExt $tradePortfolio */
        foreach ($tradePortfolios as $tradePortfolio) {
            $items = $tradePortfolio->tradePortfolioItems()->get();
            $tradePortfolio->items = $items;
            $tradePortfolio->annotateAssetsAndGroups();
            $tradePortfolio->annotateTotalShares();
        }

        $arr['asOf'] = $asOf;
        return $arr;
    }

    public function sendFundReport($fundReport)
    {
        // ignore if as_of is high date
        $isHighDate = $fundReport->as_of->format('Y-m-d') == '9999-12-31';
        $isAll = $fundReport->type === FundReportExt::TYPE_ALL;
        $isTradingBands = $fundReport->type === FundReportExt::TYPE_TRADING_BANDS;
        if ($isTradingBands) {
            $this->sendTradingBandsEmailReport($fundReport);
        } else {
            $this->createAccountReports($fundReport);
            $this->sendFundEmailReport($fundReport);
        }   

        return $fundReport;
    }

    protected function sendTradingBandsEmailReport(FundReportExt $fundReport){
        $fund = $fundReport->fund()->first();
        $asOf = $fundReport->as_of->format('Y-m-d');
        $isAdmin = $fundReport->isAdmin();

        $arr = $this->createFundResponseTradeBands($fund, $asOf, $isAdmin);
        $pdf = new FundPDF();
        $pdf->createTradeBandsPDF($arr, $isAdmin);
        $this->fundEmailReport($fundReport, $pdf);
    }

    protected function createAccountReports($fundReport)
    {
        $fund = $fundReport->fund()->first();
        Log::info("sending report to all ".$fundReport->type);
        $isAll = $fundReport->type === 'ALL';
        if ($isAll) {
            $accounts = $fund->accounts()->get();
            foreach ($accounts as $account) {
                $users = $account->user()->get();
                Log::info("* sending report to acct ".$account->nickname);
                if (count($users) == 1) {
                    $accountReport = AccountReport::create([
                        'account_id' => $account->id,
                        'type' => $fundReport->type,
                        'as_of' => $fundReport->as_of
                    ]);
                    SendAccountReport::dispatch($accountReport);
                }
            }
        } else {
            Log::warning("No account reports sent for report type ".$fundReport->type);
        }
    }

    public function reportUsers(FundExt $fund, bool $isAdmin): array
    {
        $ret = [];
        $accounts = $fund->accounts()->get();
        /** @var AccountExt $account */
        foreach ($accounts as $account) {
            $users = $account->user()->get();
            if (($isAdmin && count($users) == 0) ||
                (!$isAdmin && count($users) == 1)
            ) {
                $ret[] = $account;
            }
        }
        return $ret;
    }

    /**
     * @throws Exception
     */
    public function validateReportEmails(FundReportExt $fundReport)
    {
        /** @var FundExt $fund */
        $fund = $fundReport->fund()->first();
        $isAdmin = $fundReport->isAdmin();
        $noEmail = [];
        $accounts = $this->reportUsers($fund, $isAdmin);
        /** @var AccountExt $account */
        foreach ($accounts as $account) {
            $err = $account->validateHasEmail();
            if ($err) $noEmail[] = $err;
        }
        // the fund account has no email
        if (count($noEmail) > 0)
            throw new Exception($this->noEmailMessage . implode(", ", $noEmail));
    }

    protected function fundEmailReport(FundReportExt $fundReport, FundPDF $pdf): void
    {
        $fund = $fundReport->fund()->first();
        $asOf = $fundReport->as_of->format('Y-m-d');
        $isAdmin = $fundReport->isAdmin();

        $errs = [];
        $noEmail = [];
        $msgs = [];
        $sendCount = 0;
        $accounts = $this->reportUsers($fund, $isAdmin);
        foreach ($accounts as $account) {
            $err = $account->validateHasEmail();
            if ($err != null) {
                $noEmail[] = $err;
            } else {
                $sendCount++;
                $msg = "Sending fund report email to " . $account->email_cc;
                Log::info($msg);
                $msgs[] = $msg;
                $pdfFile = $pdf->file();
                if ($this->verbose) Log::debug("pdfFile: " . json_encode($pdfFile));
                if ($this->verbose) Log::debug("fund: " . json_encode($fund));
                $user = $account->user()->first();
                $reportData = new FundReportEmail($fundReport, $user, $asOf, $pdfFile);

                $sentMsg = $this->sendMail($reportData, $account->email_cc);
                if (null == $sentMsg) {
                    $sendCount++;
                } else {
                    $errs[] = $sentMsg;
                }
            }
        }
        if (count($noEmail)) {
            $errs[] = $this->noEmailMessage . implode(", ", $noEmail);
        }
        if ($sendCount == 0) {
            $msg = "No emails sent";
            Log::error($msg);
            $errs[] = $msg;
        }
        $this->err = $errs;
        $this->msgs = $msgs;
    }

    protected function createFundReport(array $input)
    {
        $fundReport = FundReportExt::create($input);
        $this->validateReportEmails($fundReport);
        $fundReport->save();
        SendFundReport::dispatch($fundReport);
        return $fundReport;
    }

    protected function createFundReportFromSchedule(mixed $job, $asOf, $shouldRunBy)
    {
        $templateReport = FundReportExt::query()
            ->where('id', $job->entity_id)->first();

        $fundReport = $this->createFundReport([
            'fund_id' => $templateReport->fund_id,
            'type' => $templateReport->type,
            'as_of' => $shouldRunBy,
            'scheduled_job_id' => $job->id,
            'created_at' => $asOf,
        ]);
        Log::info('Created fund report from schedule: ' . json_encode($fundReport));
        return $fundReport;
    }

    protected function fundReportScheduleDue($shouldRunBy, ScheduledJob $job, Carbon $asOf): ?FundReportExt
    {
        $shouldRunByDate = Carbon::parse($shouldRunBy);
        $dayOfWeek = $shouldRunByDate->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        
        // Calculate lookback days based on day of week
        $lookbackDays = 2; // Base lookback for holidays
        // if today, or yesterday or 2 days ago falls on a weekend, add days
        if (in_array($dayOfWeek, [0, 1, 2])) {
            $lookbackDays += 2;
        } elseif ($dayOfWeek == 6) {
            $lookbackDays += 1;
        }
        
        $lookbackDate = $shouldRunByDate->copy()->subDays($lookbackDays);
        $period = $lookbackDate->format('Y-m-d') . ' (' . $lookbackDate->format('l') 
            . ') to ' . $shouldRunByDate->format('Y-m-d') . ' (' . $shouldRunByDate->format('l') . ')';
        Log::info('Checking if got assets between '.$period.' (lookback: '.$lookbackDays.' days)');
        $hasNewAssets = AssetPrice::query()
            ->whereBetween('start_dt', [$lookbackDate, $shouldRunBy])
            ->limit(1)
            ->count();

        if ($hasNewAssets > 0) {
            Log::info('Creating fund report for schedule: ' . $job->id);
            $report = $this->createFundReportFromSchedule($job, $asOf, $shouldRunBy);
            return $report;
        } else {
            $msg = 'No data for fund report schedule ' . $job->id . ' between ' . $period . '. Lookback: ' . $lookbackDays . ' days';
            // if today is past 4 days of the schedule, error
            $diff = $shouldRunByDate->diffInDays($asOf);
            if ($diff > 4) {
                throw new Exception($msg . ' (' . $diff . ' days past due date)');
            } else {
                Log::warning($msg);
            }
        }
        return null;
    }

    protected function sendFundEmailReport($fundReport): void
    {
        $fund = $fundReport->fund()->first();
        $asOf = $fundReport->as_of->format('Y-m-d');
        $isAdmin = $fundReport->isAdmin();

        $arr = $this->createFullFundResponse($fund, $asOf, $isAdmin);
        $pdf = new FundPDF();
        $pdf->createFundPDF($arr, $isAdmin);

        $this->fundEmailReport($fundReport, $pdf);
    }

    private function createGroupMonthlyPerformanceResponse($fund, $asOf, $arr)
    {
        $transactions = $arr['transactions'];
        $tps = $arr['tradePortfolios'];
        $assetNames = ['CASH'];
        // collect all asset names under trade port list
        foreach ($tps as $tp) {
            foreach ($tp->tradePortfolioItems()->get() as $item) {
                $assetNames[] = $item->symbol;
            }
        }

        /** @var PortfolioExt $portfolio */
        $portfolio = $fund->portfolios()->first();

        /** @var PortfolioAsset $pa */
        $assetPerf = [];
        $processed = [];
        foreach ($portfolio->portfolioAssets()->get() as $pa) {
            /** @var AssetExt $asset */
            $asset = $pa->asset()->first();
            if (in_array($asset->name, $processed)) {
                continue;
            }
            $processed[] = $asset->name;

            // skip if asset name is not in curAssets
            if (!in_array($asset->name, $assetNames)) {
                Log::debug("(group) Skip $asset->name");
                continue;
            } else {
                Log::debug("(group) Add $asset->name");
            }
            $group = $asset->display_group;
            if ($asset->type == "CSH") {
                $perf = $this->createCashMonthlyPerformanceResponse($asOf, $transactions);
            } else {
                $perf = $this->createAssetMonthlyPerformanceResponse($asset, $asOf, $transactions);
            }

            // Skip if performance data is empty (no data points to plot)
            if (empty($perf)) {
                Log::debug("(group) Skip $asset->name - no performance data");
                continue;
            }

            if (!isset($assetPerf[$group])) {
                $assetPerf[$group] = [];
                $assetPerf[$group]['SP500'] = $this->createAssetMonthlyPerformanceResponse(AssetExt::getSP500Asset(), $asOf, $transactions);
            }
            $assetPerf[$group][$asset->name] = $perf;
        }
        return $assetPerf;
    }

    // create an array of assets and their historical prices
    // this is used to create the asset bands for the line graph
    // asset bands are highlight the max, min, and target values used to trigger trades
    // this data is the real value of assets (quantity * price) 
    private function createMonthlyAssetBandsResponse($fund, $asOf, $arr)
    {
        /** @var PortfolioExt $portfolio */
        $portfolio = $fund->portfolios()->first();

        /** @var PortfolioAsset $pa */
        $assetPerf = [];
        $processed = [];
        // TODO get unique assets from portfolioAssets
        $uniqueAssets = PortfolioAsset::query()
            ->where('portfolio_id', $portfolio->id)
            ->select('asset_id')
            ->distinct()
            ->get();
        // foreach ($portfolio->portfolioAssets()->get() as $pa) {
        foreach ($uniqueAssets as $pa) {
            /** @var AssetExt $asset */
            $asset = $pa->asset()->first();
            if (in_array($asset->name, $processed)) {
                Log::debug("Skip $asset->name");
                continue;
            } else {
                Log::debug("Add $asset->name");
            }
            $processed[] = $asset->name;

            $allShares = [];
            // loop through the history of the portfolio assets for this asset
            PortfolioAsset::query()
                ->where('portfolio_id', $portfolio->id)
                ->where('asset_id', $asset->id)
                // ->where('end_dt', '<=', $asOf)
                ->orderBy('start_dt', 'asc')
                ->get()
                ->each(function ($pa) use (&$allShares) {
                    $allShares[] = ['timestamp' => $pa->start_dt, 'shares' => $pa->position, 'end_dt' => $pa->end_dt];
                });

            // get last share
            $lastShare = end($allShares);
            if (substr($lastShare['end_dt'],0,10) < '9999-12-31') {
                $allShares[] = ['timestamp' => $lastShare['end_dt'], 'shares' => 0];
            }
            $perf = $this->createMonthlyPerformanceResponseFor($asOf, 'createAssetPeformanceArray', false, $allShares, $asset);

            $assetPerf[$asset->name] = $perf;
        }
        return $assetPerf;
    }

}
