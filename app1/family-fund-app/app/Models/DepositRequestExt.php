<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DepositRequestExt extends DepositRequest
{
    const STATUS_PENDING = 'PEN';
    const STATUS_APPROVED = 'APP';
    const STATUS_REJECTED = 'REJ';
    const STATUS_COMPLETED = 'COM';

    public static function statusMap()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    public function status_string() {
        return self::statusMap()[$this->status];
    }
}
