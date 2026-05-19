<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class CreditLinePaymentAllocation
 * @package App\Models
 *
 * Records that `shares` of REP transaction `transaction_id` were applied to
 * schedule row `credit_line_payment_id`. The unit of truth for how a payment
 * is distributed across installments.
 *
 * @property int $id
 * @property int $credit_line_payment_id
 * @property int $transaction_id
 * @property float $shares
 */
class CreditLinePaymentAllocation extends Model
{
    use HasFactory;

    public $table = 'credit_line_payment_allocations';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'credit_line_payment_id',
        'transaction_id',
        'shares',
    ];

    protected $casts = [
        'id' => 'integer',
        'credit_line_payment_id' => 'integer',
        'transaction_id' => 'integer',
        'shares' => 'float',
    ];

    public static $rules = [
        'credit_line_payment_id' => 'required',
        'transaction_id' => 'required',
        'shares' => 'required|numeric|min:0',
    ];

    public function payment()
    {
        return $this->belongsTo(\App\Models\CreditLinePayment::class, 'credit_line_payment_id');
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Models\TransactionExt::class, 'transaction_id');
    }
}
