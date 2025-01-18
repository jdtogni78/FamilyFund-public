<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CashDeposit;

class AssignDepositRequestsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];
        $rules['unassigned'] = 'required|numeric|min:0';
        $rules['deposits']               = 'required|array';
        $rules['deposits.*.description'] = 'nullable|string';
        $rules['deposits.*.amount']      = 'required|numeric|min:0';
        $rules['deposits.*.account_id']  = 'required|exists:accounts,id';
        $rules['deposit_ids']   = 'required|array';
        $rules['deposit_ids.*'] = 'required|exists:deposit_requests,id';
        return $rules;
    }
}
