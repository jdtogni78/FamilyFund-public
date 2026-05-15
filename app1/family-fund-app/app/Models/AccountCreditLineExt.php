<?php

namespace App\Models;

/**
 * Class AccountCreditLineExt
 * @package App\Models
 *
 * Extension hook for credit-line business logic. Phase 0 only declares
 * the constants other phases code against; service logic lives in
 * dedicated service classes added in wave 1.
 */
class AccountCreditLineExt extends AccountCreditLine
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAID_OFF = 'paid_off';
    public const STATUS_CANCELLED = 'cancelled';

    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_ANNUAL = 'annual';

    public static array $statusMap = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_PAID_OFF => 'Paid off',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public static array $frequencyMap = [
        self::FREQUENCY_MONTHLY => 'Monthly',
        self::FREQUENCY_QUARTERLY => 'Quarterly',
        self::FREQUENCY_ANNUAL => 'Annual',
    ];
}
