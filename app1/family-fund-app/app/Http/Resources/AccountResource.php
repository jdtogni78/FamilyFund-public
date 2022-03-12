<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'code' => $this->code,
            'nickname' => $this->nickname,
            'email_cc' => $this->email_cc,
            // 'user_id' => $this->user_id,
            // 'fund_id' => $this->fund_id,
            // 'updated_at' => $this->updated_at,
            // 'created_at' => $this->created_at,
            // 'deleted_at' => $this->deleted_at
        ];
    }
}
