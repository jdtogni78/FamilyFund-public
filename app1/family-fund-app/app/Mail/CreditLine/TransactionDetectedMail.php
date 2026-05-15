<?php

namespace App\Mail\CreditLine;

use App\Models\TransactionExt;
use App\Services\Detection\DetectionResult;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * UC-33: Email sent when a system-generated BOR or REP transaction is saved
 * (e.g. from a matcher, scheduler, or late-payment sweep).
 */
class TransactionDetectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public TransactionExt $tran;
    public DetectionResult $result;

    public function __construct(TransactionExt $tran, DetectionResult $result)
    {
        $this->tran   = $tran;
        $this->result = $result;
    }

    public function build(): static
    {
        $subject = $this->tran->type === TransactionExt::TYPE_BORROW
            ? 'System: Borrow recorded on your account'
            : 'System: Repayment recorded on your account';

        return $this->subject($subject)
            ->view('emails.credit_lines.transaction_detected');
    }
}
