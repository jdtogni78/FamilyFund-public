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
    use PerformanceTrait, MailTrait, DetectsDataIssuesTrait;
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
        $arr['borrowed_shares'] = Utils::shares($borrowed = $fund->borrowedShares($asOf));
        $arr['borrowed_shares_percent'] = Utils::percent($shares ? $borrowed / $shares : 0);
        $arr['borrowed_value'] = Utils::currency($borrowed * $sharePrice);
        $arr['available_unallocated_shares'] = Utils::shares($availableUnallocated = $fund->availableUnallocatedShares($asOf));
        $arr['available_unallocated_shares_percent'] = Utils::percent($shares ? $availableUnallocated / $shares : 0);
        $arr['available_unallocated_value'] = Utils::currency($availableUnallocated * $sharePrice);

        $prevYearAsOf = Utils::asOfAddYear($asOf, -1);
        // Sum max cash across all portfolios
        $maxCash = 0;
        foreach ($fund->portfolios()->get() as $portfolio) {
            $maxCash += $portfolio->maxCashBetween($prevYearAsOf, $asOf);
        }
        $arr['max_cash_value'] = $maxCash;

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
        $arr['transactions'] = $account ? $accountController->createTransactionsResponse($account, $asOf) : [];

        // Handle multiple portfolios - return array of portfolio responses
        $portController = new PortfolioAPIControllerExt(\App::make(PortfolioRepository::class));
        $portfolios = $fund->portfolios()->get();
        $portfolioResponses = [];
        $allTradePortfolios = collect();
        $fromDate = $startDate ?? Utils::asOfAddYear($asOf, -5);

        /** @var PortfolioExt $portfolio */
        foreach ($portfolios as $portfolio) {
            $portfolioResponses[] = $portController->createPortfolioResponse($portfolio, $asOf);
            $tradePortfolios = $portfolio->tradePortfoliosBetween($fromDate, $asOf);
            $allTradePortfolios = $allTradePortfolios->merge($tradePortfolios);
        }

        // For backward compatibility, set portfolio to first one; add all as array
        $arr['portfolio'] = $portfolioResponses[0] ?? null;
        $arr['portfolios'] = $portfolioResponses;
        $arr['tradePortfolios'] = $allTradePortfolios;

        $assetPerf = $this->createMonthlyAssetBandsResponse($fund, $asOf, $arr, $fromDate);
        $arr['asset_monthly_bands'] = $assetPerf;

        /** @var TradePortfolioExt $tradePortfolio */
        foreach ($tradePortfolios as $tradePortfolio) {
            $items = $tradePortfolio->tradePortfolioItems()->get();
            $tradePortfolio->items = $items->toArray();
            $tradePortfolio->annotateAssetsAndGroups();
            $tradePortfolio->annotateTotalShares();
        }

        $arr['fromDate'] = $fromDate;
        $arr['asOf'] = $asOf;

        // Add allocation status for current positions vs targets
        $arr['allocation_status'] = $this->createAllocationStatusArray(
            $assetPerf,
            $tradePortfolios,
            $asOf
        );

        return $arr;
    }

    /**
     * Create allocation status array from asset data and trade portfolios
     *
     * Calculates current allocation percentages vs targets and determines
     * if each symbol is within bounds (ok), under-allocated (under), or over-allocated (over).
     *
     * @param array $assetMonthlyBands The asset_monthly_bands data
     * @param mixed $tradePortfolios Active trade portfolios (Collection or array)
     * @param string $asOf The as-of date
     * @return array Allocation status with symbols and their band status
     */
    protected function createAllocationStatusArray(array $assetMonthlyBands, $tradePortfolios, string $asOf): array
    {
        // Convert to array if Collection
        $tpArray = is_array($tradePortfolios) ? $tradePortfolios : $tradePortfolios->toArray();

        // Get portfolio symbols from trade portfolios
        $portfolioSymbols = collect($tpArray)
            ->flatMap(fn($tp) => collect($tp['items'] ?? [])->pluck('symbol'))
            ->unique()
            ->toArray();

        // Calculate total portfolio value at as_of date
        $totalValue = 0;
        $symbolValues = [];
        foreach ($assetMonthlyBands as $symbol => $data) {
            if ($symbol === 'SP500') continue;

            // Find the closest date <= asOf
            $dates = array_keys($data);
            rsort($dates);
            foreach ($dates as $date) {
                if ($date <= $asOf) {
                    $value = $data[$date]['value'] ?? 0;
                    $totalValue += $value;
                    $symbolValues[$symbol] = $value;
                    break;
                }
            }
        }

        if ($totalValue <= 0) {
            return ['as_of_date' => $asOf, 'total_value' => 0, 'symbols' => []];
        }

        // Find active trade portfolio for asOf date
        $activeTP = null;
        foreach ($tpArray as $tp) {
            $startDt = substr($tp['start_dt'] ?? '', 0, 10);
            $endDt = substr($tp['end_dt'] ?? '', 0, 10);
            if ($startDt <= $asOf && $endDt >= $asOf) {
                $activeTP = $tp;
                break;
            }
        }

        if (!$activeTP) {
            return ['as_of_date' => $asOf, 'total_value' => $totalValue, 'symbols' => []];
        }

        $symbols = [];
        foreach ($activeTP['items'] ?? [] as $item) {
            $symbol = $item['symbol'];
            if (!in_array($symbol, $portfolioSymbols)) continue;
            if ($symbol === 'SP500') continue;

            // Get current value for this symbol
            $currentValue = $symbolValues[$symbol] ?? 0;

            $targetShare = (float) $item['target_share'];
            $deviation = (float) $item['deviation_trigger'];
            $currentPct = ($currentValue / $totalValue) * 100;
            $targetPct = $targetShare * 100;
            $minPct = ($targetShare - $deviation) * 100;
            $maxPct = ($targetShare + $deviation) * 100;

            // Determine status
            if ($currentPct >= $minPct && $currentPct <= $maxPct) {
                $status = 'ok';
            } elseif ($currentPct < $minPct) {
                $status = 'under';
            } else {
                $status = 'over';
            }

            // Get asset type from Asset model
            $asset = AssetExt::where('name', $symbol)->first();
            $type = $asset ? $asset->type : 'ETF';

            $symbols[] = [
                'symbol' => $symbol,
                'type' => $type,
                'target_pct' => $targetPct,
                'deviation_pct' => $deviation * 100,
                'min_pct' => $minPct,
                'max_pct' => $maxPct,
                'current_pct' => $currentPct,
                'current_value' => $currentValue,
                'status' => $status,
                'trade_portfolio_id' => $activeTP['id'] ?? null,
            ];
        }

        return [
            'as_of_date' => $asOf,
            'total_value' => $totalValue,
            'symbols' => $symbols,
        ];
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
        $arr['transactions'] = $account ? $accountController->createTransactionsResponse($account, $asOf) : [];

        $arr['sp500_monthly_performance'] = $this->createAssetMonthlyPerformanceResponse(AssetExt::getSP500Asset(), $asOf, $arr['transactions'], true);
        $arr['cash'] = $this->createCashMonthlyPerformanceResponse($asOf, $arr['transactions']);


        // Handle multiple portfolios
        $portController = new PortfolioAPIControllerExt(\App::make(PortfolioRepository::class));
        $portfolios = $fund->portfolios()->get();
        $portfolioResponses = [];
        $allTradePortfolios = collect();
        $yearAgo = Utils::asOfAddYear($asOf, -1);

        /** @var PortfolioExt $portfolio */
        foreach ($portfolios as $portfolio) {
            $portResponse = $portController->createPortfolioResponse($portfolio, $asOf);
            $portResponse['id'] = $portfolio->id;
            $portfolioResponses[] = $portResponse;
            $tradePortfolios = $portfolio->tradePortfoliosBetween($yearAgo, $asOf);
            $allTradePortfolios = $allTradePortfolios->merge($tradePortfolios);
        }

        // For backward compatibility, set portfolio to first one; add all as array
        $arr['portfolio'] = $portfolioResponses[0] ?? null;
        $arr['portfolios'] = $portfolioResponses;
        $arr['tradePortfolios'] = $allTradePortfolios;

        $assetPerf = $this->createGroupMonthlyPerformanceResponse($fund, $asOf, $arr);
        $arr['asset_monthly_performance'] = $assetPerf;

        // create a linear regression projection for the next 10 years
        $arr['linear_regression'] = $this->createLinearRegressionResponse($arr['monthly_performance'], $asOf);

        // Add withdrawal rule goal data if configured
        if ($fund->hasWithdrawalGoal()) {
            $withdrawalProgress = $fund->withdrawalProgress($asOf);
            // Pure growth projection (without withdrawals)
            $withdrawalProgress['target_reach'] = $fund->calculateTargetReachWithGrowthRate($asOf);
            // Net growth projection (with withdrawals deducted from growth)
            $withdrawalProgress['target_reach_with_withdrawals'] = $fund->calculateTargetReachWithWithdrawals($asOf);
            // Add countdown-specific funding percentage (not capped at 100%)
            if ($fund->getIndependenceMode() === 'countdown') {
                $withdrawalProgress['funding_pct'] = $fund->getCountdownFundingPct($asOf);
            }
            $arr['withdrawal_goal'] = $withdrawalProgress;
        }

        // Annotate every trade portfolio we accumulated across ALL fund portfolios.
        // Previous code iterated $tradePortfolios (loop-local from the last portfolio),
        // which silently dropped annotations for multi-portfolio funds and crashed on
        // zero-portfolio funds.
        /** @var TradePortfolioExt $tradePortfolio */
        foreach ($allTradePortfolios as $tradePortfolio) {
            $items = $tradePortfolio->tradePortfolioItems()->get();
            $tradePortfolio->items = $items;
            $tradePortfolio->annotateAssetsAndGroups();
            $tradePortfolio->annotateTotalShares();
        }

        $arr['asOf'] = $asOf;

        // Add data staleness info for display warning banner
        $arr['data_staleness'] = $this->calculateDataStaleness($asOf);

        // Flag fund-level data-completeness issues so the view can show flash
        // warnings (rather than the page crashing). Aggregator-style funds are
        // expected to have 0 portfolios / 0 transactions by design.
        $arr['data_warnings'] = $this->collectFundDataWarnings($fund, $portfolios, $arr['transactions'] ?? []);

        return $arr;
    }

    /**
     * Build a list of non-fatal data-completeness warnings for the fund show page.
     * Returns an array of ['type' => ..., 'title' => ..., 'message' => ...] items.
     */
    protected function collectFundDataWarnings($fund, $portfolios, $transactions): array
    {
        $warnings = [];

        if ($portfolios->isEmpty()) {
            $warnings[] = [
                'type' => 'no_portfolios',
                'title' => 'No portfolios',
                'message' => 'This fund has no portfolios configured, so asset-level data is unavailable.',
            ];
        }

        if (empty($transactions)) {
            $warnings[] = [
                'type' => 'no_transactions',
                'title' => 'No transactions',
                'message' => 'This fund has no transactions recorded, so historical performance cannot be computed.',
            ];
        }

        return $warnings;
    }

    public function sendFundReport($fundReport)
    {
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
                    // Check if account report already exists for this account/as_of to prevent duplicates on retry
                    $existing = AccountReport::where('account_id', $account->id)
                        ->where('as_of', $fundReport->as_of)
                        ->where('type', $fundReport->type)
                        ->first();
                    if ($existing) {
                        Log::info("  -> skipping, report already exists (id: {$existing->id})");
                        continue;
                    }
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
        // Only dispatch if not a template (9999-12-31)
        if ($fundReport->as_of->format('Y-m-d') !== '9999-12-31') {
            SendFundReport::dispatch($fundReport);
        }
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

    protected function fundReportScheduleDue($shouldRunBy, ScheduledJob $job, Carbon $asOf, bool $skipDataCheck = false): ?FundReportExt
    {
        $shouldRunByDate = Carbon::parse($shouldRunBy);

        if ($skipDataCheck) {
            Log::info('Skipping data check for fund report schedule: ' . $job->id);
            return $this->createFundReportFromSchedule($job, $asOf, $shouldRunBy);
        }

        // Load exchange holidays for calculating trading days
        $holidays = $this->loadExchangeHolidays('NYSE', $shouldRunByDate->copy()->subDays(14), $asOf);

        // Check for recent data in the lookback window (calendar-based for simplicity)
        $dayOfWeek = $shouldRunByDate->dayOfWeek;
        $lookbackDays = 2;
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
            return $this->createFundReportFromSchedule($job, $asOf, $shouldRunBy);
        }

        // No data in lookback window - find the most recent asset price
        $latestPrice = AssetPrice::query()
            ->where('start_dt', '<=', $shouldRunBy)
            ->orderBy('start_dt', 'desc')
            ->first();

        if (!$latestPrice) {
            $msg = 'No asset price data found before ' . $shouldRunBy;
            Log::error($msg);
            throw new Exception($msg);
        }

        $latestPriceDate = Carbon::parse($latestPrice->start_dt);

        // Calculate trading days between latest price and report date
        $tradingDaysStale = $this->calculateTradingDays($latestPriceDate, $shouldRunByDate, $holidays);

        Log::info("Latest price date: {$latestPriceDate->format('Y-m-d')}, Report date: {$shouldRunByDate->format('Y-m-d')}, Trading days stale: {$tradingDaysStale}");

        // Fail if data is more than 5 trading days stale
        if ($tradingDaysStale > 5) {
            $msg = "No recent data for fund report schedule {$job->id}. Latest price: {$latestPriceDate->format('Y-m-d')} ({$tradingDaysStale} trading days stale)";
            Log::error($msg);
            throw new Exception($msg);
        }

        // Data is stale but within tolerance - proceed with warning
        Log::warning("Creating fund report with stale data ({$tradingDaysStale} trading days). Latest price: {$latestPriceDate->format('Y-m-d')}");
        return $this->createFundReportFromSchedule($job, $asOf, $shouldRunBy);
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

    /**
     * Calculate data staleness for a given as-of date.
     *
     * @param string $asOf The as-of date (Y-m-d format)
     * @return array Data staleness info: latest_price_date, trading_days_stale, is_stale
     */
    protected function calculateDataStaleness(string $asOf): array
    {
        $asOfDate = Carbon::parse($asOf);

        // Find the most recent asset price on or before the as-of date
        $latestPrice = AssetPrice::query()
            ->where('start_dt', '<=', $asOf)
            ->orderBy('start_dt', 'desc')
            ->first();

        if (!$latestPrice) {
            return [
                'latest_price_date' => null,
                'trading_days_stale' => null,
                'is_stale' => true,
                'message' => 'No asset price data available',
            ];
        }

        $latestPriceDate = Carbon::parse($latestPrice->start_dt);

        // Load exchange holidays
        $holidays = $this->loadExchangeHolidays('NYSE', $latestPriceDate, $asOfDate);

        // Calculate trading days between latest price and as-of date
        $tradingDaysStale = $this->calculateTradingDays($latestPriceDate, $asOfDate, $holidays);

        // Data is considered stale if there's any missing trading day
        $isStale = $tradingDaysStale > 0;

        $result = [
            'latest_price_date' => $latestPriceDate->format('Y-m-d'),
            'trading_days_stale' => $tradingDaysStale,
            'is_stale' => $isStale,
        ];

        if ($isStale) {
            $result['message'] = "Portfolio data as of {$latestPriceDate->format('M j, Y')} ({$tradingDaysStale} trading day" . ($tradingDaysStale > 1 ? 's' : '') . " before report date)";
        }

        return $result;
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

        // Collect portfolio assets from ALL portfolios
        $allPortfolioAssets = collect();
        foreach ($fund->portfolios()->get() as $portfolio) {
            $allPortfolioAssets = $allPortfolioAssets->merge($portfolio->portfolioAssets()->get());
        }

        /** @var PortfolioAsset $pa */
        $assetPerf = [];
        $processed = [];
        foreach ($allPortfolioAssets as $pa) {
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
                $assetPerf[$group]['SP500'] = $this->createAssetMonthlyPerformanceResponse(AssetExt::getSP500Asset(), $asOf, $transactions, true);
            }
            $assetPerf[$group][$asset->name] = $perf;
        }
        return $assetPerf;
    }

    // create an array of assets and their historical prices
    // this is used to create the asset bands for the line graph
    // asset bands are highlight the max, min, and target values used to trigger trades
    // this data is the real value of assets (quantity * price)
    private function createMonthlyAssetBandsResponse($fund, $asOf, $arr, $fromDate = null)
    {
        // Get all portfolio IDs for this fund
        $portfolioIds = $fund->portfolios()->pluck('portfolios.id')->toArray();

        /** @var PortfolioAsset $pa */
        $assetPerf = [];
        $processed = [];
        // Get unique assets from ALL portfolios
        $uniqueAssets = PortfolioAsset::query()
            ->whereIn('portfolio_id', $portfolioIds)
            ->select('asset_id')
            ->distinct()
            ->get();
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
            // loop through the history of the portfolio assets for this asset across ALL portfolios
            PortfolioAsset::query()
                ->whereIn('portfolio_id', $portfolioIds)
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

            // Filter by fromDate if provided
            if ($fromDate) {
                $perf = array_filter($perf, fn($key) => $key >= $fromDate, ARRAY_FILTER_USE_KEY);
            }

            $assetPerf[$asset->name] = $perf;
        }
        return $assetPerf;
    }

}
