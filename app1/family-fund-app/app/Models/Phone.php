<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Phone
 * @package App\Models
 * @version January 6, 2025, 1:17 am UTC
 *
 * @property \App\Models\Person $person
 * @property integer $person_id
 * @property string $number
 * @property string $type
 * @property boolean $is_primary
 */
class Phone extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'phones';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'person_id',
        'number',
        'type',
        'is_primary'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'person_id' => 'integer',
        'number' => 'string',
        'type' => 'string',
        'is_primary' => 'boolean'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'person_id' => 'required|exists:persons,id',
        'number' => 'required|string|max:20',
        'type' => 'required|in:mobile,home,work,other',
        'is_primary' => 'boolean|default:false'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function person()
    {
        return $this->belongsTo(\App\Models\Person::class, 'person_id', 'id');
    }
}
