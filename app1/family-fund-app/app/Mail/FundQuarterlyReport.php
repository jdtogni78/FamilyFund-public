<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FundQuarterlyReport extends Mailable
{
    use Queueable, SerializesModels;

    public $fund;
    public ?User $user;
    public $pdf;
    public $asOf;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($fund, $user, $asOf, $pdf)
    {
        $this->fund = $fund;
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
        $arr = [
            'to' => $name,
            'report_name' => $this->fund->name.' quarterly report',
        ];

        return $this->view('emails.reports.quarterly_report')
            ->with("api", $arr)
            ->subject("Fund Quarterly Report - ". $this->asOf)
            ->attach($this->pdf, [
                'as' => 'fund_report_'.$this->asOf.'.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
