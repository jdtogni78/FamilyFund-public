<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class PortfolioBalance
 * @package App\Models
 *
 * @property integer $id
 * @property integer $portfolio_id
 * @property float $balance
 * @property \Carbon\Carbon $start_dt
 * @property \Carbon\Carbon $end_dt
 * @property \App\Models\Portfolio $portfolio
 */
class PortfolioBalance extends Model
{
    use HasFactory;

    public $table = 'portfolio_balances';

    public $fillable = [
        'portfolio_id',
        'balance',
        'start_dt',
        'end_dt'
    ];

    protected $casts = [
        'id' => 'integer',
        'portfolio_id' => 'integer',
        'balance' => 'decimal:2',
        'start_dt' => 'date',
        'end_dt' => 'date'
    ];

    public static $rules = [
        'portfolio_id' => 'required|exists:portfolios,id',
        'balance' => 'required|numeric',
        'start_dt' => 'required|date',
        'end_dt' => 'nullable|date'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function portfolio()
    {
        return $this->belongsTo(\App\Models\Portfolio::class, 'portfolio_id');
    }
}
