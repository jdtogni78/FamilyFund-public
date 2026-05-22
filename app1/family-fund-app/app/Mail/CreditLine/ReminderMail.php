<?php

namespace App\Mail\CreditLine;

use App\Models\AccountCreditLine;
use App\Models\CreditLinePayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * UC-18: Sent N days before a scheduled payment is due.
 */
class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public AccountCreditLine $line;
    public CreditLinePayment $payment;
    public int $leadDays;

    public function __construct(AccountCreditLine $line, CreditLinePayment $payment, int $leadDays)
    {
        $this->line     = $line;
        $this->payment  = $payment;
        $this->leadDays = $leadDays;
    }

    public function build(): static
    {
        return $this->subject("Reminder: loan share payment due in {$this->leadDays} day(s)")
            ->view('emails.credit_lines.reminder');
    }
}
