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
class ReportScheduleExt extends ReportSchedule
{
    /**
     * @var string[]
     */
    public static array $typeMap = [
        'DOM' => 'Day of Month',
        'DOW' => 'Day of Week',
        'DOY' => 'Day of Year',
        'DOQ' => 'Day of Quarter',
    ];
}
