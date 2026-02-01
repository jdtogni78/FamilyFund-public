<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountMatchingRuleRemovedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $api;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($api)
    {
        $this->api = $api;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->api['to'] = $this->api['account']->user?->name ?? $this->api['account']->nickname;
        $this->api['report_name'] = 'Matching Rule Removed';

        return $this->view('emails.matching_removed')
            ->with("api", $this->api)
            ->subject("Matching Rule Removed from Account");
    }
}
