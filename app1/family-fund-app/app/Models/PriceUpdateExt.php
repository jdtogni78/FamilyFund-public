<?php

namespace App\Models;

/**
 * Class PriceUpdateExt
 * @package App\Models
 * @version March 5, 2022, 8:26 pm UTC
 */
class PriceUpdateExt extends PriceUpdate
{
    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'source' => 'required|string|max:30|exists:portfolios,source',
        'timestamp' => 'required',
        'symbols' => 'required|array|min:1',
        'symbols.*.name' => 'required|string|not_in:CASH',
        'symbols.*.type' => 'required|string|not_in:CSH',
        'symbols.*.price' => 'required|numeric|gt:0|lt:99999999999.99', // 13.2
        'symbols.*.position' => 'prohibited',
    ];
}
