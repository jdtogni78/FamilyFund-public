<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountBalanceResource extends JsonResource
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
            'shares' => $this->shares,
            'account_id' => $this->account_id,
            'account' => $this->account(),
            'transaction_id' => $this->transaction_id,
            'start_dt' => $this->start_dt,
            'end_dt' => $this->end_dt,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at
        ];
    }
}
