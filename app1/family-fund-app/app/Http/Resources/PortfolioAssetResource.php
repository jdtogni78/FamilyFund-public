<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioAssetResource extends JsonResource
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
            'portfolio_id' => $this->portfolio_id,
            'asset_id' => $this->asset_id,
            'asset' => $this->asset(),
            'position' => $this->position,
            'start_dt' => $this->start_dt,
            'end_dt' => $this->end_dt,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at
        ];
    }
}
