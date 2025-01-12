<?php

namespace App\Models;


class TradePortfolioItemExt extends TradePortfolioItem
{
    const TYPE_STK = 'STK';
    const TYPE_FUND = 'FUND';
    const TYPE_CRYPTO = 'CRYPTO';
    const TYPE_OTHER = 'OTHER';

    public static function typeMap() {
        return [
            self::TYPE_STK => 'Stock',
            self::TYPE_FUND => 'Fund',
            self::TYPE_CRYPTO => 'Crypto',
            self::TYPE_OTHER => 'Other',
        ];
    }
}
