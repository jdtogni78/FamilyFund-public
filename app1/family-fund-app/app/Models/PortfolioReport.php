<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class PortfolioReport
 * @package App\Models
 *
 * @property \App\Models\Portfolio $portfolio
 * @property \App\Models\ScheduledJobExt $scheduledJob
 * @property integer $portfolio_id
 * @property string $start_date
 * @property string $end_date
 * @property integer $scheduled_job_id
 */
class PortfolioReport extends Model
{
    use SoftDeletes;
    use HasFactory;

    public $table = 'portfolio_reports';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = ['deleted_at'];

    public $fillable = [
        'portfolio_id',
        'start_date',
        'end_date',
        'report_type',
        'scheduled_job_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'portfolio_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'scheduled_job_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'portfolio_id' => 'required',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'scheduled_job_id' => 'nullable',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function portfolio()
    {
        return $this->belongsTo(\App\Models\Portfolio::class, 'portfolio_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function scheduledJob()
    {
        return $this->belongsTo(\App\Models\ScheduledJobExt::class, 'scheduled_job_id');
    }
}
