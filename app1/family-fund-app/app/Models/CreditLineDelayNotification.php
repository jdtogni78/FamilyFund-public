<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class CreditLineDelayNotification
 * @package App\Models
 *
 * Ledger row recording a single delay-notification email that
 * ScanLatePaymentsJob sent for a given credit_line_payments row. Persisted
 * (instead of cache-keyed) so the job stays idempotent across queue workers
 * and cache flushes. Issue #7.
 *
 * @property int $id
 * @property int $credit_line_payment_id
 * @property int $notification_number
 * @property \Carbon\Carbon $sent_at
 * @property string|null $recipient_email
 */
class CreditLineDelayNotification extends Model
{
    use HasFactory;

    public $table = 'credit_line_delay_notifications';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'credit_line_payment_id',
        'notification_number',
        'sent_at',
        'recipient_email',
    ];

    protected $casts = [
        'id' => 'integer',
        'credit_line_payment_id' => 'integer',
        'notification_number' => 'integer',
        'sent_at' => 'datetime',
        'recipient_email' => 'string',
    ];

    public function payment()
    {
        return $this->belongsTo(\App\Models\CreditLinePayment::class, 'credit_line_payment_id');
    }
}
