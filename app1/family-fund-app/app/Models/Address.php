<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'person_id',
        'type',
        'is_primary',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'country'
    ];

    protected $casts = [
        'is_primary' => 'boolean'
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
} 