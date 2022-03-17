<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FundQuarterlyReport extends Mailable
{
    use Queueable, SerializesModels;

    public $fund;
    public $pdf;
    public $asOf;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($fund, $asOf, $pdf)
    {
        $this->fund = $fund;
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
        $arr = $this->fund->toArray();

        return $this->view('emails.reports.fund_quarterly')
            ->with("api", $arr)
            ->attach($this->pdf, [
                'as' => 'fund_report_'.$this->asOf.'.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
