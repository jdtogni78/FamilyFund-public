<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountContactPerson extends Model
{
    protected $fillable = ['account_id', 'person_id'];
    protected $table = 'account_contact_persons';

    public function account()
    {
        return $this->belongsTo(\App\Models\Account::class);
    }

    public function person()
    {
        return $this->belongsTo(\App\Models\Person::class);
    }
}
