<?php

namespace App\Http\Controllers\Traits;

use App\Models\AccountExt;
use App\Models\Fund;

trait AccountSelectorTrait
{
    /**
     * Get account and fund data for the fund_account_selector component
     */
    protected function getAccountSelectorData(): array
    {
        $accounts = AccountExt::with(['user', 'fund'])->orderBy('nickname')->get();
        $accountsWithFund = $accounts->map(function($a) {
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

        return [
            'accountMap' => AccountExt::accountMap(),
            'accountsWithFund' => $accountsWithFund,
            'fundMap' => Fund::pluck('name', 'id')->toArray(),
        ];
    }
}
