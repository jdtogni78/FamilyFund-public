<?php

namespace App\Models;

/**
 * Class FundExt
 * @package App\Models
 */
class TradePortfolioExt extends TradePortfolio
{
    public function portfolio()
    {
        $portfolios = TradePortfolio::portfolio($this);
        if ($portfolios->count() > 1)
            throw new \Exception("Every trade portfolio must have at most 1 portfolio (found " . $portfolios->count() . ")");
        return $portfolios->first();
    }
}
