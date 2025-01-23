<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class AccountGoal
 * @package App\Models
 * @version January 21, 2025, 12:27 am UTC
 *
 * @property \App\Models\Account $account
 * @property \App\Models\Goal $goal
 * @property integer $account_id
 * @property integer $goal_id
 */
class AccountGoal extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'account_goals';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'account_id',
        'goal_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'goal_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'account_id' => 'required',
        'goal_id' => 'required',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function account()
    {
        return $this->belongsTo(\App\Models\Account::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function goal()
    {
        return $this->belongsTo(\App\Models\Goal::class, 'goal_id');
    }
}
