<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Account
 * @package App\Models
 * @version January 14, 2022, 4:53 am UTC
 *
 * @property \App\Models\Fund $fund
 * @property \App\Models\User $user
 * @property \Illuminate\Database\Eloquent\Collection $accountBalances
 * @property \Illuminate\Database\Eloquent\Collection $accountMatchingRules
 * @property \Illuminate\Database\Eloquent\Collection $transactions
 * @property \Illuminate\Database\Eloquent\Collection $goals
 * @property string $code
 * @property string $nickname
 * @property string $email_cc
 * @property integer $user_id
 * @property integer $fund_id
 */
class Account extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'accounts';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'code',
        'nickname',
        'email_cc',
        'user_id',
        'fund_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'code' => 'string',
        'nickname' => 'string',
        'email_cc' => 'string',
        'user_id' => 'integer',
        'fund_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'code' => 'required|string|max:15',
        'nickname' => 'nullable|string|max:15',
        'email_cc' => 'nullable|string|max:1024',
        'user_id' => 'nullable',
        'fund_id' => 'required',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function fund()
    {
        return $this->belongsTo(\App\Models\FundExt::class, 'fund_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function user()
    {
        return $this->belongsTo(\App\Models\UserExt::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function accountBalances()
    {
        return $this->hasMany(\App\Models\AccountBalance::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function accountMatchingRules()
    {
        return $this->hasMany(\App\Models\AccountMatchingRuleExt::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function transactions()
    {
        return $this->hasMany(\App\Models\TransactionExt::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     **/
    public function goals()
    {
        return $this->belongsToMany(\App\Models\GoalExt::class, 'account_goals');
    }
}
