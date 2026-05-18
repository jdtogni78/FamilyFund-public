<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class AccountCreditLine
 * @package App\Models
 *
 * @property int $id
 * @property int $account_id
 * @property string $nickname
 * @property float $principal_shares
 * @property float $outstanding_shares
 * @property int $term_months
 * @property string $origination_date
 * @property string $maturity_date
 * @property string $payment_frequency
 * @property string $status
 * @property string|null $descr
 * @property float|null $imputed_interest_rate
 */
class AccountCreditLine extends Model
{
    use SoftDeletes;
    use HasFactory;

    public $table = 'account_credit_lines';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = ['deleted_at', 'origination_date', 'maturity_date'];

    public $fillable = [
        'account_id',
        'nickname',
        'principal_shares',
        'outstanding_shares',
        'term_months',
        'origination_date',
        'maturity_date',
        'payment_frequency',
        'status',
        'descr',
        'imputed_interest_rate',
        'reminder_lead_days',
        'reminder_enabled',
        'delay_notification_grace_days',
        'delay_notification_repeat_days',
        'delay_notification_max_repeats',
        'transaction_email_enabled',
        'mismatch_alert_enabled',
    ];

    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'nickname' => 'string',
        'principal_shares' => 'float',
        'outstanding_shares' => 'float',
        'term_months' => 'integer',
        'origination_date' => 'date',
        'maturity_date' => 'date',
        'payment_frequency' => 'string',
        'status' => 'string',
        'descr' => 'string',
        'imputed_interest_rate' => 'float',
        'reminder_lead_days' => 'integer',
        'reminder_enabled' => 'boolean',
        'delay_notification_grace_days' => 'integer',
        'delay_notification_repeat_days' => 'integer',
        'delay_notification_max_repeats' => 'integer',
        'transaction_email_enabled' => 'boolean',
        'mismatch_alert_enabled' => 'boolean',
    ];

    public static $rules = [
        'account_id' => 'required',
        'nickname' => 'required|string|max:255',
        'principal_shares' => 'required|numeric|min:0',
        'outstanding_shares' => 'required|numeric|min:0',
        'term_months' => 'required|integer|min:1',
        'origination_date' => 'required|date',
        'maturity_date' => 'required|date',
        'payment_frequency' => 'required|in:monthly,quarterly,annual',
        'status' => 'required|in:active,paid_off,cancelled',
    ];

    public function account()
    {
        return $this->belongsTo(\App\Models\AccountExt::class, 'account_id');
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\CreditLinePayment::class, 'account_credit_line_id');
    }

    public function adjustments()
    {
        return $this->hasMany(\App\Models\CreditLineAdjustment::class, 'account_credit_line_id');
    }

    public function transactions()
    {
        return $this->hasMany(\App\Models\TransactionExt::class, 'account_credit_line_id');
    }
}
