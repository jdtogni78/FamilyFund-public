<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveCreditLineMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (auth()->user()?->is_admin());
    }

    public function rules(): array
    {
        return [
            'transaction_id'         => 'required|integer|exists:transactions,id',
            'account_credit_line_id' => 'required|integer|exists:account_credit_lines,id',
        ];
    }
}
