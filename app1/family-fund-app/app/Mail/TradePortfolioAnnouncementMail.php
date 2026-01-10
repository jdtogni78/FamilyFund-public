<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TradePortfolioAnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $api;
    public $to;

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
        $api = $this->api;
        $api['to'] = env('MAIL_ADMIN_ADDRESS', 'admin@example.com');
        $api['report_name'] = 'Trade Portfolio Changes';

        return $this->view('emails.trade_portfolio_announcement')
            ->with("api", $api)
            ->subject("Trade Portfolio Changes");
    }
}
