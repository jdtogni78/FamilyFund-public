<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Fund
 * @package App\Models
 * @version January 14, 2022, 4:54 am UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $accounts
 * @property \Illuminate\Database\Eloquent\Collection $portfolios
 * @property string $name
 * @property string $goal
 */
class Fund extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'funds';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'name',
        'goal',
        'withdrawal_yearly_expenses',
        'withdrawal_net_worth_pct',
        'withdrawal_rate',
        'expected_growth_rate',
        'independence_mode',
        'independence_target_date'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'goal' => 'string',
        'withdrawal_yearly_expenses' => 'decimal:2',
        'withdrawal_net_worth_pct' => 'decimal:2',
        'withdrawal_rate' => 'decimal:2',
        'expected_growth_rate' => 'decimal:2',
        'independence_mode' => 'string',
        'independence_target_date' => 'date'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:30',
        'goal' => 'nullable|string|max:1024',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function accounts()
    {
        return $this->hasMany(\App\Models\AccountExt::class, 'fund_id');
    }

    /**
     * Many-to-many relationship with portfolios via pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function portfolios()
    {
        return $this->belongsToMany(\App\Models\PortfolioExt::class, 'fund_portfolio', 'fund_id', 'portfolio_id')
            ->withTimestamps();
    }

    /**
     * Get the fund account (the account with null user_id)
     * This is a convenience method for tests and simple access
     *
     * @return \App\Models\AccountExt|null
     */
    public function account()
    {
        return $this->accounts()->whereNull('user_id')->first();
    }
}
