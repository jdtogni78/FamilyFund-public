<?php

namespace App\Mail;

use App\Models\ScheduledJob;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ScheduledJobFailureMail extends Mailable
{
    use Queueable, SerializesModels;

    public $job;
    public $asOf;
    public $reason;
    public $exception;
    public $entityType;
    public $entityName;
    public $shouldRunByDate;
    public $daysOverdue;
    public $context;
    public $recommendedActions;

    /**
     * Create a new message instance.
     */
    public function __construct(
        ScheduledJob $job,
        Carbon $asOf,
        string $reason,
        ?\Exception $exception,
        string $entityType,
        string $entityName,
        Carbon $shouldRunByDate,
        int $daysOverdue,
        array $context,
        string $recommendedActions
    ) {
        $this->job = $job;
        $this->asOf = $asOf;
        $this->reason = $reason;
        $this->exception = $exception;
        $this->entityType = $entityType;
        $this->entityName = $entityName;
        $this->shouldRunByDate = $shouldRunByDate;
        $this->daysOverdue = $daysOverdue;
        $this->context = $context;
        $this->recommendedActions = $recommendedActions;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $severity = $this->daysOverdue > 4 ? 'CRITICAL' : 'WARNING';
        $subject = "[FamilyFund][{$severity}] Scheduled Job Failed: {$this->entityType} - {$this->entityName}";

        return $this->subject($subject)
            ->text('emails.scheduled_job_failure');
    }
}
