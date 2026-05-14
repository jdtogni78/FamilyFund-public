<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReverseTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (auth()->user()?->is_admin());
    }

    public function rules(): array
    {
        return [
            'transaction_id' => 'required|integer|exists:transactions,id',
            'reason'         => 'required|string|min:1|max:1000',
        ];
    }
}
