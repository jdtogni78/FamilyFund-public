<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Fund;

class CreateFundWithSetupRequest extends FormRequest
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
            // Fund fields (from Fund::$rules)
            'name' => 'required|string|max:30',
            'goal' => 'nullable|string|max:1024',

            // Account fields
            'account_nickname' => 'nullable|string|max:100',

            // Portfolio fields (can be single or array)
            'portfolio_source' => 'required',
            'portfolio_source.*' => 'required|string|max:30',

            // Transaction fields
            'create_initial_transaction' => 'nullable|boolean',
            'initial_shares' => 'nullable|numeric|min:0.00000001|max:9999999999999.9991',
            'initial_value' => 'nullable|numeric|min:0.01|max:99999999999.99',
            'transaction_description' => 'nullable|string|max:255',

            // Preview mode
            'preview' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom attribute names for validation errors
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'portfolio_source' => 'portfolio source',
            'portfolio_source.*' => 'portfolio source',
            'initial_shares' => 'initial shares',
            'initial_value' => 'initial value',
            'account_nickname' => 'account nickname',
            'transaction_description' => 'transaction description',
        ];
    }

    /**
     * Get custom messages for validation errors
     *
     * @return array
     */
    public function messages()
    {
        return [
            'portfolio_source.required' => 'At least one portfolio source is required.',
            'portfolio_source.*.required' => 'Portfolio source cannot be empty.',
            'initial_shares.min' => 'Initial shares must be at least 0.00000001.',
            'initial_value.min' => 'Initial value must be at least $0.01.',
        ];
    }
}
