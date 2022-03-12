<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetPriceResource extends JsonResource
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
            'asset_id' => $this->asset_id,
            'price' => $this->price,
            'start_dt' => $this->start_dt,
            'end_dt' => $this->end_dt,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at
        ];
    }
}
