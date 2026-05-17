<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCreditLinePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (auth()->user()?->is_admin());
    }

    public function rules(): array
    {
        return [
            // Editable amount — defaults to the row's shares_due in the UI,
            // but the admin may record an under/over payment.
            'shares' => 'required|numeric|min:0.0001',
            // Real settlement date as reported by the external system.
            // Backdating allowed; future dating is not.
            'date'   => 'nullable|date|before_or_equal:today',
        ];
    }
}
