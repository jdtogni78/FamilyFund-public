<?php

namespace App\Models;

class CashDepositExt extends CashDeposit
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_DEPOSITED = 'DEPOSITED';
    const STATUS_ALLOCATED = 'ALLOCATED';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CANCELLED = 'CANCELLED';
    
    public static function statusMap()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_DEPOSITED => 'Deposited',
            self::STATUS_ALLOCATED => 'Allocated',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function status_string() {
        return self::statusMap()[$this->status] ?? 'Unknown';
    }
}
