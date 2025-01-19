<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Transaction
 * @package App\Models
 * @version March 2, 2024, 10:21 am UTC
 *
 * @property \App\Models\AccountExt $account
 * @property \App\Models\ScheduledJobExt $scheduledJob
 * @property \Illuminate\Database\Eloquent\Collection $accountBalances
 * @property \Illuminate\Database\Eloquent\Collection $transactionMatchings
 * @property \Illuminate\Database\Eloquent\Collection $referenceTransactionMatching
 * @property string $type
 * @property string $status
 * @property number $value
 * @property number $shares
 * @property string|\Carbon\Carbon $timestamp
 * @property integer $account_id
 * @property string $descr
 * @property string $flags
 * @property integer $scheduled_job_id
 */
class Transaction extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'transactions';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'type',
        'status',
        'value',
        'shares',
        'timestamp',
        'account_id',
        'descr',
        'flags',
        'scheduled_job_id'
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
        'flags' => 'string',
        'scheduled_job_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'type' => 'required|in:PUR,SAL,BOR,REP,MAT,INI',
        'status' => 'required|in:C,P,S',
        'value' => 'required|numeric',
        'shares' => 'nullable|numeric',
        'timestamp' => 'required|after:last year|before_or_equal:tomorrow',
        'account_id' => 'required',
        'descr' => 'nullable|string|max:255',
        'flags' => 'nullable|string|in:A,C,U',
        'scheduled_job_id' => 'nullable',
        'updated_at' => 'nullable',
        'created_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    public static $create_rules = [
        'type' => 'required|in:PUR,INI,SAL',
        'status' => 'required|in:P,S',
        'value' => 'required|numeric',
        'shares' => 'nullable|numeric',
        'timestamp' => 'nullable|after:last year|before_or_equal:tomorrow',
        'account_id' => 'required',
        'descr' => 'nullable|string|max:255',
        'flags' => 'nullable|string|in:A,C,U',
        'scheduled_job_id' => 'nullable',
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
    public function scheduledJob()
    {
        return $this->belongsTo(\App\Models\ScheduledJobExt::class, 'scheduled_job_id');
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function cashDeposit()
    {
        return $this->hasOne(\App\Models\CashDepositExt::class, 'transaction_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function depositRequest()
    {
        return $this->hasOne(\App\Models\DepositRequestExt::class, 'transaction_id');
    }
}
