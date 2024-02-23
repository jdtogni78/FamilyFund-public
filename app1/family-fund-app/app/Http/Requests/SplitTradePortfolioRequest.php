<?php

namespace App\Http\Requests;

use App\Models\TradePortfolioExt;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\TradePortfolio;

class SplitTradePortfolioRequest extends FormRequest
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
        $rules = TradePortfolioExt::$split_rules;

        return $rules;
    }
}
