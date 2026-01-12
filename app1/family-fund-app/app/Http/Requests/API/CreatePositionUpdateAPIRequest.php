<?php

namespace App\Http\Requests\API;

class CreatePositionUpdateAPIRequest extends BaseAPIRequest
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
            'symbols.*.name' => 'required|string',
            'symbols.*.type' => 'required|string',
            'symbols.*.position' => 'required|numeric|gt:0|lt:9999999999999.9991',
            'symbols.*.price' => 'prohibited',
        ];
    }
}
