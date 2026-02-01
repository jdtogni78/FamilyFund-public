<?php

namespace App\Http\Requests\API;

class CreatePriceUpdateAPIRequest extends BaseAPIRequest
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
            'source' => 'required|string|max:30|exists:portfolios,source',
            'timestamp' => 'required',
            'symbols' => 'required|array|min:1',
            'symbols.*.name' => 'required|string|not_in:CASH',
            'symbols.*.type' => 'required|string|not_in:CSH',
            'symbols.*.price' => 'required|numeric|gt:0|lt:99999999999.99',
            'symbols.*.position' => 'prohibited',
        ];
    }
}
