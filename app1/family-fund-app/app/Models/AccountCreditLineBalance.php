<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Temporal history of an AccountCreditLine's outstanding_shares.
 *
 * One row per [start_dt, end_dt) interval. The active row has end_dt='9999-12-31'.
 * Maintained by CreditLineBalanceTracker; read by FundReceivableCalculator.
 *
 * @property int $id
 * @property int $account_credit_line_id
 * @property float $outstanding_shares
 * @property string $start_dt
 * @property string $end_dt
 * @property int|null $transaction_id
 */
class AccountCreditLineBalance extends Model
{
    public $table = 'account_credit_line_balances';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'account_credit_line_id',
        'outstanding_shares',
        'start_dt',
        'end_dt',
        'transaction_id',
    ];

    protected $casts = [
        'id'                     => 'integer',
        'account_credit_line_id' => 'integer',
        'outstanding_shares'     => 'float',
        'start_dt'               => 'date',
        'end_dt'                 => 'date',
        'transaction_id'         => 'integer',
    ];

    public function creditLine()
    {
        return $this->belongsTo(AccountCreditLine::class, 'account_credit_line_id');
    }

    public function transaction()
    {
        return $this->belongsTo(TransactionExt::class, 'transaction_id');
    }
}
