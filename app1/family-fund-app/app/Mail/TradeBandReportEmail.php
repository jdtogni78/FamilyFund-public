<?php

namespace App\Mail;

use App\Models\TradeBandReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TradeBandReportEmail extends Mailable
{
    use Queueable, SerializesModels;

    public TradeBandReport $tradeBandReport;
    public $pdf;
    public $asOf;

    /**
     * Create a new message instance.
     */
    public function __construct(TradeBandReport $tradeBandReport, string $asOf, $pdf)
    {
        $this->tradeBandReport = $tradeBandReport;
        $this->asOf = $asOf;
        $this->pdf = $pdf;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $fundName = $this->tradeBandReport->fund->name;
        $reportName = 'Trading Bands Report';

        $arr = [
            'to' => 'Admin',
            'report_name' => $fundName . ' ' . $reportName,
        ];

        return $this->view('emails.reports.quarterly_report')
            ->with("api", $arr)
            ->subject($reportName . " - " . $this->asOf)
            ->attach($this->pdf, [
                'as' => 'trade_bands_report_' . $this->asOf . '.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
