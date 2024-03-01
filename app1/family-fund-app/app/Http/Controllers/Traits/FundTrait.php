<?php

namespace App\Http\Controllers\Traits;

use App\Http\Controllers\APIv1\PortfolioAPIControllerExt;
use App\Http\Controllers\WebV1\AccountControllerExt;
use App\Http\Resources\FundResource;
use App\Jobs\SendAccountReport;
use App\Jobs\SendFundReport;
use App\Mail\FundQuarterlyReport;
use App\Models\AccountExt;
use App\Models\AccountReport;
use App\Models\AssetExt;
use App\Models\FundExt;
use App\Models\FundReportExt;
use App\Models\PortfolioAsset;
use App\Models\PortfolioExt;
use App\Models\TradePortfolioExt;
use App\Models\User;
use App\Models\Utils;
use App\Repositories\AccountRepository;
use App\Repositories\PortfolioRepository;
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
        $arr['share_value'] = Utils::currency($shares ? $value / $shares : 0);

        $prevYearAsOf = Utils::asOfAddYear($asOf, -1);
        $arr['max_cash_value'] = $fund->portfolio()->maxCashBetween($prevYearAsOf, $asOf);

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
        $isAll = $fundReport->type === 'ALL';

        $this->createAccountReports($fundReport);
        $this->sendFundEmailReport($fundReport);

        return $fundReport;
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

    protected function fundEmailReport($fundReport, FundPDF $pdf): void
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
                if ($this->verbose) Log::debug("pdfFile: " . json_encode($pdfFile) . "\n");
                if ($this->verbose) Log::debug("fund: " . json_encode($fund) . "\n");
                $user = $account->user()->first();
                $reportData = new FundQuarterlyReport($fund, $user, $asOf, $pdfFile);

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

    protected function sendFundEmailReport($fundReport): void
    {
        $fund = $fundReport->fund()->first();
        $asOf = $fundReport->as_of->format('Y-m-d');
        $isAdmin = $fundReport->isAdmin();

        $arr = $this->createFullFundResponse($fund, $asOf, $isAdmin);
        $pdf = new FundPDF($arr, $isAdmin);

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
        foreach ($portfolio->portfolioAssets()->get() as $pa) {
            /** @var AssetExt $asset */
            $asset = $pa->asset()->first();

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

            if (!isset($assetPerf[$group])) {
                $assetPerf[$group] = [];
                $assetPerf[$group]['SP500'] = $this->createAssetMonthlyPerformanceResponse(AssetExt::getSP500Asset(), $asOf, $transactions);
            }
            $assetPerf[$group][$asset->name] = $perf;
        }
        return $assetPerf;
    }
}
