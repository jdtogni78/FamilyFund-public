<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TradePortfolioResource extends JsonResource
{
    /**
     * Get the portfolio nickname (fund name) handling both Collection and single model.
     */
    private function getPortfolioNickname(): ?string
    {
        // Use portfolio_id to get the portfolio directly (avoids broken override in TradePortfolioExt)
        if ($this->portfolio_id) {
            $portfolio = \App\Models\PortfolioExt::find($this->portfolio_id);
            return $portfolio?->fund?->name;
        }
        return null;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'account_name' => $this->account_name,
            'nickname' => $this->getPortfolioNickname(),
            'portfolio_id' => $this->portfolio_id,
            'tws_query_id' => $this->tws_query_id,
            'tws_token' => $this->tws_token,
            'cash_target' => $this->cash_target,
            'cash_reserve_target' => $this->cash_reserve_target,
            'max_single_order' => $this->max_single_order,
            'minimum_order' => $this->minimum_order,
            'rebalance_period' => $this->rebalance_period,
            'mode' => $this->mode,
            'items' => TradePortfolioItemResource::collection($this->tradePortfolioItems),
            'start_dt' => $this->start_dt->format('Y-m-d'),
            'end_dt' => $this->end_dt->format('Y-m-d'),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at
        ];
    }
}
