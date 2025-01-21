<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Goal
 * @package App\Models
 * @version January 20, 2025, 11:17 pm UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $accounts
 * @property string $name
 * @property string $description
 * @property string $start_dt
 * @property string $end_dt
 * @property string $target_type
 * @property number $target_amount
 * @property number $pct4
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
        'pct4' => 'decimal:2',
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
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function accounts()
    {
        return $this->belongsToMany(\App\Models\Account::class, 'account_goals');
    }
}
