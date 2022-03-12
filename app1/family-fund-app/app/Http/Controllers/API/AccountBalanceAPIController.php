<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateAccountBalanceAPIRequest;
use App\Http\Requests\API\UpdateAccountBalanceAPIRequest;
use App\Models\AccountBalance;
use App\Repositories\AccountBalanceRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\AccountBalanceResource;
use Response;

/**
 * Class AccountBalanceController
 * @package App\Http\Controllers\API
 */

class AccountBalanceAPIController extends AppBaseController
{
    /** @var  AccountBalanceRepository */
    protected $accountBalanceRepository;

    public function __construct(AccountBalanceRepository $accountBalanceRepo)
    {
        $this->accountBalanceRepository = $accountBalanceRepo;
    }

    /**
     * Display a listing of the AccountBalance.
     * GET|HEAD /accountBalances
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $accountBalances = $this->accountBalanceRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse(AccountBalanceResource::collection($accountBalances), 'Account Balances retrieved successfully');
    }

    /**
     * Store a newly created AccountBalance in storage.
     * POST /accountBalances
     *
     * @param CreateAccountBalanceAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateAccountBalanceAPIRequest $request)
    {
        $input = $request->all();

        $accountBalance = $this->accountBalanceRepository->create($input);

        return $this->sendResponse(new AccountBalanceResource($accountBalance), 'Account Balance saved successfully');
    }

    /**
     * Display the specified AccountBalance.
     * GET|HEAD /accountBalances/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var AccountBalance $accountBalance */
        $accountBalance = $this->accountBalanceRepository->find($id);

        if (empty($accountBalance)) {
            return $this->sendError('Account Balance not found');
        }

        return $this->sendResponse(new AccountBalanceResource($accountBalance), 'Account Balance retrieved successfully');
    }

    /**
     * Update the specified AccountBalance in storage.
     * PUT/PATCH /accountBalances/{id}
     *
     * @param int $id
     * @param UpdateAccountBalanceAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAccountBalanceAPIRequest $request)
    {
        $input = $request->all();

        /** @var AccountBalance $accountBalance */
        $accountBalance = $this->accountBalanceRepository->find($id);

        if (empty($accountBalance)) {
            return $this->sendError('Account Balance not found');
        }

        $accountBalance = $this->accountBalanceRepository->update($input, $id);

        return $this->sendResponse(new AccountBalanceResource($accountBalance), 'AccountBalance updated successfully');
    }

    /**
     * Remove the specified AccountBalance from storage.
     * DELETE /accountBalances/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var AccountBalance $accountBalance */
        $accountBalance = $this->accountBalanceRepository->find($id);

        if (empty($accountBalance)) {
            return $this->sendError('Account Balance not found');
        }

        $accountBalance->delete();

        return $this->sendSuccess('Account Balance deleted successfully');
    }
}
