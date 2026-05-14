<?php

namespace App\Http\Requests;

use App\Models\TransactionExt;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UC-46: admin "create transaction with arbitrary timestamp".
 *
 * Differs from regular transaction creation in two ways:
 *  1. `timestamp` is fully user-controlled (no before_or_equal:tomorrow gate)
 *     so admins can backfill historical records or correct past data.
 *  2. All fields are explicit (no derivation) — `shares` may be supplied
 *     directly for share-denominated types (BOR/REP/MAT) and `value` for
 *     cash-denominated ones (PUR/SAL/INI).
 */
class AdminCreateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (auth()->user()?->is_admin());
    }

    public function rules(): array
    {
        return [
            'account_id'              => 'required|integer|exists:accounts,id',
            'account_credit_line_id'  => 'nullable|integer|exists:account_credit_lines,id',
            'type'                    => 'required|in:' . implode(',', [
                TransactionExt::TYPE_PURCHASE,
                TransactionExt::TYPE_SALE,
                TransactionExt::TYPE_INITIAL,
                TransactionExt::TYPE_MATCHING,
                TransactionExt::TYPE_BORROW,
                TransactionExt::TYPE_REPAY,
            ]),
            'status'                  => 'required|in:' . implode(',', [
                TransactionExt::STATUS_PENDING,
                TransactionExt::STATUS_CLEARED,
                TransactionExt::STATUS_SCHEDULED,
            ]),
            'value'                   => 'required|numeric',
            'shares'                  => 'nullable|numeric',
            'timestamp'               => 'required|date',
            'descr'                   => 'nullable|string|max:255',
        ];
    }
}
