<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class AccountReport
 * @package App\Models
 * @version March 28, 2022, 2:48 am UTC
 *
 * @property \App\Models\Account $account
 * @property integer $account_id
 * @property string $type
 * @property string $as_of
 */
class AccountReport extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'account_reports';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'account_id',
        'type',
        'as_of'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'type' => 'string',
        'as_of' => 'date'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'account_id' => 'required',
        'type' => 'required|string|max:3',
        'as_of' => 'required',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function account()
    {
        return $this->belongsTo(\App\Models\Account::class, 'account_id');
    }
}
