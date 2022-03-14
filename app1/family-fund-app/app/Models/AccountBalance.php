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
        'shares' => 'required|numeric',
        'account_id' => 'nullable',
        'transaction_id' => 'required',
        'start_dt' => 'required',
        'end_dt' => 'required',
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
}
