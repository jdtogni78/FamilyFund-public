<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Person
 * @package App\Models
 * @version January 6, 2025, 1:02 am UTC
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

    public $table = 'people';
    

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
}
