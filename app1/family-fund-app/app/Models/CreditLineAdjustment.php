<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class CreditLineAdjustment
 * @package App\Models
 *
 * Immutable audit row for credit-line readjustments.
 *
 * @property int $id
 * @property int $account_credit_line_id
 * @property string $adjusted_at
 * @property string $effective_date
 * @property int|null $adjusted_by_user_id
 * @property float $outstanding_shares_at_adjustment
 * @property int $old_term_months
 * @property int $new_term_months
 * @property string $old_payment_frequency
 * @property string $new_payment_frequency
 * @property string $old_maturity_date
 * @property string $new_maturity_date
 * @property string $old_planned_payoff_date
 * @property string $new_planned_payoff_date
 * @property string|null $reason
 */
class CreditLineAdjustment extends Model
{
    use HasFactory;

    public $table = 'credit_line_adjustments';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = [
        'adjusted_at',
        'effective_date',
        'old_maturity_date',
        'new_maturity_date',
        'old_planned_payoff_date',
        'new_planned_payoff_date',
    ];

    public $fillable = [
        'account_credit_line_id',
        'adjusted_at',
        'effective_date',
        'adjusted_by_user_id',
        'outstanding_shares_at_adjustment',
        'old_term_months',
        'new_term_months',
        'old_payment_frequency',
        'new_payment_frequency',
        'old_maturity_date',
        'new_maturity_date',
        'old_planned_payoff_date',
        'new_planned_payoff_date',
        'reason',
    ];

    protected $casts = [
        'id' => 'integer',
        'account_credit_line_id' => 'integer',
        'adjusted_at' => 'datetime',
        'effective_date' => 'date',
        'adjusted_by_user_id' => 'integer',
        'outstanding_shares_at_adjustment' => 'float',
        'old_term_months' => 'integer',
        'new_term_months' => 'integer',
        'old_payment_frequency' => 'string',
        'new_payment_frequency' => 'string',
        'old_maturity_date' => 'date',
        'new_maturity_date' => 'date',
        'old_planned_payoff_date' => 'date',
        'new_planned_payoff_date' => 'date',
        'reason' => 'string',
    ];

    public static $rules = [
        'account_credit_line_id' => 'required',
        'adjusted_at' => 'required|date',
        'outstanding_shares_at_adjustment' => 'required|numeric|min:0',
        'old_term_months' => 'required|integer|min:1',
        'new_term_months' => 'required|integer|min:1',
    ];

    public function creditLine()
    {
        return $this->belongsTo(\App\Models\AccountCreditLine::class, 'account_credit_line_id');
    }

    public function adjustedBy()
    {
        return $this->belongsTo(\App\Models\UserExt::class, 'adjusted_by_user_id');
    }
}
