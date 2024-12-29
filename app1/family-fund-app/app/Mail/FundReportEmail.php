<?php

namespace App\Mail;

use App\Models\User;
use App\Models\FundReportExt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FundReportEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $fundReport;
    public ?User $user;
    public $pdf;
    public $asOf;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($fundReport, $user, $asOf, $pdf)
    {
        $this->fundReport = $fundReport;
        $this->user = $user;
        $this->asOf = $asOf;
        $this->pdf = $pdf;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $name = 'Admin';
        if ($this->user != null) {
            $name = $this->user->name;
        }
        $reportName = FundReportExt::$emailSubjects[$this->fundReport->type];
        $arr = [
            'to' => $name,
            'report_name' => $this->fundReport->fund->name . ' ' . $reportName,
        ];

        return $this->view('emails.reports.quarterly_report')
            ->with("api", $arr)
            ->subject($reportName . " - " . $this->asOf)
            ->attach($this->pdf, [
                'as' => 'fund_report_' . $this->asOf . '.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
