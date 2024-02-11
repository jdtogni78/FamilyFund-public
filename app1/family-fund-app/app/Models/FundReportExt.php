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
    // typeMap
    public static array $typeMap = [
        'ADM' => 'Admin',
        'ALL' => 'All',
    ];

    /**
     **/
    public function isAdmin(): bool
    {
        return 'ADM' == $this->type;
    }
}
