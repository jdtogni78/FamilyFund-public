<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ScheduledJob
 * @package App\Models
 * @version March 2, 2024, 5:09 am UTC
 *
 * @property \App\Models\Schedule $schedule
 * @property integer $schedule_id
 * @property string $entity_descr
 * @property integer $entity_id
 * @property string $start_dt
 * @property string $end_dt
 */
class ScheduledJob extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'scheduled_jobs';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'schedule_id',
        'entity_descr',
        'entity_id',
        'start_dt',
        'end_dt'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'schedule_id' => 'integer',
        'entity_descr' => 'string',
        'entity_id' => 'integer',
        'start_dt' => 'date',
        'end_dt' => 'date'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'schedule_id' => 'required',
        'entity_descr' => 'required|string|max:255',
        'entity_id' => 'required',
        'start_dt' => 'required',
        'end_dt' => 'required',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function schedule()
    {
        return $this->belongsTo(\App\Models\ScheduleExt::class, 'schedule_id');
    }
}
