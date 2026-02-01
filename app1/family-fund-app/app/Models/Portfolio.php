<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Portfolio
 * @package App\Models
 * @version January 14, 2022, 4:54 am UTC
 *
 * @property \App\Models\Fund $fund
 * @property \Illuminate\Database\Eloquent\Collection $portfolioAssets
 * @property integer $fund_id
 * @property string $source
 */
class Portfolio extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'portfolios';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'fund_id',
        'source',
        'display_name',
        'type',
        'category'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'fund_id' => 'integer',
        'source' => 'string',
        'display_name' => 'string',
        'type' => 'string',
        'category' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'source' => 'required|string|max:30',
        'display_name' => 'nullable|string|max:100',
        'type' => 'nullable|string|max:20',
        'category' => 'nullable|string|max:20',
        // Accept either fund_id (legacy) or fund_ids (new multi-fund)
        'fund_id' => 'required_without:fund_ids|exists:funds,id',
        'fund_ids' => 'required_without:fund_id|array|min:1',
        'fund_ids.*' => 'exists:funds,id',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * Many-to-many relationship with funds.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function funds()
    {
        return $this->belongsToMany(\App\Models\FundExt::class, 'fund_portfolio', 'portfolio_id', 'fund_id')
            ->withTimestamps();
    }

    /**
     * Legacy relationship for backward compatibility.
     * Returns the first fund from the pivot table, or uses fund_id if set.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function fund()
    {
        return $this->belongsTo(\App\Models\FundExt::class, 'fund_id');
    }

    /**
     * Get the primary fund (first fund from pivot table or legacy fund_id).
     * Used for backward compatibility where $portfolio->fund was used.
     *
     * @return \App\Models\FundExt|null
     */
    public function getPrimaryFund()
    {
        // Try pivot table first (new way)
        $pivotFund = $this->funds()->first();
        if ($pivotFund) {
            return $pivotFund;
        }
        // Fall back to legacy fund_id
        if ($this->fund_id) {
            return \App\Models\FundExt::find($this->fund_id);
        }
        return null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function portfolioAssets()
    {
        return $this->hasMany(\App\Models\PortfolioAsset::class, 'portfolio_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function tradePortfolios()
    {
        return $this->hasMany(\App\Models\TradePortfolioExt::class, 'portfolio_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function portfolioBalances()
    {
        return $this->hasMany(\App\Models\PortfolioBalance::class, 'portfolio_id');
    }
}
