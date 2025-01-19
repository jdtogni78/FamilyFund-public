<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransactionEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction_data;
    public $to;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($transaction_data)
    {
        $this->transaction_data = $transaction_data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $arr = [
            'to' => $this->transaction_data['transaction']->account->email_cc,
            'report_name' => 'Transaction Confirmation',
        ];

        return $this->view('emails.transaction')
            ->with("api", $arr)
            ->with("api1", $this->transaction_data)
            ->subject("Transaction Confirmation");
    }
}
