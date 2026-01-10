<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class IdDocument
 * @package App\Models
 * @version January 6, 2025, 1:17 am UTC
 *
 * @property \App\Models\Person $person
 * @property integer $person_id
 * @property string $type
 * @property string $number
 */
class IdDocument extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'iddocuments';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'person_id',
        'type',
        'number'
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
        'number' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'person_id' => 'required|exists:persons,id',
        'type' => 'required|in:CPF,RG,CNH,Passport,SSN,other',
        'number' => 'required|string|max:50'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function person()
    {
        return $this->belongsTo(\App\Models\Person::class, 'person_id', 'id');
    }
}
