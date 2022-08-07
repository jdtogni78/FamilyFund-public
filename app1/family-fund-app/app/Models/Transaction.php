<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Transaction
 * @package App\Models
 * @version August 1, 2022, 4:46 am UTC
 *
 * @property \App\Models\Account $account
 * @property \Illuminate\Database\Eloquent\Collection $accountBalances
 * @property \Illuminate\Database\Eloquent\Collection $transactionMatchings
 * @property \Illuminate\Database\Eloquent\Collection $transactionMatching1s
 * @property string $type
 * @property string $status
 * @property number $value
 * @property number $shares
 * @property string|\Carbon\Carbon $timestamp
 * @property integer $account_id
 * @property string $descr
 */
class Transaction extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'transactions';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];

    public static array $typeMap = [
        'PUR' => 'Purchase',
        'INI' => 'Initial Value',
    ];
    public static array $statusMap = [
        'P' => 'Pending',
        'C' => 'Cleared',
        ];


    public $fillable = [
        'type',
        'status',
        'value',
        'shares',
        'timestamp',
        'account_id',
        'descr'
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
        'descr' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'type' => 'in:PUR,BOR,SAL,REP,MAT,INI',
        'status' => 'in:C,P',
        'value' => 'required|numeric',
        'shares' => 'nullable|numeric',
        'timestamp' => 'required|after:last year|before_or_equal:tomorrow',
        'account_id' => 'required',
        'descr' => 'nullable|string|max:255',
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
