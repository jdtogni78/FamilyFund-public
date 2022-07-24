<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TradePortfolioResource extends JsonResource
{
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
            'cash_target' => $this->cash_target,
            'cash_reserve_target' => $this->cash_reserve_target,
            'max_single_order' => $this->max_single_order,
            'minimum_order' => $this->minimum_order,
            'rebalance_period' => $this->rebalance_period,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at
        ];
    }
}
