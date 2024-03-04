<?php namespace Tests;

use App\Models\AccountExt;
use App\Models\AssetExt;
use App\Models\AssetPrice;
use App\Models\FundReport;
use App\Models\Schedule;
use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use App\Models\ScheduleExt;
use App\Models\TradePortfolio;
use App\Models\TradePortfolioExt;
use App\Models\TradePortfolioItem;
use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Models\TransactionMatching;
use App\Models\User;
use App\Models\Fund;
use App\Models\Portfolio;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Asset;
use App\Models\PortfolioAsset;
use App\Models\MatchingRule;
use App\Models\AccountMatchingRule;
use Carbon\Carbon;
use Log;
use Nette\Utils\DateTime;

class DataFactory
{
    public $userNum = 0;
    public $user;
    public $users = array();

    public AccountExt $userAccount;
    public $userAccounts = array();

    public $matchingRules = array();
    public $accountMatching = array();
    public $transactionMatchings = array();
    public $matchTransaction;
    public $matchingRule;

    public $fund;
    public $funds = array();
    public $fundAccount;
    public $fundTransaction;
    public $fundBalance;

    public $portfolio;
    public $source;

    public $cash;
    public $cashPosition;
    public $transaction;
    public $transactions = array();

    public $asset;
    public $assets = [];
    public $assetPrice;
    public $assetPrices = [];
    public $portfolioAsset;
    public $portfolioAssets = [];

    public TradePortfolioExt $tradePortfolio;

    public $verbose = false;

    public function createFund($shares=1000, $value=1000, $timestamp='2022-01-01', $noTransaction=false)
    {
        $this->fund = Fund::factory()
            ->has(Portfolio::factory()->count(1), 'portfolios')
            ->has(Account::factory()->count(1), 'accounts')
            ->create();
        if ($this->verbose) Log::debug("this->fund: " . json_encode($this->fund));
        $this->funds[] = $this->fund;

        $this->fundAccount = $this->fund->account();
        $this->portfolio = $this->fund->portfolio();
        $this->source = $this->portfolio->source;

        if (!$noTransaction) {
            $this->fundTransaction = $this->createTransaction($value, $this->fundAccount, TransactionExt::TYPE_INITIAL,
                TransactionExt::STATUS_CLEARED, null, $timestamp);

            $this->fundBalance = AccountBalance::factory()
                ->for($this->fundTransaction, 'transaction')
                ->for($this->fundAccount, 'account')
                ->create([
                    'type' => 'OWN',
                    'start_dt' => $timestamp,
                    'end_dt' => '9999-12-31',
                    'shares' => $shares
                ]);

            $this->cash = AssetExt::getCashAsset();

            $this->cashPosition = PortfolioAsset::factory()
                ->for($this->portfolio, 'portfolio')
                ->for($this->cash, 'asset')
                ->create([
                    'position' => $value,
                    'start_dt' => $timestamp,
                ]);
        }
        return $this->fund;
    }

    public function createUser() {
        $this->user = User::factory()
            ->has(Account::factory()
                ->for($this->fund, 'fund')
                ->count(1), 'accounts')
            ->create();
        $this->userAccounts[] = $this->userAccount = $this->user->accounts()->first();
        $this->users[] = $this->user;
        $this->userNum = count($this->users)-1;
        return $this->user;
    }

    public function createTradePortfolio($start_dt, $end_dt='9999-12-31')
    {
        $this->tradePortfolio = TradePortfolio::factory()
//            ->for($this->portfolio, 'portfolio')
            ->create([
                'start_dt' => $start_dt,
                'end_dt' => $end_dt,
            ]);

        if ($this->verbose) Log::debug("tradePortfolio: " . json_encode($this->tradePortfolio));

        for ($i=0; $i<3; $i++) {
            $item = TradePortfolioItem::factory()
                ->for($this->tradePortfolio, 'tradePortfolio')
                ->create([]);
            if ($this->verbose) Log::debug("item: " . json_encode($item));
        }
        return $this->tradePortfolio;
    }


    public function createMatching($dollar_end=100, $match=100, $start='2024-01-01', $end='9999-12-31', $dollar_start = 0)
    {
        $mr = $this->createMatchingRule($dollar_end, $match, $start, $end, $dollar_start);
        $this->createAccountMatching();
        return $mr;
    }

