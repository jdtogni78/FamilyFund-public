<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RebalanceTradePortfolioRequest extends FormRequest
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
        return [
            // Dates
            'start_dt' => 'required|date',
            'end_dt' => 'required|date|after:start_dt',

            // Portfolio settings
            'cash_target' => 'required|numeric|between:0,0.99',
            'cash_reserve_target' => 'required|numeric|between:0,0.99',
            'rebalance_period' => 'required|integer|min:1',
            'mode' => 'required|in:STD,MAX',
            'minimum_order' => 'required|numeric|min:0',
            'max_single_order' => 'required|numeric|min:0',

            // Items array
            'items' => 'required|array|min:1',
            'items.*.symbol' => 'required|string|max:50',
            'items.*.type' => 'required|in:STK,FUND,CRYPTO,OTHER',
            'items.*.target_share' => 'required|numeric|between:0,1',
            'items.*.deviation_trigger' => 'required|numeric|between:0,1',
            'items.*.deleted' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'items.required' => 'At least one portfolio item is required.',
            'items.min' => 'At least one portfolio item is required.',
            'items.*.target_share.between' => 'Target share must be between 0 and 1 (e.g., 0.10 for 10%).',
            'items.*.deviation_trigger.between' => 'Deviation trigger must be between 0 and 1.',
        ];
    }
}
