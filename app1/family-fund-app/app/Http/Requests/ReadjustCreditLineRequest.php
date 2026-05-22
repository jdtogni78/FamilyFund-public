<?php

namespace App\Http\Requests;

use App\Models\AccountCreditLine;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ReadjustCreditLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (auth()->user()?->is_admin());
    }

    public function rules(): array
    {
        return [
            'account_credit_line_id' => 'required|integer|exists:account_credit_lines,id',
            'new_term_months'        => 'nullable|integer|min:1|max:480',
            'new_payment_frequency'  => 'nullable|in:monthly,quarterly,annual',
            'effective_date'         => 'nullable|date',
            'reason'                 => 'nullable|string|max:1000',
        ];
    }

    /**
     * A readjust effective_date before the line's origination_date would
     * anchor the new schedule before the line existed, producing payment rows
     * due before origination and a malformed trajectory waveform. Reject it.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $effective = $this->input('effective_date');
            if (!$effective) {
                return;
            }

            $line = AccountCreditLine::find($this->input('account_credit_line_id'));
            if (!$line || !$line->origination_date) {
                return;
            }

            if (Carbon::parse($effective)->lt(Carbon::parse($line->origination_date))) {
                $validator->errors()->add(
                    'effective_date',
                    "The readjust start date cannot be before the line's origination date ("
                    . Carbon::parse($line->origination_date)->toDateString() . ').'
                );
            }
        });
    }
}
