<?php

namespace App\Http\Requests;

use App\Models\AccountExt;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CreateAccountCreditLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (auth()->user()?->is_admin());
    }

    public function rules(): array
    {
        return [
            'account_id'            => 'required|integer|exists:accounts,id',
            'principal_shares'      => 'required|numeric|min:0.0001',
            'term_months'           => 'required|integer|min:1|max:480',
            'payment_frequency'     => 'required|in:monthly,quarterly,annual',
            // Backdating is allowed: FF only adjusts allocated/unallocated
            // shares, no cash/receivable is modelled. Future-dating is not —
            // this flow records draws that have already happened.
            'origination_date'      => 'nullable|date|before_or_equal:today',
            'descr'                 => 'nullable|string|max:255',
            'imputed_interest_rate' => 'nullable|numeric|min:0|max:1',
        ];
    }

    /**
     * Enforce the draw cap (UC-03) at validation time so the user gets a
     * field-level error instead of a flashed exception after submit. The cap
     * is evaluated as of the origination date — a backdated draw is checked
     * against the OWN balance on that date, matching DrawService::open().
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return; // base rules failed — account_id / principal not trustworthy yet
            }

            $account = AccountExt::find($this->input('account_id'));
            if (!$account) {
                return;
            }

            $originationDate = $this->filled('origination_date')
                ? Carbon::parse($this->input('origination_date'))
                : Carbon::today();

            $available = app(OutstandingCalculator::class)
                ->availableToBorrow($account, $originationDate);

            if ((float) $this->input('principal_shares') > $available) {
                $validator->errors()->add(
                    'principal_shares',
                    sprintf(
                        'Only %.4f shares available to borrow as of %s '
                        . '(OWN minus outstanding on active lines).',
                        round($available, 4),
                        $originationDate->toDateString()
                    )
                );
            }
        });
    }
}
