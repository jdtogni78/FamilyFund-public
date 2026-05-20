<?php

namespace App\Mail\CreditLine;

use App\Models\TransactionExt;
use App\Services\Detection\DetectionResult;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * UC-29 / UC-30: Sent when a REP transaction has match_status ambiguous or unmatched.
 * Contains a placeholder resolution URL — Phase 2 will mount the route.
 */
class MismatchAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public TransactionExt $tran;
    public DetectionResult $result;
    public string $resolveUrl;

    public function __construct(TransactionExt $tran, DetectionResult $result)
    {
        $this->tran       = $tran;
        $this->result     = $result;
        // Phase 2 TODO: replace with route('credit_lines.resolve', $tran->id)
        $this->resolveUrl = url("/credit-lines/resolve/{$tran->id}");
    }

    public function build(): static
    {
        return $this->subject('Action required: repayment needs review')
            ->view('emails.credit_lines.mismatch_alert');
    }
}
