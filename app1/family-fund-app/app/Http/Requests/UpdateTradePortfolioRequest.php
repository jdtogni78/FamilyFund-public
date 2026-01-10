<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TradePortfolio;

class UpdateTradePortfolioRequest extends FormRequest
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
        $rules = TradePortfolio::$rules;

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hasOverlappingDates()) {
                $validator->errors()->add('start_dt', 'Date range overlaps with another trade portfolio for this portfolio.');
            }
        });
    }

    /**
     * Check if the date range overlaps with existing trade portfolios.
     */
    protected function hasOverlappingDates(): bool
    {
        $portfolioId = $this->input('portfolio_id');
        $startDt = $this->input('start_dt');
        $endDt = $this->input('end_dt');
        $currentId = $this->route('tradePortfolio');

        if (!$portfolioId || !$startDt || !$endDt) {
            return false;
        }

        // Check for overlapping date ranges (start1 <= end2 AND start2 <= end1)
        return TradePortfolio::where('portfolio_id', $portfolioId)
            ->where('id', '!=', $currentId)
            ->where('start_dt', '<=', $endDt)
            ->where('end_dt', '>=', $startDt)
            ->exists();
    }
}
