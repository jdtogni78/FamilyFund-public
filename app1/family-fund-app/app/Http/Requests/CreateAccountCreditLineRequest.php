<?php

namespace App\Http\Requests;

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
            'descr'                 => 'nullable|string|max:255',
            'imputed_interest_rate' => 'nullable|numeric|min:0|max:1',
        ];
    }
}
