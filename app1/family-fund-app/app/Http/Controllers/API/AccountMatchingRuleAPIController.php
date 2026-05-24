<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Traits\AuthorizesApiAccess;
use App\Http\Requests\API\CreateAccountMatchingRuleAPIRequest;
use App\Http\Requests\API\UpdateAccountMatchingRuleAPIRequest;
use App\Models\AccountMatchingRule;
use App\Repositories\AccountMatchingRuleRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\AccountMatchingRuleResource;
use Response;

/**
 * Class AccountMatchingRuleController
 * @package App\Http\Controllers\API
 */

class AccountMatchingRuleAPIController extends AppBaseController
{
    use AuthorizesApiAccess;

    /** @var  AccountMatchingRuleRepository */
    protected $accountMatchingRuleRepository;

    public function __construct(AccountMatchingRuleRepository $accountMatchingRuleRepo)
    {
        $this->accountMatchingRuleRepository = $accountMatchingRuleRepo;
    }

    /**
     * Display a listing of the AccountMatchingRule.
     * GET|HEAD /accountMatchingRules
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $query = $this->apiAuthz()->scopeByAccountRelation(AccountMatchingRule::query());

        foreach ($request->except(['skip', 'limit']) as $field => $value) {
            $query->where($field, $value);
        }

        if ($request->get('skip')) {
            $query->skip((int) $request->get('skip'));
        }

        if ($request->get('limit')) {
            $query->limit((int) $request->get('limit'));
        }

        $accountMatchingRules = $query->get();

        return $this->sendResponse(AccountMatchingRuleResource::collection($accountMatchingRules), 'Account Matching Rules retrieved successfully');
    }

    /**
     * Store a newly created AccountMatchingRule in storage.
     * POST /accountMatchingRules
     *
     * @param CreateAccountMatchingRuleAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateAccountMatchingRuleAPIRequest $request)
    {
        $input = $request->all();

        $this->requireAccountAccess($this->resolveAccount($input['account_id'] ?? null), modify: true);

        $accountMatchingRule = $this->accountMatchingRuleRepository->create($input);

        return $this->sendResponse(new AccountMatchingRuleResource($accountMatchingRule), 'Account Matching Rule saved successfully');
    }

    /**
     * Display the specified AccountMatchingRule.
     * GET|HEAD /accountMatchingRules/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var AccountMatchingRule $accountMatchingRule */
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            return $this->sendError('Account Matching Rule not found');
        }

        $this->requireAccountAccess($this->resolveAccount($accountMatchingRule->account_id));

        return $this->sendResponse(new AccountMatchingRuleResource($accountMatchingRule), 'Account Matching Rule retrieved successfully');
    }

    /**
     * Update the specified AccountMatchingRule in storage.
     * PUT/PATCH /accountMatchingRules/{id}
     *
     * @param int $id
     * @param UpdateAccountMatchingRuleAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAccountMatchingRuleAPIRequest $request)
    {
        $input = $request->all();

        /** @var AccountMatchingRule $accountMatchingRule */
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            return $this->sendError('Account Matching Rule not found');
        }

        $this->requireAccountAccess($this->resolveAccount($accountMatchingRule->account_id), modify: true);

        $accountMatchingRule = $this->accountMatchingRuleRepository->update($input, $id);

        return $this->sendResponse(new AccountMatchingRuleResource($accountMatchingRule), 'AccountMatchingRule updated successfully');
    }

    /**
     * Remove the specified AccountMatchingRule from storage.
     * DELETE /accountMatchingRules/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var AccountMatchingRule $accountMatchingRule */
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            return $this->sendError('Account Matching Rule not found');
        }

        $this->requireAccountAccess($this->resolveAccount($accountMatchingRule->account_id), modify: true);

        $accountMatchingRule->delete();

        return $this->sendSuccess('Account Matching Rule deleted successfully');
    }
}
