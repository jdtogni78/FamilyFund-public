<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates per-line notification-settings updates (UC-20).
 *
 * Only the 7 reminder / notification columns are user-editable here.
 * Principal, term, frequency and status flow through Readjust / Cancel.
 */
class UpdateAccountCreditLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (auth()->user()?->is_admin());
    }

    protected function prepareForValidation(): void
    {
        // Checkboxes that are unchecked don't get posted at all in HTML forms.
        // Normalise the three boolean fields so we always have an explicit value.
        $this->merge([
            'reminder_enabled'            => $this->boolean('reminder_enabled'),
            'transaction_email_enabled'   => $this->boolean('transaction_email_enabled'),
            'mismatch_alert_enabled'      => $this->boolean('mismatch_alert_enabled'),
        ]);
    }

    public function rules(): array
    {
        return [
            'reminder_lead_days'              => 'required|integer|min:0|max:365',
            'reminder_enabled'                => 'required|boolean',
            'delay_notification_grace_days'   => 'required|integer|min:0|max:365',
            // Wave-2 review: must be >= 1 — ScanLatePaymentsJob divides by this
            // value (`floor($daysAfterGrace / $repeatDays)`), so 0 = DivisionByZero.
            'delay_notification_repeat_days'  => 'nullable|integer|min:1|max:365',
            'delay_notification_max_repeats'  => 'required|integer|min:0|max:100',
            'transaction_email_enabled'       => 'required|boolean',
            'mismatch_alert_enabled'          => 'required|boolean',
        ];
    }
}
