<?php

namespace App\Models;

use Carbon\Carbon;
use Log;

/**
 * Class FundExt
 * @package App\Models
 */
class TradePortfolioExt extends TradePortfolio
{
    public array $groups;
    public static $split_rules = [
//        'id' => 'required',
        'start_dt' => 'required',
        'end_dt' => 'required',
    ];

    public static function portMap() {
        $portfolios = TradePortfolio::all();
        $map = ['none' => 'Select Portfolio'];
        foreach ($portfolios as $portfolio) {
            $map[$portfolio->id] = $portfolio->id 
                . ' ' . $portfolio->source . ' '
                . ' ' . $portfolio->portfolio->fund->name . ' '
                . ' (' . $portfolio->start_dt . ' - ' . $portfolio->end_dt . ')';
        }
        return $map;
    }

    public function previous()
    {
        $tp = TradePortfolio::where('start_dt', '<', $this->start_dt)
            ->where('portfolio_id', $this->portfolio_id)
            ->orderBy('start_dt', 'desc')
            ->first();
        return $tp;
    }

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

    public function annotateTotalShares()
    {
        // sum total shares
        $total = $this->cash_target;
        /** @var TradePortfolioItem $item */
        foreach ($this->items as $item) {
            $total += $item->target_share;
        }
        $this->total_shares = $total * 100.0;
    }

    public function annotateAssetsAndGroups() {
        $items = $this->tradePortfolioItems()->get();
        $groups = [];
        /** @var TradePortfolioItem $item */
        foreach ($items as $item) {
            $asset = AssetExt::getAsset($item->symbol, $item->type);
            $group = $asset->display_group;
            $item->group = $group;
            // add asset as key to group
            if (!array_key_exists($group, $groups)) {
                $groups[$group] = 0;
            }
            $groups[$group] += $item->target_share * 100;
        }
        $asset = AssetExt::getCashAsset();
        if (!array_key_exists($asset->display_group, $groups)) {
            $groups[$asset->display_group] = 0;
        }
        $groups[$asset->display_group] += (float)($this->cash_target * 100);

        $this->groups = $groups;
        $this->items = $items;
    }
}
