<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Goal
 * @package App\Models
 * @version January 21, 2025, 12:27 am UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $accountGoals
 * @property string $name
 * @property string $description
 * @property string $start_dt
 * @property string $end_dt
 * @property string $target_type
 * @property number $target_amount
 * @property number $target_pct
 */
class Goal extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'goals';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'name',
        'description',
        'start_dt',
        'end_dt',
        'target_type',
        'target_amount',
        'target_pct'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'start_dt' => 'date',
        'end_dt' => 'date',
        'target_type' => 'string',
        'target_amount' => 'decimal:2',
        'target_pct' => 'decimal:2'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:30',
        'description' => 'nullable|string|max:1024',
        'start_dt' => 'required',
        'end_dt' => 'required',
        'target_type' => 'required|string|max:10',
        'target_amount' => 'required|numeric',
        'target_pct' => 'required|numeric',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function accounts()
    {
        return $this->belongsToMany(\App\Models\Account::class, 'account_goals', 'goal_id', 'account_id');
    }
}
