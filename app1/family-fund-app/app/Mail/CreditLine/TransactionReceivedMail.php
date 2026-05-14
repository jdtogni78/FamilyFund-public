<?php

namespace App\Mail\CreditLine;

use App\Models\TransactionExt;
use App\Services\Detection\DetectionResult;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * UC-32: Email sent when a user-submitted BOR or REP transaction is saved.
 */
class TransactionReceivedMail extends Mailable
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
            ? 'Borrow recorded on your account'
            : 'Repayment received on your account';

        return $this->subject($subject)
            ->view('emails.credit_lines.transaction_received');
    }
}
