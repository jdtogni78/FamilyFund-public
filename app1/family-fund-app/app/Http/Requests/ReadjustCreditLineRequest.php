<?php

namespace App\Http\Requests;

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
            'reason'                 => 'nullable|string|max:1000',
        ];
    }
}
