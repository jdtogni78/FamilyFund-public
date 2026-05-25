<?php

namespace App\Http\Controllers\Traits;

use App\Models\AccountExt;
use App\Models\Fund;
use App\Services\AuthorizationService;
use Illuminate\Support\Facades\Auth;

trait AccountSelectorTrait
{
    /**
     * Get account and fund data for the fund_account_selector component.
     *
     * The result feeds filter dropdowns (account + fund). It MUST be scoped to
     * the viewer: without scoping the dropdown leaked every account — including
     * other tenants' owner names and emails (PII) — even on pages whose table
     * was correctly scoped (e.g. the transactions index a beneficiary can
     * reach). System admins still see everything; full-access users see their
     * funds; a beneficiary sees only their own account(s) and fund.
     */
    protected function getAccountSelectorData(): array
    {
        $authService = new AuthorizationService(Auth::user());

        $accounts = $authService->scopeAccountsQuery(
            AccountExt::with(['user', 'fund'])->orderBy('nickname')
        )->get();

        $accountsWithFund = $accounts->map(function ($a) {
            $label = $a->nickname;
            $details = [];
            if ($a->code) {
                $details[] = $a->code;
            }
            if ($a->user) {
                $details[] = $a->user->name;
                if ($a->user->email) {
                    $details[] = $a->user->email;
                }
            }
            if ($a->fund) {
                $details[] = $a->fund->name;
            }
            if (!empty($details)) {
                $label .= ' (' . implode(' | ', $details) . ')';
            }
            return [
                'id' => $a->id,
                'nickname' => $a->nickname,
                'label' => $label,
                'fund_id' => $a->fund_id,
            ];
        })->toArray();

        // Build accountMap from the SAME scoped collection (mirrors
        // AccountExt::accountMap()'s label format) so it can't leak unscoped.
        $accountMap = [null => 'Select an Account'];
        foreach ($accounts as $account) {
            $label = $account->nickname;
            if ($account->code) {
                $label .= ' (' . $account->code . ')';
            }
            if ($account->user) {
                $label .= ' - ' . $account->user->name;
            }
            $accountMap[$account->id] = $label;
        }

        $fundMap = $authService->scopeFundsQuery(Fund::query())
            ->pluck('name', 'id')
            ->toArray();

        return [
            'accountMap' => $accountMap,
            'accountsWithFund' => $accountsWithFund,
            'fundMap' => $fundMap,
        ];
    }
}
