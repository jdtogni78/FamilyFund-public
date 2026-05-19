<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class CreditLinePayment
 * @package App\Models
 *
 * @property int $id
 * @property int $account_credit_line_id
 * @property string $due_date
 * @property float $shares_due
 * @property string $status
 * @property int|null $paid_transaction_id
 */
class CreditLinePayment extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_LATE = 'late';
    public const STATUS_CANCELLED = 'cancelled';

    public $table = 'credit_line_payments';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = ['due_date'];

    public $fillable = [
        'account_credit_line_id',
        'due_date',
        'shares_due',
        'status',
        'paid_transaction_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'account_credit_line_id' => 'integer',
        'due_date' => 'date',
        'shares_due' => 'float',
        'status' => 'string',
        'paid_transaction_id' => 'integer',
    ];

    public static $rules = [
        'account_credit_line_id' => 'required',
        'due_date' => 'required|date',
        'shares_due' => 'required|numeric|min:0',
        'status' => 'required|in:scheduled,paid,partial,late,cancelled',
    ];

    public function creditLine()
    {
        return $this->belongsTo(\App\Models\AccountCreditLine::class, 'account_credit_line_id');
    }

    public function paidTransaction()
    {
        return $this->belongsTo(\App\Models\TransactionExt::class, 'paid_transaction_id');
    }

    /**
     * Per-transaction allocations applied to this schedule row. The unit of
     * truth for how much has been paid; `status`/`paid_transaction_id` are
     * derived from this.
     */
    public function allocations()
    {
        return $this->hasMany(\App\Models\CreditLinePaymentAllocation::class, 'credit_line_payment_id');
    }
}
