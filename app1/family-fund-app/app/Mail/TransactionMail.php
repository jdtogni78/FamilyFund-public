<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransactionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;
    public $api1;
    public $to;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($transaction, $api1)
    {
        $this->transaction = $transaction;
        $this->api1 = $api1;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $arr = [
            'to' => $this->transaction->account()->get()->first()->user()->get()->first()->name,
            'report_name' => 'Transaction Confirmation',
        ];

        return $this->view('emails.transaction')
            ->with("api", $arr)
            ->with("api1", $this->api1)
            ->subject("Transaction Confirmation");
    }
}
