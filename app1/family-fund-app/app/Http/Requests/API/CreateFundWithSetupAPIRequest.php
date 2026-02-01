<?php

namespace App\Http\Requests\API;

use App\Models\Portfolio;

class CreateFundWithSetupAPIRequest extends BaseAPIRequest
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
            // Fund details
            'name' => 'required|string|max:30',
            'goal' => 'nullable|string|max:1024',

            // Account details
            'account_nickname' => 'nullable|string|max:100',

            // Portfolio details - can be string or array
            'portfolio_source' => 'required',
            'portfolio_source.*' => 'string|max:30',

            // Transaction details
            'create_initial_transaction' => 'nullable|boolean',
            'initial_shares' => 'nullable|numeric|min:0.00000001|max:9999999999999.9991',
            'initial_value' => 'nullable|numeric|min:0.01|max:99999999999.99',
            'transaction_description' => 'nullable|string|max:255',

            // Preview/dry run mode
            'dry_run' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Fund name is required',
            'name.max' => 'Fund name must not exceed 30 characters',
            'portfolio_source.required' => 'Portfolio source is required',
            'initial_shares.min' => 'Initial shares must be greater than 0',
            'initial_value.min' => 'Initial value must be at least 0.01',
        ];
    }
}
