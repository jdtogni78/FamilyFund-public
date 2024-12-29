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
     **/
    public function isAdmin(): bool
    {
        return 'ADM' == $this->type;
    }

    public function scheduledJobs()
    {
        return ScheduledJobExt::scheduledJobs(ScheduledJobExt::ENTITY_FUND_REPORT, $this->id);
    }
}
