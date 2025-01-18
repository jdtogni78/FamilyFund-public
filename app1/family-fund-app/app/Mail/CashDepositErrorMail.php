<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CashDepositErrorMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

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
        // send to system admin
        $arr = [
            'to' => env('MAIL_ADMIN_ADDRESS'),
            'report_name' => 'Cash Deposit Errors',
        ];

        return $this->view('emails.cash_deposit_error')
            ->with("data", $this->data)
            ->with("api", $arr)
            ->subject("Cash Deposit Errors");
    }
}
