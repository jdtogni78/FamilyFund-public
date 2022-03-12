<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccountMatchingRuleRequest;
use App\Http\Requests\UpdateAccountMatchingRuleRequest;
use App\Repositories\AccountMatchingRuleRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class AccountMatchingRuleController extends AppBaseController
{
    /** @var  AccountMatchingRuleRepository */
    protected $accountMatchingRuleRepository;

    public function __construct(AccountMatchingRuleRepository $accountMatchingRuleRepo)
    {
        $this->accountMatchingRuleRepository = $accountMatchingRuleRepo;
    }

    /**
     * Display a listing of the AccountMatchingRule.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $accountMatchingRules = $this->accountMatchingRuleRepository->all();

        return view('account_matching_rules.index')
            ->with('accountMatchingRules', $accountMatchingRules);
    }

    /**
     * Show the form for creating a new AccountMatchingRule.
     *
     * @return Response
     */
    public function create()
    {
        return view('account_matching_rules.create');
    }

    /**
     * Store a newly created AccountMatchingRule in storage.
     *
     * @param CreateAccountMatchingRuleRequest $request
     *
     * @return Response
     */
    public function store(CreateAccountMatchingRuleRequest $request)
    {
        $input = $request->all();

        $accountMatchingRule = $this->accountMatchingRuleRepository->create($input);

        Flash::success('Account Matching Rule saved successfully.');

        return redirect(route('accountMatchingRules.index'));
    }

    /**
     * Display the specified AccountMatchingRule.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            Flash::error('Account Matching Rule not found');

            return redirect(route('accountMatchingRules.index'));
        }

        return view('account_matching_rules.show')->with('accountMatchingRule', $accountMatchingRule);
    }

    /**
     * Show the form for editing the specified AccountMatchingRule.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            Flash::error('Account Matching Rule not found');

            return redirect(route('accountMatchingRules.index'));
        }

        return view('account_matching_rules.edit')->with('accountMatchingRule', $accountMatchingRule);
    }

    /**
     * Update the specified AccountMatchingRule in storage.
     *
     * @param int $id
     * @param UpdateAccountMatchingRuleRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAccountMatchingRuleRequest $request)
    {
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            Flash::error('Account Matching Rule not found');

            return redirect(route('accountMatchingRules.index'));
        }

        $accountMatchingRule = $this->accountMatchingRuleRepository->update($request->all(), $id);

        Flash::success('Account Matching Rule updated successfully.');

        return redirect(route('accountMatchingRules.index'));
    }

    /**
     * Remove the specified AccountMatchingRule from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            Flash::error('Account Matching Rule not found');

            return redirect(route('accountMatchingRules.index'));
        }

        $this->accountMatchingRuleRepository->delete($id);

        Flash::success('Account Matching Rule deleted successfully.');

        return redirect(route('accountMatchingRules.index'));
    }
}
