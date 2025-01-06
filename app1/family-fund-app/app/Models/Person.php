<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Person extends Model
{
    use HasFactory;
    
    protected $table = 'persons';
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'birthday',
        'legal_guardian_id'
    ];

    protected $casts = [
        'birthday' => 'date'
    ];

    public function phones()
    {
        return $this->hasMany(Phone::class);
    }

    public function idDocuments()
    {
        return $this->hasMany(IdDocument::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function primaryAddress()
    {
        return $this->hasOne(Address::class)->where('is_primary', true);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function legalGuardian()
    {
        return $this->belongsTo(Person::class, 'legal_guardian_id');
    }

    public function wards()
    {
        return $this->hasMany(Person::class, 'legal_guardian_id');
    }

    public function accountsAsBeneficiary()
    {
        return $this->hasMany(Account::class, 'beneficiary_id');
    }

    public function contactForAccounts()
    {
        return $this->belongsToMany(Account::class, 'account_contact_persons');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
} 