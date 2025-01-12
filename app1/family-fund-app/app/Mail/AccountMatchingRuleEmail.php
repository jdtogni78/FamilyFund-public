<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountMatchingRuleEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $accountMatchingRule;
    public $api;
    public $to;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($accountMatchingRule, $api)
    {
        $this->accountMatchingRule = $accountMatchingRule;
        $this->api = $api;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->api['to'] = $this->accountMatchingRule->account()->get()->first()->user()->get()->first()->name;
        $this->api['report_name'] = 'Account Matching Rule Confirmation';

        return $this->view('emails.matching')
            ->with("api", $this->api)
            ->subject("Matching Rule Added to Account");
    }
}
