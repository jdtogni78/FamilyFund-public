<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class TradePortfolioItem
 * @package App\Models
 * @version July 23, 2022, 12:55 pm UTC
 *
 * @property \App\Models\TradePortfolio $tradePortfolio
 * @property integer $trade_portfolio_id
 * @property string $symbol
 * @property string $type
 * @property number $target_share
 * @property number $deviation_trigger
 */
class TradePortfolioItem extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'trade_portfolio_items';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'trade_portfolio_id',
        'symbol',
        'type',
        'target_share',
        'deviation_trigger'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'trade_portfolio_id' => 'integer',
        'symbol' => 'string',
        'type' => 'string',
        'target_share' => 'decimal:2',
        'deviation_trigger' => 'decimal:5'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'trade_portfolio_id' => 'required',
        'symbol' => 'required|string|max:50',
        'type' => 'required|string|max:50',
        'target_share' => 'required|numeric',
        'deviation_trigger' => 'required|numeric',
        'updated_at' => 'nullable',
        'created_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function tradePortfolio()
    {
        return $this->belongsTo(\App\Models\TradePortfolio::class, 'trade_portfolio_id');
    }
}
