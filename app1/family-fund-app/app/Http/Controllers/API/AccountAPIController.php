<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateAccountAPIRequest;
use App\Http\Requests\API\UpdateAccountAPIRequest;
use App\Models\Account;
use App\Models\AccountExt;
use App\Repositories\AccountRepository;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\AccountResource;
use Response;

/**
 * Class AccountController
 * @package App\Http\Controllers\API
 */

class AccountAPIController extends AppBaseController
{
    /** @var  AccountRepository */
    protected $accountRepository;

    public function __construct(AccountRepository $accountRepo)
    {
        $this->accountRepository = $accountRepo;
    }

    /**
     * Display a listing of the Account.
     * GET|HEAD /accounts
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $query = AuthorizationService::for($request->user())
            ->scopeAccountsQuery(AccountExt::query());

        foreach ($request->except(['skip', 'limit']) as $field => $value) {
            $query->where($field, $value);
        }

        if ($request->get('skip')) {
            $query->skip((int) $request->get('skip'));
        }

        if ($request->get('limit')) {
            $query->limit((int) $request->get('limit'));
        }

        $accounts = $query->get();

        return $this->sendResponse(AccountResource::collection($accounts), 'Accounts retrieved successfully');
    }

    /**
     * Store a newly created Account in storage.
     * POST /accounts
     *
     * @param CreateAccountAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateAccountAPIRequest $request)
    {
        $input = $request->all();

        $account = $this->accountRepository->create($input);

        return $this->sendResponse(new AccountResource($account), 'Account saved successfully');
    }

    /**
     * Display the specified Account.
     * GET|HEAD /accounts/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show(Request $request, $id)
    {
        /** @var AccountExt $account */
        $account = AccountExt::find($id);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        abort_unless($request->user()?->canAccessAccount($account), 403);

        return $this->sendResponse(new AccountResource($account), 'Account retrieved successfully');
    }

    /**
     * Update the specified Account in storage.
     * PUT/PATCH /accounts/{id}
     *
     * @param int $id
     * @param UpdateAccountAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAccountAPIRequest $request)
    {
        $input = $request->all();

        /** @var Account $account */
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        $account = $this->accountRepository->update($input, $id);

        return $this->sendResponse(new AccountResource($account), 'Account updated successfully');
    }

    /**
     * Remove the specified Account from storage.
     * DELETE /accounts/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var Account $account */
        $account = $this->accountRepository->find($id);

        if (empty($account)) {
            return $this->sendError('Account not found');
        }

        $account->delete();

        return $this->sendSuccess('Account deleted successfully');
    }
}
