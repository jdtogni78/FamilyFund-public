<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Goal
 * @package App\Models
 * @version January 20, 2025, 10:51 pm UTC
 *
 * @property \App\Models\Account $account
 * @property string $name
 * @property string $description
 * @property string $start_dt
 * @property string $end_dt
 * @property string $target_type
 * @property number $target_amount
 * @property number $pct4
 * @property integer $account_id
 */
class Goal extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'goals';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'name',
        'description',
        'start_dt',
        'end_dt',
        'target_type',
        'target_amount',
        'pct4',
        'account_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'start_dt' => 'date',
        'end_dt' => 'date',
        'target_type' => 'string',
        'target_amount' => 'decimal:2',
        'pct4' => 'decimal:2',
        'account_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:30',
        'description' => 'nullable|string|max:1024',
        'start_dt' => 'required|date',
        'end_dt' => 'required|date',
        'target_type' => 'required|string|max:10',
        'target_amount' => 'required|numeric',
        'pct4' => 'required|numeric',
        'account_id' => 'required'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function account()
    {
        return $this->belongsTo(\App\Models\Account::class, 'account_id', 'id');
    }
}
