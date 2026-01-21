<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class FundReportExt
 * @package App\Models
 * @version March 28, 2022, 2:48 am UTC
 *
 */
class FundReportExt extends FundReport
{
    const TYPE_ADMIN = 'ADM';
    const TYPE_ALL = 'ALL';
    const TYPE_TRADING_BANDS = 'TRADING_BANDS';

    // typeMap
    public static array $typeMap = [
        self::TYPE_ADMIN => 'Admin',
        self::TYPE_ALL => 'All',
        self::TYPE_TRADING_BANDS => 'Trading Bands',
    ];

    // create email subjects for each type
    public static array $emailSubjects = [
        self::TYPE_ADMIN => 'Fund Admin Report',
        self::TYPE_ALL => 'Fund Report',
        self::TYPE_TRADING_BANDS => 'Trading Bands Report',
    ];

    /**
     * Returns true if this report should go to fund admin (accounts with no users)
     **/
    public function isAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN || $this->type === self::TYPE_TRADING_BANDS;
    }

    public function scheduledJobs()
    {
        return ScheduledJobExt::scheduledJobs(ScheduledJobExt::ENTITY_FUND_REPORT, $this->id);
    }

    /**
     * Check if this report is a template (as_of = 9999-12-31)
     */
    public function isTemplate(): bool
    {
        return $this->as_of && $this->as_of->format('Y-m-d') === '9999-12-31';
    }

    /**
     * Get all fund report templates (as_of = 9999-12-31)
     */
    public static function templates()
    {
        return static::with('fund')
            ->where('as_of', '9999-12-31')
            ->orderBy('fund_id')
            ->get();
    }

    /**
     * Get fund report templates as options for select dropdowns
     * Returns [id => "Fund Name - Type"]
     */
    public static function templateOptions()
    {
        return static::templates()->mapWithKeys(function ($report) {
            $typeName = self::$typeMap[$report->type] ?? $report->type;
            return [$report->id => $report->fund->name . ' - ' . $typeName];
        });
    }
}
