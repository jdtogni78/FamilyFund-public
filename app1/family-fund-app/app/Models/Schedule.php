<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Schedule
 * @package App\Models
 * @version March 2, 2024, 5:09 am UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $scheduledJobs
 * @property string $descr
 * @property string $type
 * @property string $value
 */
class Schedule extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'schedules';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'descr',
        'type',
        'value'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'descr' => 'string',
        'type' => 'string',
        'value' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'descr' => 'nullable|string|max:255',
        'type' => 'required|in:DOM,DOW,DOQ,DOY',
        'value' => 'required|string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function scheduledJobs()
    {
        return $this->hasMany(\App\Models\ScheduledJobExt::class, 'schedule_id');
    }
}
