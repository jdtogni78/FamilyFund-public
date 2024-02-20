<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Transaction
 * @package App\Models
 * @version February 4, 2024, 7:42 pm UTC
 *
 * @property \App\Models\Account $account
 * @property \App\Models\TransactionMatching $transactionMatching
 * @property \App\Models\TransactionMatching $transactionMatching1
 * @property \App\Models\AccountBalance $accountBalance
 * @property string $type
 * @property string $status
 * @property number $value
 * @property number $shares
 * @property string|\Carbon\Carbon $timestamp
 * @property integer $account_id
 * @property string $descr
 * @property string $flags
 */
class Transaction extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'transactions';


    protected $dates = ['deleted_at'];

    public $fillable = [
        'type',
        'status',
        'value',
        'shares',
        'timestamp',
        'account_id',
        'descr',
        'flags'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'type' => 'string',
        'status' => 'string',
        'value' => 'decimal:2',
        'shares' => 'decimal:4',
        'timestamp' => 'datetime',
        'account_id' => 'integer',
        'descr' => 'string',
        'flags' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'type' => 'required|in:PUR,SAL,BOR,REP,MAT,INI',
        'status' => 'required|in:C,P',
        'value' => 'required|numeric',
        'shares' => 'nullable|numeric',
        'timestamp' => 'required|after:last year|before_or_equal:tomorrow',
        'account_id' => 'required',
        'descr' => 'nullable|string|max:255',
        'flags' => 'nullable|string|in:A,C,U',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];
    public static $create_rules = [
        'type' => 'in:PUR',
        'status' => 'in:P',
        'value' => 'required|numeric',
        'shares' => 'prohibited',
        'timestamp' => 'required|after:last year|before_or_equal:tomorrow',
        'account_id' => 'required',
        'descr' => 'nullable|string|max:255',
        'flags' => 'nullable|string|in:A,C',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function account()
    {
        return $this->belongsTo(\App\Models\AccountExt::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function transactionMatching()
    {
        return $this->hasOne(\App\Models\TransactionMatching::class, 'transaction_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function referenceTransactionMatching()
    {
        return $this->hasOne(\App\Models\TransactionMatching::class, 'reference_transaction_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function accountBalance()
    {
        return $this->hasOne(\App\Models\AccountBalance::class, 'transaction_id');
    }
}
