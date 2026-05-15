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

    /**
     * Convenience admin check used by credit-line authorization (Phase 2).
     *
     * Aliases the existing `isSystemAdmin()` method on the base User model so
     * controller / form-request code can write `auth()->user()?->is_admin()`
     * without needing to know about the Spatie role machinery.
     */
    public function is_admin(): bool
    {
        return $this->isSystemAdmin();
    }
}
