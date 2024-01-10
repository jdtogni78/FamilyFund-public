<?php

namespace App\Models;

/**
 * Class FundExt
 * @package App\Models
 */
class TradePortfolioExt extends TradePortfolio
{
    public function fund()
    {
        $funds = TradePortfolio::fund($this);
        if ($funds->count() > 1)
            throw new \Exception("Every trade portfosio must have at most 1 fund (found " . $funds->count() . ")");
        return $funds->first();
    }
}
