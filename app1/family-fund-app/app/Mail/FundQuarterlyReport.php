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

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
                'as' => 'name.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
