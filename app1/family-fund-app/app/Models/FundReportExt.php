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

    // typeMap
    public static array $typeMap = [
        self::TYPE_ADMIN => 'Admin',
        self::TYPE_ALL => 'All',
    ];

    // create email subjects for each type
    public static array $emailSubjects = [
        self::TYPE_ADMIN => 'Fund Admin Report',
        self::TYPE_ALL => 'Fund Report',
    ];

    /**
     * Returns true if this report should go to fund admin (accounts with no users)
     **/
    public function isAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN;
    }

    public function scheduledJobs()
    {
        return ScheduledJobExt::scheduledJobs(ScheduledJobExt::ENTITY_FUND_REPORT, $this->id);
    }
}
