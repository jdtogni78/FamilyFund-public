<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class AccountBalance
 * @package App\Models
 * @version January 14, 2022, 4:53 am UTC
 *
 * @property \App\Models\Account $account
 * @property \App\Models\Transaction $transaction
 * @property string $type
 * @property number $shares
 * @property integer $account_id
 * @property integer $transaction_id
 * @property integer $previous_balance_id
 * @property string $start_dt
 * @property string $end_dt
 */
class AccountBalance extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'account_balances';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'type',
        'shares',
        'account_id',
        'transaction_id',
        'previous_balance_id',
        'start_dt',
        'end_dt'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'type' => 'string',
        'shares' => 'decimal:4',
        'account_id' => 'integer',
        'transaction_id' => 'integer',
        'previous_balance_id' => 'integer',
        'start_dt' => 'date',
        'end_dt' => 'date'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'type' => 'nullable|string|max:3',
        'shares' => 'required|numeric|min:0.0001',
        'account_id' => 'nullable|exists:accounts,id',
        'transaction_id' => 'required|exists:transactions,id',
        'start_dt' => 'required|date',
        'end_dt' => 'required|date',
        'updated_at' => 'nullable',
        'created_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function account()
    {
        return $this->belongsTo(\App\Models\AccountExt::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function transaction()
    {
        return $this->belongsTo(\App\Models\TransactionExt::class, 'transaction_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function previousBalance()
    {
        return $this->belongsTo(\App\Models\AccountBalance::class, 'previous_balance_id');
    }

    /**
     * Get the share value (price per share) at the time of this balance
     *
     * @return float|null
     */
    public function getShareValueAttribute()
    {
        if (!$this->transaction) {
            return null;
        }

        // For initial transactions, calculate share value from the transaction itself
        if ($this->transaction->type === 'INI' && $this->shares > 0) {
            return $this->transaction->value / $this->shares;
        }

        $account = $this->account;
        if (!$account || !$account->fund) {
            return null;
        }

        return $account->fund->shareValueAsOf($this->transaction->timestamp);
    }

    /**
     * Get the total balance (shares * share_value) at the time of this balance
     *
     * @return float|null
     */
    public function getBalanceAttribute()
    {
        $shareValue = $this->share_value;
        if ($shareValue === null) {
            return null;
        }

        return $this->shares * $shareValue;
    }
}
