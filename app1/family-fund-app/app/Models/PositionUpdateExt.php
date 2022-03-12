<?php

namespace App\Models;

/**
 * Class PositionUpdateExt
 * @package App\Models
 * @version March 5, 2022, 8:26 pm UTC
 */
class PositionUpdateExt extends PositionUpdate
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
        'symbols.*.name' => 'required|string',
        'symbols.*.type' => 'required|string',
        'symbols.*.position' => 'required|numeric|gt:0|lt:9999999999999.9991',
        'symbols.*.price' => 'prohibited',
    ];
}
