<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * Class Person
 * @package App\Models
 * @version January 6, 2025, 1:17 am UTC
 *
 * @property \App\Models\Person $legalGuardian
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $birthday
 * @property integer $legal_guardian_id
 */
class Person extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'persons';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'first_name',
        'last_name',
        'email',
        'birthday',
        'legal_guardian_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'first_name' => 'string',
        'last_name' => 'string',
        'email' => 'string',
        'birthday' => 'date',
        'legal_guardian_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:persons',
        'birthday' => 'required|date',
        'legal_guardian_id' => 'nullable|exists:persons,id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function legalGuardian()
    {
        return $this->belongsTo(\App\Models\Person::class, 'legal_guardian_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function phones(): HasMany
    {
        return $this->hasMany(\App\Models\Phone::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function addresses(): HasMany
    {
        return $this->hasMany(\App\Models\Address::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function idDocuments(): HasMany
    {
        return $this->hasMany(\App\Models\IdDocument::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function guardedChildren(): HasMany
    {
        return $this->hasMany(\App\Models\Person::class, 'legal_guardian_id', 'id');
    }

    // full name
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return Phone
     **/
    public function primaryPhone(): ?Phone
    {
        return $this->phones()->where('is_primary', true)->first();
    }

    /**
     * @return Address
     **/
    public function primaryAddress(): ?Address
    {
        return $this->addresses()->where('is_primary', true)->first();
    }

}
