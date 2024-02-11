<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class FundReportSchedule
 * @package App\Models
 * @version February 11, 2024, 7:23 pm UTC
 *
 * @property \App\Models\FundReport $fundReport
 * @property \App\Models\ReportSchedule $schedule
 * @property integer $fund_report_id
 * @property integer $schedule_id
 * @property string $start_dt
 * @property string $end_dt
 */
class FundReportSchedule extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'fund_report_schedules';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'fund_report_id',
        'schedule_id',
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
        'fund_report_id' => 'integer',
        'schedule_id' => 'integer',
        'start_dt' => 'date',
        'end_dt' => 'date'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'fund_report_id' => 'required',
        'schedule_id' => 'required',
        'start_dt' => 'required',
        'end_dt' => 'required',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function fundReport()
    {
        return $this->belongsTo(\App\Models\FundReport::class, 'fund_report_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function schedule()
    {
        return $this->belongsTo(\App\Models\ReportSchedule::class, 'schedule_id');
    }
}
