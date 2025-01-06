<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Address
 * @package App\Models
 * @version January 6, 2025, 1:17 am UTC
 *
 * @property \App\Models\Person $person
 * @property integer $person_id
 * @property string $type
 * @property boolean $is_primary
 * @property string $street
 * @property string $number
 * @property string $complement
 * @property string $neighborhood
 * @property string $city
 * @property string $state
 * @property string $zip_code
 * @property string $country
 */
class Address extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'addresses';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
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

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'person_id' => 'integer',
        'type' => 'string',
        'is_primary' => 'boolean',
        'street' => 'string',
        'number' => 'string',
        'complement' => 'string',
        'neighborhood' => 'string',
        'city' => 'string',
        'state' => 'string',
        'zip_code' => 'string',
        'country' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'person_id' => 'required|exists:persons,id',
        'type' => 'required|in:home,work,other',
        'is_primary' => 'boolean',
        'street' => 'required|string|max:255',
        'number' => 'required|string|max:20',
        'complement' => 'nullable|string|max:255',
        'neighborhood' => 'required|string|max:255',
        'city' => 'required|string|max:255',
        'state' => 'required|string|max:2',
        'zip_code' => 'required|string|max:10',
        'country' => 'required|string|max:255'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function person()
    {
        return $this->belongsTo(\App\Models\Person::class, 'person_id', 'id');
    }
}
