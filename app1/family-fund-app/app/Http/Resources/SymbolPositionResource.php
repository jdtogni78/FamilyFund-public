<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SymbolPositionResource extends JsonResource
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
            'name' => $this->name,
            'type' => $this->type,
            'position' => $this->position
        ];
    }
}
