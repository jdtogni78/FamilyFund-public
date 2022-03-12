<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetChangeLogResource extends JsonResource
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
            'action' => $this->action,
            'asset_id' => $this->asset_id,
            'field' => $this->field,
            'content' => $this->content,
            'datetime' => $this->datetime,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at
        ];
    }
}
