<?php

namespace App\Mail\CreditLine;

use App\Models\AccountExt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * UC-50: Quarterly per-account loan-share status digest.
 *
 * One email per account aggregating every active line: the LoansSummaryBuilder
 * snapshot plus a trajectory-only forecast (expected-vs-actual, overdue backlog,
 * projected payoff date, variance vs plan) for each line.
 *
 * @param array  $summary  LoansSummaryBuilder::forAccount() output.
 * @param array  $lines    list of ['line' => AccountCreditLine, 'trajectory' => array]
 *                          (TrajectoryBuilder::build() output) for each active line.
 */
class StatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public AccountExt $account;
    public array $summary;
    public array $lines;
    public string $asOf;

    public function __construct(AccountExt $account, array $summary, array $lines, string $asOf)
    {
        $this->account = $account;
        $this->summary = $summary;
        $this->lines   = $lines;
        $this->asOf    = $asOf;
    }

    public function build(): static
    {
        return $this->subject("Credit line status update — as of {$this->asOf}")
            ->view('emails.credit_lines.status_update');
    }
}
