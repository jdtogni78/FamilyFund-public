<?php

namespace App\Models;

class CashDepositExt extends CashDeposit
{
    const STATUS_PENDING = 'PEN';
    const STATUS_DEPOSITED = 'DEP';
    const STATUS_ALLOCATED = 'ALL';
    const STATUS_COMPLETED = 'COM';
    const STATUS_CANCELLED = 'CAN';
    
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
        return self::statusMap()[$this->status];
    }
}
