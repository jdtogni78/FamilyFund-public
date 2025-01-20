<?php

namespace App\Models;

class GoalExt extends Goal
{
    const TARGET_TYPE_TOTAL = 'TOTAL';
    const TARGET_TYPE_4PCT = '4PCT';

    public static function targetTypeMap()
    {
        return [
            self::TARGET_TYPE_TOTAL => 'Total',
            self::TARGET_TYPE_4PCT => '4%',
        ];
    }
}
