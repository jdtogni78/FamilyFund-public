<?php

namespace App\Mail;

use App\Models\DepositRequestExt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DepositAllocationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $depositRequest;
    public $cashDeposit;

    /**
     * Create a new message instance.
     *
     * @param DepositRequestExt $depositRequest
     */
    public function __construct(DepositRequestExt $depositRequest)
    {
        $this->depositRequest = $depositRequest;
        $this->cashDeposit = $depositRequest->cashDeposit;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $account = $this->depositRequest->account;

        return $this->view('emails.deposit_allocation')
            ->with([
                'depositRequest' => $this->depositRequest,
                'cashDeposit' => $this->cashDeposit,
                'account' => $account,
                'to' => $account->nickname,
            ])
            ->subject("Deposit Allocation Confirmed - \${$this->depositRequest->amount}");
    }
}
