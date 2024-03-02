<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class FundReport
 * @package App\Models
 * @version February 11, 2024, 11:55 pm UTC
 *
 * @property \App\Models\Fund $fund
 * @property \App\Models\ScheduledJobExt $scheduledJob
// * @property \Illuminate\Database\Eloquent\Collection $fundReportSchedules
 * @property integer $fund_id
 * @property string $type
 * @property string $as_of
 * @property integer $scheduled_job_id
 */
class FundReport extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'fund_reports';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'fund_id',
        'type',
        'as_of',
        'scheduled_job_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'fund_id' => 'integer',
        'type' => 'string',
        'as_of' => 'date',
        'scheduled_job_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'fund_id' => 'required',
        'type' => 'required|in:ALL,ADM',
        'as_of' => 'required',
        'scheduled_job_id' => 'nullable',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function fund()
    {
        return $this->belongsTo(\App\Models\FundExt::class, 'fund_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function scheduledJob()
    {
        return $this->belongsTo(\App\Models\ScheduledJobExt::class, 'scheduled_job_id');
    }
}
