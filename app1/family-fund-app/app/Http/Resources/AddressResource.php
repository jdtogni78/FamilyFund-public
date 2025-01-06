<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'person_id' => $this->person_id,
            'type' => $this->type,
            'is_primary' => $this->is_primary,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'county' => $this->county,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
