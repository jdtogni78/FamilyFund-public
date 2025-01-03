<?php

namespace App\Models;

use App\Models\User;

/**
 * Class UserExt
 * @package App\Models
 */
class UserExt extends User
{
    public $table = 'users';

    public static function userMap()
    {
        $userMap = self::all()->pluck('name', 'id')->toArray();
        $userMap = [null => 'Select User'] + $userMap;
        return $userMap;
    }

}
