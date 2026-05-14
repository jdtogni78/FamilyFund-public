<?php

namespace App\Mail\CreditLine;

use App\Models\AccountCreditLine;
use App\Models\CreditLinePayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * UC-19: Sent after a payment is missed, repeated per settings until paid or cap reached.
 */
class DelayNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public AccountCreditLine $line;
    public CreditLinePayment $payment;
    public int $notificationCount;   // 1-based count of how many have been sent (including this one)

    public function __construct(AccountCreditLine $line, CreditLinePayment $payment, int $notificationCount)
    {
        $this->line              = $line;
        $this->payment           = $payment;
        $this->notificationCount = $notificationCount;
    }

    public function build(): static
    {
        return $this->subject('Notice: credit line payment is overdue')
            ->view('emails.credit_lines.delay_notification');
    }
}
