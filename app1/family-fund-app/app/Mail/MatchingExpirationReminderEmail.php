<?php

namespace App\Mail;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MatchingExpirationReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $account;
    public $expiringRules;
    public $allRules;
    public $api;

    /**
     * Create a new message instance.
     *
     * @param Account $account
     * @param array $expiringRules Rules expiring within 45 days
     * @param array $allRules All active rules with remaining capacity
     */
    public function __construct(Account $account, array $expiringRules, array $allRules)
    {
        $this->account = $account;
        $this->expiringRules = $expiringRules;
        $this->allRules = $allRules;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $user = $this->account->user;
        $this->api = [
            'to' => $user ? $user->name : $this->account->nickname,
            'account' => $this->account,
            'expiringRules' => $this->expiringRules,
            'allRules' => $this->allRules,
            'report_name' => 'Matching Expiration Reminder',
        ];

        $expiringCount = count($this->expiringRules);
        $subject = $expiringCount === 1
            ? 'Matching Opportunity Expiring Soon'
            : "$expiringCount Matching Opportunities Expiring Soon";

        return $this->view('emails.matching_expiration_reminder')
            ->with('api', $this->api)
            ->subject($subject);
    }
}
