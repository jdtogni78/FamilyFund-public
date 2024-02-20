<?php namespace Tests;

use App\Models\AssetExt;
use App\Models\AssetPrice;
use App\Models\Transaction;
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
use Nette\Utils\DateTime;

class DataFactory
{
    public $userNum = 0;
    public $user;
    public $users = array();
    public $userAccount;
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

    public $verbose = false;

    public function createFund($shares=1000, $value=1000, $timestamp='2022-01-01')
    {
        $this->fund = Fund::factory()
            ->has(Portfolio::factory()->count(1), 'portfolios')
            ->has(Account::factory()->count(1), 'accounts')
            ->create();
        if ($this->verbose) print_r("this->fund: " . json_encode($this->fund) . "\n");
        $this->funds[] = $this->fund;

        $this->fundAccount = $this->fund->account();
        $this->portfolio = $this->fund->portfolio();
        $this->source = $this->portfolio->source;

        $this->fundTransaction = $this->createTransaction($value, $this->fundAccount, 'INI', 'C', null, $timestamp);

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
        if ($this->verbose) print_r("mr: " . json_encode($this->matchingRule) . "\n");

        $this->matchingRules[] = $this->matchingRule;
        return $this->matchingRule;
    }

    public function createAccountMatching() {
        $amr = AccountMatchingRule::factory()
            ->for($this->userAccounts[$this->userNum], 'account')
            ->for($this->matchingRule)
            ->create([
            ]);
        if ($this->verbose) print_r("amr: " . json_encode($amr) . "\n");
        $this->accountMatching[] = $amr;
        return $this->accountMatching;
    }

    public function createTransaction($value=100, $account=null, $type='PUR', $status='P', $flags=null, $timestamp=null) {
        $tran = $this->makeTransaction($value, $account, $type, $status, $flags, $timestamp);
        $tran->save();
        if ($this->verbose) print_r("tran: " . json_encode($tran) . "\n");
        return $tran;
    }

    public function makeTransaction($value=100, $account=null, $type='PUR', $status='P', $flags=null, $timestamp=null, $shares=null) {
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

        $this->transaction = Transaction::factory()
            ->for($account, 'account')
            ->make($arr);
        $this->transactions[] = $this->transaction;
        return $this->transaction;
    }

    public function createAsset($source)
    {
        if ($source == null) $source = $this->source;
        $asset = Asset::factory()->create([
            'source' => $source,
        ]);
        if ($this->verbose) print_r("asset: " . json_encode($asset) . "\n");
        $this->assets[] = $this->asset = $asset;
        return $asset;
    }

    protected function createAssetPrice($asset, $price=null)
    {
        $data = ['asset_id' => $asset];
        if ($price != null) $data['price'] = $price;
        $ap = AssetPrice::factory()->create($data);
        if ($this->verbose) print_r("ap: " . json_encode($ap) . "\n");
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
        if ($this->verbose) print_r("pa: " . json_encode($pa) . "\n");
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
        $transaction = $this->createTransaction($value1, null, 'PUR', 'P', null, null);
        $matching = $this->userAccounts[$this->userNum]->accountMatchingRules()->first();

        $this->matchTransaction = $this->createTransaction($value2, null, 'MAT', 'C', null, null);
        $match = $this->createTransactionMatching($matching, $this->matchTransaction, $transaction);
    }

    public function dumpTransactions($account = null) {
        if ($account == null) $account = $this->userAccount;
        print_r(["TRANSACTIONS", $account->id]);
//        foreach ($this->factory->account->transactions() as $t) {
        foreach ($account->transactions()->get() as $t) {
            print_r("** TRAN " . json_encode($t->toArray()) . "\n");
            $bal = $t->accountBalance()->first();
            if ($bal) print_r("**** BAL " . json_encode($bal->toArray()) . "\n");
            foreach ($t->referenceTransactionMatching()->get() as $r) {
                print_r("**** REF " . json_encode($r->toArray()) . "\n");
            }
        }
    }

    public function dumpMatchingRules()
    {
        print_r("[MatchingRules]\n");
        foreach ($this->matchingRules as $mr) {
            $accts = [];
            foreach ($mr->accountMatchingRules()->get() as $amr) {
                $accts[] = $amr->account()->first()->id;
            }
            print_r("** MR ".json_encode($mr->toArray()) . " accounts:" . json_encode($accts)."\n");

        }
    }

    public function dumpBalances()
    {
        $accounts = array_merge([$this->fundAccount], $this->userAccounts);
        print_r("[BALANCES]\n");
        foreach ($accounts as $account) {
            foreach ($account->accountBalances()->get() as $bal) {
                print_r("** " . json_encode($bal) . "\n");
            }
        }
    }

}
