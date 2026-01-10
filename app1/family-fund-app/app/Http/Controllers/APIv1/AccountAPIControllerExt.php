<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\Traits\AccountTrait;
use App\Http\Controllers\Traits\PerformanceTrait;
use App\Models\Account;
use App\Models\AccountExt;
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
    use AccountTrait;

    public function __construct(AccountRepository $accountRepo)
    {
        parent::__construct($accountRepo);
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
        $account = $this->accountRepository->find($id);
        $arr = [];
        $arr['matching_rules'] = $this->createAccountMatchingResponse($account, $asOf);
        $arr['matching_available'] = $this->getTotalAvailableMatching($arr['matching_rules']);
        $arr['nickname'] = $this->accountRepository->find($id)->nickname;
        $arr['as_of'] = $asOf;

        return $this->sendResponse($arr, 'Record fetched successfully');
    }

    /**
     * GET|HEAD /funds/{id}/share_value_as_of/{date}
     * @param int $id
     * @return Response
     */
    public function shareValueAsOf($id, $asOf)
    {
        /** @var AccountExt $account */
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        $user = $account->user;
        $arr = [
            'share_price' => $account->shareValueAsOf($asOf),
            'available_shares' => $account->fund()->first()->unallocatedShares($asOf),
            'account_shares' => $account->sharesAsOf($asOf),
            'account_value' => $account->valueAsOf($asOf),
            'account_nickname' => $account->nickname,
            'account_code' => $account->code,
            'user_name' => $user?->name,
            'user_email' => $account->email_cc ?: $user?->email,
        ];
        return $this->sendResponse($arr, 'Share value retrieved successfully');
    }


}
