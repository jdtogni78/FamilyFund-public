<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class CashDeposit
 * @package App\Models
 * @version January 14, 2025, 5:04 am UTC
 *
 * @property \App\Models\Transaction $transaction
 * @property \App\Models\Account $account
 * @property string $date
 * @property string $description
 * @property number $value
 * @property string $status
 * @property integer $account_id
 * @property integer $transaction_id
 */
class CashDeposit extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'cash_deposits';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'date',
        'description',
        'amount',
        'status',
        'account_id',
        'transaction_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'date' => 'date',
        'description' => 'string',
        'amount' => 'decimal:2',
        'status' => 'string:3',
        'account_id' => 'integer',
        'transaction_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date' => 'nullable',
        'description' => 'nullable',
        'amount' => 'required|numeric|min:0',
        'status' => 'required|in:PEN,DEP,ALL,COM,CAN',
        'account_id' => 'required',
        'transaction_id' => 'nullable'
    ];

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
    public function account()
    {
        return $this->belongsTo(\App\Models\AccountExt::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function depositRequests()
    {
        return $this->hasMany(\App\Models\DepositRequestExt::class, 'cash_deposit_id');
    }
}
