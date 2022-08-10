<?php

namespace App\Http\Requests\API;

use App\Http\Controllers\Traits\CashValidator;
use App\Models\AssetPrice;

class CreateAssetPriceAPIRequest extends BaseAPIRequest
{
    use CashValidator;

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
        return AssetPrice::$rules;
    }
}
