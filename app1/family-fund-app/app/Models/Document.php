<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdDocument extends Model
{
    protected $table = 'iddocuments';

    protected $fillable = [
        'person_id',
        'type',
        'number'
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
} 