<?php namespace Tests;

use App\Models\AssetExt;
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

class DataFactory
{
    public $funds = array();
    public $userNum = 0;
    public $user;
    public $users = array();
    public $userAccount;
    public $userAccounts = array();
    public $matchingRules = array();
    public $accountMatching = array();
    public $transactionMatchings = array();
    public $transactions = array();
    public $portfolio;
    public $fundAccount;
    public $fundTransaction;
    public $fundBalance;
    public $cashPosition;
    public $cash;
    public $transaction;
    public $matchTransaction;
    public $matchingRule;

    public function createFund($shares=1000, $value=1000, $timestamp='2022-01-01')
    {
        $this->fund = Fund::factory()
            ->has(Portfolio::factory()->count(1), 'portfolios')
            ->has(Account::factory()->count(1), 'accounts')
            ->create();
        $this->funds[] = $this->fund;

        $this->fundAccount = $this->fund->account();
        $this->portfolio = $this->fund->portfolio();

        $this->fundTransaction = Transaction::factory()
            ->for($this->fundAccount, 'account')
            ->create();

        $this->fundBalance = AccountBalance::factory()
            ->for($this->fundTransaction, 'transaction')
            ->create([
                'type' => 'OWN',
                'shares' => $shares
            ]);

        $this->cash = AssetExt::getCashAsset()->first();

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
        return $this->user;
    }

    public function createMatchingRule($limit=100, $match=100) {
        $this->matchingRule = MatchingRule::factory()->create([
            'dollar_range_start' => 0,
            'dollar_range_end' => $limit,
            'match_percent' => $match
        ]);
        $this->matchingRules[] = $this->matchingRule;
        return $this->matchingRule;
    }

    public function createAccountMatching() {
        return $this->accountMatching[] = AccountMatchingRule::factory()
            ->for($this->userAccounts[$this->userNum], 'account')
            ->for($this->matchingRule)
            ->create();
    }

    public function createTransaction($value=100, $account=null, $type='PUR', $source='DIR') {
        $tran = $this->makeTransaction($value, $account, $type, $source);
        $tran->save();
        return $tran;
    }

    public function makeTransaction($value=100, $account=null, $type='PUR', $source='DIR') {
        if ($account == null) {
            if (count($this->userAccounts) > $this->userNum) {
                $account = $this->userAccounts[$this->userNum];
            } else {
                $account = $this->fundAccount;
            }
        }
        $this->transaction = Transaction::factory()
            ->for($account, 'account')
            ->make([
                'type' => $type,
                'source' => $source,
                'value' => $value
            ]);
        $this->transactions[] = $this->transaction;
        return $this->transaction;
    }

    public function createAsset($source)
    {
        return Asset::factory()->create([
            'source' => $source,
        ]);
    }

    public function createFundWithMatching()
    {
        $factory = $this;
        $factory->createFund();
        $factory->createUser();
        $factory->createMatchingRule(150, 100);
        $factory->createAccountMatching();

        $transaction = $factory->createTransaction();
        $matching = $factory->userAccounts[$this->userNum]->accountMatchingRules()->first();

        $this->matchTransaction = $factory->createTransaction($transaction->value/2, null, 'DIR', 'MAT');
        $match = $this->createTransactionMatching($matching, $this->matchTransaction, $transaction)
        ;
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
}
