<?php

namespace App\Models;

/**
 * Class FundExt
 * @package App\Models
 */
class TradePortfolioExt extends Fund
{
    public function fund()
    {
        $funds = $this->funds();
        if ($funds->count() != 1)
            throw new \Exception("Every trade portfosio must have 1 fund (found " . $funds->count() . ")");
        return $funds->first();
    }
}
