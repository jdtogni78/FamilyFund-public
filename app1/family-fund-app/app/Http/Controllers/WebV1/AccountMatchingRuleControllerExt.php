<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\CreateAccountMatchingRuleRequest;
use App\Http\Requests\UpdateAccountMatchingRuleRequest;
use App\Http\Requests\CreateAccountMatchingRuleRequestBulk;
use App\Repositories\AccountMatchingRuleRepository;
use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\AccountMatchingRuleController;
use Illuminate\Http\Request;
use Flash;
use Response;
use App\Http\Controllers\Traits\MailTrait;
use App\Mail\AccountMatchingRuleEmail;
use App\Mail\AccountMatchingRuleRemovedEmail;
use App\Models\MatchingRuleExt;
use App\Models\AccountExt;
use App\Http\Controllers\Traits\AccountSelectorTrait;

class AccountMatchingRuleControllerExt extends AccountMatchingRuleController
{
    use MailTrait;
    use AccountSelectorTrait;

    /**
     * Display a listing of AccountMatchingRules with filtering.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $query = \App\Models\AccountMatchingRule::with(['account.fund', 'matchingRule']);

        // Apply filters
        $filters = [];

        if ($request->filled('fund_id')) {
            $filters['fund_id'] = $request->fund_id;
            $query->whereHas('account', function($q) use ($request) {
                $q->where('fund_id', $request->fund_id);
            });
        }

        if ($request->filled('account_id')) {
            $filters['account_id'] = $request->account_id;
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('matching_rule_id')) {
            $filters['matching_rule_id'] = $request->matching_rule_id;
            $query->where('matching_rule_id', $request->matching_rule_id);
        }

        $accountMatchingRules = $query->get();

        $api = array_merge(
            $this->getAccountSelectorData(),
            [
                'matchingRuleMap' => MatchingRuleExt::ruleMap(),
                'filters' => $filters,
            ]
        );

        return view('account_matching_rules.index')
            ->with('accountMatchingRules', $accountMatchingRules)
            ->with('api', $api)
            ->with('filters', $filters);
    }

    /**
     * Show the form for creating a new AccountMatchingRule.
     *
     * @return Response
     */
    public function create()
    {
        $api = array_merge(
            $this->getAccountSelectorData(),
            ['mr' => MatchingRuleExt::ruleMap()]
        );
        return view('account_matching_rules.create')->with('api', $api);
    }

    public function bulkCreate()
    {
        $api = array_merge(
            $this->getAccountSelectorData(),
            ['mr' => MatchingRuleExt::ruleMap()]
        );
        return view('account_matching_rules.create_bulk')->with('api', $api);
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

        Flash::success('Account Matching Rule saved successfully.');
        $this->_store($input);

        return redirect(route('accountMatchingRules.index'));
    }

    private function _store($input)
    {
        $accountMatchingRule = $this->accountMatchingRuleRepository->create($input);
        $api = [
            'mr' => $accountMatchingRule->matchingRule()->first(),
            'account' => $accountMatchingRule->account()->first(),
        ];
        $to = $accountMatchingRule->account()->first()->email_cc;
        $mail = new AccountMatchingRuleEmail($accountMatchingRule, $api);
        $this->sendMail($mail, $to);
        return $accountMatchingRule;
    }

    // create a bulk store function that allows multiple accounts to be associated with a matching rule
    public function bulkStore(CreateAccountMatchingRuleRequestBulk $request)
    {
        $input = $request->all();
        $accounts = $input['account_ids'];
        $matchingRule = $input['matching_rule_id'];
        $out = [];
        foreach ($accounts as $account) {
            $_input = [
                'account_id' => $account,
                'matching_rule_id' => $matchingRule,
            ];
            $accountMatchingRule = $this->_store($_input);
            $out[] = $accountMatchingRule->id;
        }
        Flash::success('Matching Rule ' . $matchingRule . ' applied to ' . implode(', ', $out) . ' accounts.');
        return redirect(route('accountMatchingRules.index'));
    }


    public function show($id)
    {
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            Flash::error('Account Matching Rule not found');

            return redirect(route('accountMatchingRules.index'));
        }
        $api = [
            'mr' => $accountMatchingRule->matchingRule()->first(),
            'account' => $accountMatchingRule->account()->first(),
        ];



