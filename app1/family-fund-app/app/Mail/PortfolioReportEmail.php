<?php

namespace App\Mail;

use App\Models\User;
use App\Models\PortfolioReportExt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PortfolioReportEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $portfolioReport;
    public ?User $user;
    public $pdf;
    public $dateRange;

    /**
     * Create a new message instance.
     */
    public function __construct($portfolioReport, $user, $dateRange, $pdf)
    {
        $this->portfolioReport = $portfolioReport;
        $this->user = $user;
        $this->dateRange = $dateRange;
        $this->pdf = $pdf;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $name = 'Admin';
        if ($this->user != null) {
            $name = $this->user->name;
        }

        $portfolio = $this->portfolioReport->portfolio;
        $fundName = $portfolio->fund->name ?? 'Portfolio #' . $portfolio->id;
        $reportName = PortfolioReportExt::$emailSubject;

        $arr = [
            'to' => $name,
            'report_name' => $fundName . ' ' . $reportName,
        ];

        return $this->view('emails.reports.quarterly_report')
            ->with("api", $arr)
            ->subject($reportName . " - " . $this->dateRange)
            ->attach($this->pdf, [
                'as' => 'portfolio_rebalance_' . $this->portfolioReport->end_date->format('Y-m-d') . '.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