    public function createMatchingRule($dollar_end=100, $match=100, $start='2024-01-01', $end='9999-12-31', $dollar_start = 0) {
        $this->matchingRule = MatchingRule::factory()->create([
            'dollar_range_start' => $dollar_start,
            'dollar_range_end' => $dollar_end,
            'match_percent' => $match,
            "date_start" => $start,
            "date_end" => $end,
        ]);
        if ($this->verbose) Log::debug("mr: " . json_encode($this->matchingRule));

        $this->matchingRules[] = $this->matchingRule;
        return $this->matchingRule;
    }

    public function createAccountMatching() {
        $amr = AccountMatchingRule::factory()
            ->for($this->userAccounts[$this->userNum], 'account')
            ->for($this->matchingRule)
            ->create([
            ]);
        if ($this->verbose) Log::debug("amr: " . json_encode($amr));
        $this->accountMatching[] = $amr;
        return $this->accountMatching;
    }

    public function createTransaction($value=100, $account=null, $type=TransactionExt::TYPE_PURCHASE,
                                      $status=TransactionExt::STATUS_PENDING, $flags=null, $timestamp=null) {
        $tran = $this->makeTransaction($value, $account, $type, $status, $flags, $timestamp);
        $tran->save();
        if ($this->verbose) Log::debug("tran: " . json_encode($tran));
        return $tran;
    }

    public function makeTransaction($value=100, $account=null, $type=TransactionExt::TYPE_PURCHASE,
                                    $status=TransactionExt::STATUS_PENDING, $flags=null, $timestamp=null, $shares=null) {
        Log::debug("makeTransaction: " . json_encode($account) . " " . $type . " " . $status . " " . $value . " " . $flags . " " . $timestamp . " " . $shares);
        if ($account == null) {
            if (count($this->userAccounts) > $this->userNum) {
                $account = $this->userAccounts[$this->userNum];
            } else {
                $account = $this->fundAccount;
            }
        }

        $arr = [
            'type' => $type,
            'status' => $status,
            'value' => $value,
            'flags' => $flags,
        ];
        if ($shares) $arr['shares'] = $shares;
        if ($timestamp) $arr['timestamp'] = $timestamp;

        Log::debug("makeTransaction: " . json_encode($arr));
        $this->transaction = Transaction::factory()
            ->for($account, 'account')
            ->make($arr);
        $this->transactions[] = $this->transaction;
        return $this->transaction;
    }

    public function createScheduledTransaction($schedType, $schedValue, $timestamp, $value, $account=null)
    {
        $schedule = $this->createSchedule($schedType, $schedValue);
        $tran = $this->makeTransaction($value, $account, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_SCHEDULED, TransactionExt::FLAGS_NO_MATCH, $timestamp);
        $tran->save();
        $job = $this->createScheduledJob($schedule, ScheduledJobExt::ENTITY_TRANSACTION, $tran->id, $timestamp);
        if ($this->verbose) Log::debug("tran: " . json_encode($tran));
        return array($schedule, $job, $tran);
    }

    public function createScheduledFundReport($schedType, $schedValue, $fund, $type, $asOf=null)
    {
        if ($asOf == null) $asOf = Carbon::parse('9999-12-31');
        $schedule = $this->createSchedule($schedType, $schedValue);
        $report = $this->createFundReport($fund, $type, $asOf);
        $job = $this->createScheduledJob($schedule, ScheduledJobExt::ENTITY_FUND_REPORT, $report->id, $asOf);
        if ($this->verbose) Log::debug("report: " . json_encode($report));
        return array($schedule, $job, $report);
    }

    public function createFundReport($fund, $type, $asOf=null) {
        if ($asOf == null) $asOf = Carbon::parse('9999-12-31');
        $report = FundReport::factory()
            ->for($fund, 'fund')
            ->create([
                'type' => $type,
                'as_of' => $asOf,
            ]);
        $report->save();
        if ($this->verbose) Log::debug("fund report: " . json_encode($report));
        return $report;
    }

    public function createScheduledJob($schedule, $entity_descr, $entity_id, $start_dt=null, $end_dt='9999-12-31')
    {
        if ($start_dt == null) $start_dt = date('Y-m-d');
        $job = ScheduledJob::factory()
            ->for($schedule, 'schedule')
            ->create([
                'entity_descr' => $entity_descr,
                'entity_id' => $entity_id,
                'start_dt' => $start_dt,
                'end_dt' => $end_dt,
            ]);
        if ($this->verbose) Log::debug("Scheduled job: " . json_encode($job));
        return $job;
    }

