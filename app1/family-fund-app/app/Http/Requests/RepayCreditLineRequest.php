<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RepayCreditLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (auth()->user()?->is_admin());
    }

    public function rules(): array
    {
        return [
            'account_credit_line_id' => 'required|integer|exists:account_credit_lines,id',
            'shares'                 => 'required|numeric|min:0.0001',
            'date'                   => 'nullable|date',
        ];
    }
}
