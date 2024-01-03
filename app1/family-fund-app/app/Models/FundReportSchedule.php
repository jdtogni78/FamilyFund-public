<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class FundReportSchedule
 * @package App\Models
 * @version January 17, 2023, 6:26 am UTC
 *
 * @property \App\Models\FundReport $templateFundReport
 * @property integer $template_fund_report_id
 * @property integer $day_of_month
 * @property integer $frequency
 */
class FundReportSchedule extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'fund_report_schedules';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'template_fund_report_id',
        'day_of_month',
        'frequency'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'template_fund_report_id' => 'integer',
        'day_of_month' => 'integer',
        'frequency' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'template_fund_report_id' => 'required',
        'day_of_month' => 'required|max:31',
        'frequency' => 'required|in:1,3,12',
        'updated_at' => 'nullable|nullable',
        'created_at' => 'required',
        'deleted_at' => 'nullable|nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function templateFundReport()
    {
        return $this->belongsTo(\App\Models\FundReport::class, 'template_fund_report_id');
    }
}
