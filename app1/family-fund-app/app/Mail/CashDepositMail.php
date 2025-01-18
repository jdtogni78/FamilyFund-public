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
        $arr = [
            'to' => $this->data['to'],
            'report_name' => 'Cash Deposit Detected',
        ];

        return $this->view('emails.cash_deposit')
            ->with("data", $this->data)
            ->with("api", $arr)
            ->subject("Cash Deposit Detected");
    }
}
