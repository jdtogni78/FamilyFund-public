<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TradePortfolioItemResource extends JsonResource
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
            'trade_portfolio_id' => $this->trade_portfolio_id,
            'symbol' => $this->symbol,
            'type' => $this->type,
            'target_share' => $this->target_share,
            'deviation_trigger' => $this->deviation_trigger,
            'display_category' => $this->display_category,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
