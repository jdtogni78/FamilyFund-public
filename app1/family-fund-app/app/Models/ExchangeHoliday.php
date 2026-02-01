<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeHoliday extends Model
{
    use HasFactory;
    protected $fillable = [
        'exchange_code',
        'holiday_date',
        'holiday_name',
        'early_close_time',
        'source',
        'is_active',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'early_close_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForExchange($query, $exchangeCode)
    {
        return $query->where('exchange_code', $exchangeCode);
    }

    public function scopeForYear($query, $year)
    {
        return $query->whereYear('holiday_date', $year);
    }
}
