<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MatchingRuleResource extends JsonResource
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
            'name' => $this->name,
            'dollar_range_start' => $this->dollar_range_start,
            'dollar_range_end' => $this->dollar_range_end,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'match_percent' => $this->match_percent,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at
        ];
    }
}