        return view('account_matching_rules.show')
            ->with('accountMatchingRule', $accountMatchingRule)
            ->with('api', $api);
    }

    /**
     * Resend the email notification for an AccountMatchingRule.
     *
     * @param int $id
     * @return Response
     */
    public function resendEmail($id)
    {
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            Flash::error('Account Matching Rule not found');
            return redirect(route('accountMatchingRules.index'));
        }

        $account = $accountMatchingRule->account()->first();
        $to = $account->email_cc;

        if (empty($to)) {
            Flash::error('No email address configured for account: ' . $account->nickname);
            return redirect()->back();
        }

        $api = [
            'mr' => $accountMatchingRule->matchingRule()->first(),
            'account' => $account,
        ];

        $mail = new AccountMatchingRuleEmail($accountMatchingRule, $api);
        $error = $this->sendMail($mail, $to);

        if ($error) {
            Flash::error('Failed to send email: ' . $error);
        } else {
            Flash::success('Email sent successfully to ' . $to);
        }

        return redirect()->back();
    }

    /**
     * Show the form for editing the specified AccountMatchingRule.
     * Enhanced to include account selector and matching rule options like create.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            Flash::error('Account Matching Rule not found');
            return redirect(route('accountMatchingRules.index'));
        }

        $api = array_merge(
            $this->getAccountSelectorData(),
            ['mr' => MatchingRuleExt::ruleMap()]
        );

        return view('account_matching_rules.edit')
            ->with('accountMatchingRule', $accountMatchingRule)
            ->with('api', $api);
    }

    /**
     * Update the specified AccountMatchingRule in storage.
     * Sends email notification when changes are made.
     *
     * @param int $id
     * @param UpdateAccountMatchingRuleRequest $request
     * @return Response
     */
    public function update($id, UpdateAccountMatchingRuleRequest $request)
    {
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            Flash::error('Account Matching Rule not found');
            return redirect(route('accountMatchingRules.index'));
        }

        $oldAccountId = $accountMatchingRule->account_id;
        $oldMatchingRuleId = $accountMatchingRule->matching_rule_id;

        $input = $request->all();
        $accountMatchingRule = $this->accountMatchingRuleRepository->update($input, $id);

        // Check if account or matching rule changed
        $accountChanged = $oldAccountId != $accountMatchingRule->account_id;
        $ruleChanged = $oldMatchingRuleId != $accountMatchingRule->matching_rule_id;

        if ($accountChanged || $ruleChanged) {
            // Send notification email to the (new) account owner
            $api = [
                'mr' => $accountMatchingRule->matchingRule()->first(),
                'account' => $accountMatchingRule->account()->first(),
            ];
            $to = $accountMatchingRule->account()->first()->email_cc;
            if ($to) {
                $mail = new AccountMatchingRuleEmail($accountMatchingRule, $api);
                $this->sendMail($mail, $to);
            }
        }

        Flash::success('Account Matching Rule updated successfully.');
        return redirect(route('accountMatchingRules.index'));
    }

    /**
     * Remove the specified AccountMatchingRule from storage.
     * Sends email notification before deleting.
     *
     * @param int $id
     * @throws \Exception
     * @return Response
     */
    public function destroy($id)
    {
        $accountMatchingRule = $this->accountMatchingRuleRepository->find($id);

        if (empty($accountMatchingRule)) {
            Flash::error('Account Matching Rule not found');
            return redirect(route('accountMatchingRules.index'));
        }

        // Get account and rule info before deleting
        $account = $accountMatchingRule->account()->first();
        $matchingRule = $accountMatchingRule->matchingRule()->first();
        $to = $account->email_cc;

        // Send removal notification email
        if ($to) {
            $api = [
                'mr' => $matchingRule,
                'account' => $account,
            ];
            $mail = new AccountMatchingRuleRemovedEmail($api);
            $error = $this->sendMail($mail, $to);

            if ($error) {
                Flash::warning('Account Matching Rule deleted, but email notification failed: ' . $error);
            } else {
                Flash::success('Account Matching Rule deleted successfully. Notification sent to ' . $to);
            }
        } else {
            Flash::success('Account Matching Rule deleted successfully. (No email configured for account)');
        }

        $this->accountMatchingRuleRepository->delete($id);

        return redirect(route('accountMatchingRules.index'));
    }

}
