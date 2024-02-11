<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ReportSchedule
 * @package App\Models
 * @version February 11, 2024, 7:23 pm UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $fundReportSchedules
 * @property string $descr
 * @property string $type
 * @property string $value
 */
class ReportSchedule extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'report_schedules';

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
        'type' => 'required|in:DOM',
        'value' => 'required|string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function fundReportSchedules()
    {
        return $this->hasMany(\App\Models\FundReportSchedule::class, 'schedule_id');
    }
}
