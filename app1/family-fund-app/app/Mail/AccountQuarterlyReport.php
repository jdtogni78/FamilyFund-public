<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountQuarterlyReport extends Mailable
{
    use Queueable, SerializesModels;

    public $account;
    public $pdf;
    public $asOf;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($account, $asOf, $pdf)
    {
        $this->account = $account;
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
        $arr = [
            'to' => $this->account->user()->get()->first()->name,
            'report_name' => 'Account Quarterly Report',
        ];

        return $this->view('emails.reports.quarterly_report')
            ->with("api", $arr)
            ->subject("Account Quarterly Report - ". $this->asOf)
            ->attach($this->pdf, [
                'as' => 'account_report_'.$this->asOf.'.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
