<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class TradePortfolio
 * @package App\Models
 * @version July 23, 2022, 12:55 pm UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $tradePortfolioItems
 * @property string $account_name
 * @property number $cash_target
 * @property number $cash_reserve_target
 * @property number $max_single_order
 * @property number $minimum_order
 * @property integer $rebalance_period
 */
class TradePortfolio extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'trade_portfolios';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'account_name',
        'cash_target',
        'cash_reserve_target',
        'max_single_order',
        'minimum_order',
        'rebalance_period'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'account_name' => 'string',
        'cash_target' => 'decimal:2',
        'cash_reserve_target' => 'decimal:2',
        'max_single_order' => 'decimal:2',
        'minimum_order' => 'decimal:2',
        'rebalance_period' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'account_name' => 'nullable|string|max:50',
        'cash_target' => 'required|numeric',
        'cash_reserve_target' => 'required|numeric',
        'max_single_order' => 'required|numeric',
        'minimum_order' => 'required|numeric',
        'rebalance_period' => 'required|integer',
        'updated_at' => 'nullable',
        'created_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function tradePortfolioItems()
    {
        return $this->hasMany(\App\Models\TradePortfolioItem::class, 'trade_portfolio_id');
    }
}
