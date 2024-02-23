<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Class FundExt
 * @package App\Models
 */
class TradePortfolioExt extends TradePortfolio
{
    public static $split_rules = [
//        'id' => 'required',
        'start_dt' => 'required',
        'end_dt' => 'required',
    ];

    public function portfolio()
    {
        $portfolios = TradePortfolio::portfolio($this);
        if ($portfolios->count() > 1)
            throw new \Exception("Every trade portfolio must have at most 1 portfolio (found " . $portfolios->count() . ")");
        return $portfolios->first();
    }

    public function splitWithItems($start_dt, $end_dt)
    {
        $current = $this;
        $newTp = $current->replicate();
        $today = Carbon::today();
        $end_dt = new Carbon($end_dt);
        $start_dt = new Carbon($start_dt);

        if ($today->gt($start_dt))
            throw new \Exception("Start date ($start_dt) must be greater than today ($today)");
        if ($start_dt->lte($current->start_dt))
            throw new \Exception("Start date ($start_dt) must be greater than previous start date ($current->start_dt)");
        if ($start_dt->gt($current->end_dt))
            throw new \Exception("Start date ($start_dt) cannot be greater than previous end date ($current->end_dt)");
        if ($today->gte($end_dt))
            throw new \Exception("End date ($end_dt) must be greater than today ($today)");
        if ($end_dt->lt($start_dt))
            throw new \Exception("End date ($end_dt) must be greater than start date ($start_dt)");
        if ($end_dt->lt($current->end_dt))
            throw new \Exception("End date ($end_dt) must be greater than previous end date ($current->end_dt)");

        $current->end_dt = $start_dt;
        $current->save();

        // create new trade portfolio
        $newTp->start_dt = $start_dt;
        $newTp->end_dt = $end_dt;
        $newTp->save();

        // replicate all items
        $items = $current->tradePortfolioItems()->get();
        foreach ($items as $item) {
            $newItem = $item->replicate();
            $newItem->trade_portfolio_id = $newTp->id;
            $newItem->save();
        }

        return $newTp;
    }
}