    public function createSchedule($type, $value) {
        $schedule = Schedule::factory()
            ->create([
                'descr' => "test sched $type $value",
                'type' => $type,
                'value' => $value,
            ]);
        if ($this->verbose) Log::debug("schedule: " . json_encode($schedule));
        return $schedule;
    }



    public function createAsset($source)
    {
        if ($source == null) $source = $this->source;
        $asset = Asset::factory()->create([
            'source' => $source,
        ]);
        if ($this->verbose) Log::debug("asset: " . json_encode($asset));
        $this->assets[] = $this->asset = $asset;
        return $asset;
    }

    public function createAssetPrice($asset, $price=null, $start_dt=null, $end_dt=null)
    {
        $data = ['asset_id' => $asset];
        if ($price != null) $data['price'] = $price;
        if ($start_dt != null) $data['start_dt'] = $start_dt;
        else $data['start_dt'] = Carbon::today();
        if ($end_dt != null) $data['end_dt'] = $end_dt;
        else $data['end_dt'] = Carbon::parse('9999-12-31');

        $ap = AssetPrice::factory()->create($data);
        if ($this->verbose) Log::debug("ap: " . json_encode($ap));
        $this->assetPrices[] = $this->assetPrice = $ap;
        return $ap;
    }

    protected function createPortfolioAsset($asset, $position=null, $source=null)
    {
        $data = [
            'asset_id' => $asset,
            'portfolio_id' => $this->portfolio->id
        ];
        if ($position != null) $data['position'] = $position;
        $pa = PortfolioAsset::factory()->create($data);
        if ($this->verbose) Log::debug("pa: " . json_encode($pa));
        $this->portfolioAssets[] = $this->portfolioAsset = $pa;
        return $pa;
    }

    public function createAssetWithPrice($price=null, $source=null, $position=null)
    {
        $asset = $this->createAsset($source);
        $this->createAssetPrice($asset, $price);
        $this->createPortfolioAsset($asset, $position, $source);
        return $asset;
    }

    public function createFundWithMatching($dollar_end=100, $match=50, $value=50)
    {
        $factory = $this;
        $factory->createFund();
        $factory->createUser();
        $factory->createMatching($dollar_end, $match);
        $factory->createTransactionWithMatching($value, $value * $match / 100.0);
    }

    public function createTransactionMatching($matching, $matchTran, $transaction)
    {
        $this->transactionMatchings[] = $tm = TransactionMatching::factory()
            ->for($matching->matchingRule()->first())
            ->create([
                'transaction_id' => $matchTran->id,
                'reference_transaction_id' => $transaction->id
            ]);
        return $tm;
    }

    public function createTransactionWithMatching($value1=100, $value2=50): void
    {
        $transaction = $this->createTransaction($value1, null, TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING, null, null);
        $matching = $this->userAccounts[$this->userNum]->accountMatchingRules()->first();

        $this->matchTransaction = $this->createTransaction($value2, null, TransactionExt::TYPE_MATCHING,
            TransactionExt::STATUS_CLEARED, null, null);
        $match = $this->createTransactionMatching($matching, $this->matchTransaction, $transaction);
    }

    public function dumpTransactions($account = null) {
        if ($account == null) $account = $this->userAccount;
        Log::debug(["TRANSACTIONS", $account->id]);
//        foreach ($this->factory->account->transactions() as $t) {
        foreach ($account->transactions()->get() as $t) {
            Log::debug("** TRAN " . json_encode($t->toArray()));
            $bal = $t->accountBalance()->first();
            if ($bal) Log::debug("**** BAL " . json_encode($bal->toArray()));
            foreach ($t->referenceTransactionMatching()->get() as $r) {
                Log::debug("**** REF " . json_encode($r->toArray()));
            }
        }
    }

    public function dumpMatchingRules()
    {
        Log::debug("[MatchingRules]\n");
        foreach ($this->matchingRules as $mr) {
            $accts = [];
            foreach ($mr->accountMatchingRules()->get() as $amr) {
                $accts[] = $amr->account()->first()->id;
            }
            Log::debug("** MR ".json_encode($mr->toArray()) . " accounts:" . json_encode($accts));

        }
    }

    public function dumpBalances()
    {
        $accounts = array_merge([$this->fundAccount], $this->userAccounts);
        Log::debug("[BALANCES]\n");
        foreach ($accounts as $account) {
            foreach ($account->accountBalances()->get() as $bal) {
                Log::debug("** " . json_encode($bal));
            }
        }
    }

}
