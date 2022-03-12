<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
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
            'fund_id' => $this->fund_id,
            'source' => $this->source,
//            'updated_at' => $this->updated_at,
//            'created_at' => $this->created_at,
//            'deleted_at' => $this->deleted_at
        ];
    }
}
