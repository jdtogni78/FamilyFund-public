<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CashDepositMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $to;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->data['to'] = $this->data['cash_deposit']->account->email_cc;
        $this->data['report_name'] = 'Cash Deposit Detected';

        return $this->view('emails.cash_deposit')
            ->with("data", $this->data)
            ->subject("Cash Deposit Detected");
    }
}
