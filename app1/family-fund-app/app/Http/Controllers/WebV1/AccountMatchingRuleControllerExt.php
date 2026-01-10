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
use App\Models\MatchingRuleExt;
use App\Models\AccountExt;

class AccountMatchingRuleControllerExt extends AccountMatchingRuleController
{
    use MailTrait;

    /**
     * Show the form for creating a new AccountMatchingRule.
     *
     * @return Response
     */
    public function create()
    {
        $api = [
            'mr' => MatchingRuleExt::ruleMap(),
            'account' => AccountExt::accountMap()
        ];
        return view('account_matching_rules.create')->with('api', $api);
    }

    public function bulkCreate()
    {
        $api = [
            'mr' => MatchingRuleExt::ruleMap(),
            'account' => AccountExt::accountMap()
        ];
        unset($api['account'][null]);
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

}
