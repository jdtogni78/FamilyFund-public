<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,
            'value' => $this->value,
            'shares' => $this->shares,
            'timestamp' => $this->timestamp,
            'account_id' => $this->account_id,
//            'account' => $this->account(), // remove from api response
            'descr' => $this->descr,
            'flags' => $this->flags,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
