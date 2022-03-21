<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\Traits\PerformanceTrait;
use App\Models\Account;
use App\Models\Utils;
use App\Repositories\AccountRepository;
use App\Http\Controllers\API\AccountAPIController;
use App\Http\Resources\AccountResource;
use Carbon\Traits\Date;
use Response;
use Carbon\Carbon;

/**
 * Class AccountAPIControllerExt
 * @package App\Http\Controllers\API
 */

class AccountAPIControllerExt extends AccountAPIController
{
    use PerformanceTrait;
    public function __construct(AccountRepository $accountRepo)
    {
        parent::__construct($accountRepo);
    }

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
     * Display the specified Accounts.
     * GET|HEAD /Accounts/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $now = date('Y-m-d');
        return $this->showAsOf($id, $now);
    }

    /**
     * Display the specified Accounts.
     * GET|HEAD /accounts/{id}/as_of/{date}
     *
     * @param int $id
     *
     * @return Response
     */
    public function showAsOf($id, $asOf)
    {
        /** @var Account $account */
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        $arr = $this->createAccountResponse($account, $asOf);
        $arr['as_of'] = $asOf;

        return $this->sendResponse($arr, 'Account retrieved successfully');
    }

    /**
     * Display the specified Accounts.
     * GET|HEAD /accounts/{id}/performance_as_of/{date}
     *
     * @param int $id
     *
     * @return Response
     */
    public function showPerformanceAsOf($id, $asOf)
    {
        /** @var Account $account */
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        $arr = $this->createAccountArray($account);
        $this->perfObject = $account;
        $arr['performance'] = $this->createMonthlyPerformanceResponse($asOf);
        $arr['as_of'] = $asOf;

        return $this->sendResponse($arr, 'Account retrieved successfully');
    }

    /**
     * Display the specified Accounts.
     * GET|HEAD /accounts/{id}/transactions_as_of/{date}
     *
     * @param int $id
     *
     * @return Response
     */
    public function showTransactionsAsOf($id, $asOf)
    {
        /** @var Account $account */
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        $arr = $this->createAccountArray($account);
        $arr['transactions'] = $this->createTransactionsResponse($account, $asOf);
        $arr['as_of'] = $asOf;

        return $this->sendResponse($arr, 'Account retrieved successfully');
    }

   /**
     * Display the specified Accounts.
     * GET|HEAD /accounts/{id}/report_as_of/{date}
     *
     * @param int $id
     *
     * @return Response
     */
    public function showReportAsOf($id, $asOf)
    {
        /** @var Account $account */
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        $arr = $this->createAccountResponse($account, $asOf);
        $arr['transactions'] = $this->createTransactionsResponse($account, $asOf);
        $arr['performance'] = $this->createMonthlyPerformanceResponse($asOf);
        $arr['as_of'] = $asOf;

        return $this->sendResponse($arr, 'Account retrieved successfully');
    }

    /**
     * Generate Account Matching Report (use of matchings).
     * GET|HEAD /account_matching/{id}/report_as_of/{date}
     *
     * @param int $id
     * @param Date $asOf
     *
     * @return Response
     */
    public function accountMatching($id, $asOf)
    {
        $account = $this->accountRepository->with(['accountMatchingRules.matchingRule.transactionMatchings.transaction'])->find($id);
//        print_r("data: " . json_encode($account) . "\n");
//        print_r($account);
        $mrs = [];
        foreach ($account->accountMatchingRules()->get() as $amr) {
            foreach ($amr->matchingRule()->get() as $mr) {
                $sum = 0;
                foreach ($mr->transactionMatchings()->get() as $tm) {
                    foreach ($tm->transaction()->get() as $transaction) {
                        $sum += $transaction->value;
                    }
                }
                $mr['used'] = $sum;
                $mrA = $mr->toArray();
                unset($mrA['transaction_matchings']);
                $mrs[] = $mrA;
            }
        }
        $arr = [];
        $arr['nickname'] = $account->nickname;
        $arr['matching_rules'] = $mrs;

        return $this->sendResponse($arr, 'Record fetched successfully');
    }

    public function createAccountMatchingResponse($data)
    {
    }
}
