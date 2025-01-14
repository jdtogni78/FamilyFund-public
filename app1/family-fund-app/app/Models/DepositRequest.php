<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class DepositRequest
 * @package App\Models
 * @version January 14, 2025, 4:25 am UTC
 *
 * @property \App\Models\Account $account
 * @property \App\Models\CashDeposit $cashDeposit
 * @property \App\Models\Transaction $transaction
 * @property string $date
 * @property string $description
 * @property string $status
 * @property integer $account_id
 * @property integer $cash_deposit_id
 * @property integer $transaction_id
 */
class DepositRequest extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'deposit_requests';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'date',
        'description',
        'status',
        'account_id',
        'cash_deposit_id',
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
        'status' => 'string',
        'account_id' => 'integer',
        'cash_deposit_id' => 'integer',
        'transaction_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date' => 'required',
        'description' => 'required',
        'status' => 'required|in:PENDING,APPROVED,REJECTED',
        'account_id' => 'required',
        'cash_deposit_id' => 'nullable',
        'transaction_id' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function account()
    {
        return $this->belongsTo(\App\Models\Account::class, 'account_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function cashDeposit()
    {
        return $this->belongsTo(\App\Models\CashDeposit::class, 'cash_deposit_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function transaction()
    {
        return $this->belongsTo(\App\Models\Transaction::class, 'transaction_id');
    }
}
