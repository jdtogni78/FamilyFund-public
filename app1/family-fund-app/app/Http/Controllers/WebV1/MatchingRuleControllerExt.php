<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Requests\CreateMatchingRuleRequest;
use App\Repositories\MatchingRuleRepository;
use App\Repositories\AccountMatchingRuleRepository;
use App\Http\Controllers\MatchingRuleController;
use App\Models\AccountExt;
use App\Models\Fund;
use Illuminate\Http\Request;
use Flash;

class MatchingRuleControllerExt extends MatchingRuleController
{
    protected $accountMatchingRuleRepository;

    public function __construct(
        MatchingRuleRepository $matchingRuleRepo,
        AccountMatchingRuleRepository $accountMatchingRuleRepo
    ) {
        parent::__construct($matchingRuleRepo);
        $this->accountMatchingRuleRepository = $accountMatchingRuleRepo;
    }

    /**
     * Show the form for cloning a MatchingRule.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function clone($id)
    {
        $matchingRule = $this->matchingRuleRepository->find($id);

        if (empty($matchingRule)) {
            Flash::error('Matching Rule not found');
            return redirect(route('matchingRules.index'));
        }

        // Get accounts already assigned to this rule
        $assignedAccountIds = $matchingRule->accountMatchingRules()
            ->pluck('account_id')
            ->toArray();

        // Get only beneficiary accounts (with user_id), grouped by fund
        $accounts = AccountExt::with(['user', 'fund'])
            ->whereNotNull('user_id')
            ->orderBy('fund_id')
            ->orderBy('nickname')
            ->get()
            ->map(function ($account) {
                $label = $account->nickname;
                if ($account->code) {
                    $label .= ' (' . $account->code . ')';
                }
                if ($account->user) {
                    $label .= ' - ' . $account->user->name;
                }
                return [
                    'id' => $account->id,
                    'label' => $label,
                    'fund_id' => $account->fund_id,
                    'fund_name' => $account->fund->name ?? 'Unknown',
                ];
            });

        // Get funds for filter dropdown
        $funds = Fund::orderBy('name')->pluck('name', 'id');

        return view('matching_rules.clone')
            ->with('matchingRule', $matchingRule)
            ->with('accounts', $accounts)
            ->with('funds', $funds)
            ->with('assignedAccountIds', $assignedAccountIds);
    }

    /**
     * Store the cloned MatchingRule with account assignments.
     *
     * @param CreateMatchingRuleRequest $request
     * @return \Illuminate\Http\Response
     */
    public function storeClone(CreateMatchingRuleRequest $request)
    {
        $input = $request->only([
            'name',
            'dollar_range_start',
            'dollar_range_end',
            'date_start',
            'date_end',
            'match_percent'
        ]);

        // Create the new matching rule
        $matchingRule = $this->matchingRuleRepository->create($input);

        // Assign to selected accounts
        $accountIds = $request->input('account_ids', []);
        $assignedCount = 0;

        foreach ($accountIds as $accountId) {
            $this->accountMatchingRuleRepository->create([
                'account_id' => $accountId,
                'matching_rule_id' => $matchingRule->id,
            ]);
            $assignedCount++;
        }

        $message = 'Matching Rule cloned successfully.';
        if ($assignedCount > 0) {
            $message .= " Assigned to {$assignedCount} account(s).";
        }

        Flash::success($message);

        return redirect(route('matchingRules.index'));
    }
}
