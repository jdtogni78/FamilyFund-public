<?php

namespace App\Http\Controllers\Traits;

use App\Mail\MatchingExpirationReminderEmail;
use App\Models\MatchingReminderLog;
use App\Models\MatchingRule;
use App\Models\ScheduledJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait MatchingReminderTrait
{
    use MailTrait;

    /**
     * Handler for matching_reminder scheduled jobs.
     * Finds accounts with matching rules expiring within 45 days that have remaining capacity,
     * and sends reminder emails.
     *
     * @param Carbon $shouldRunBy
     * @param ScheduledJob $schedule
     * @param Carbon $asOf
     * @param bool $skipDataCheck
     * @return MatchingReminderLog|null
     */
    protected function matchingReminderScheduleDue($shouldRunBy, ScheduledJob $schedule, Carbon $asOf, bool $skipDataCheck = false): ?MatchingReminderLog
    {
        $expirationThreshold = $asOf->copy()->addDays(45);

        Log::info("MatchingReminderTrait: Checking for rules expiring between {$asOf->toDateString()} and {$expirationThreshold->toDateString()}");

        // 1. Find ALL active rules with remaining capacity
        $allActiveRules = MatchingRule::where('date_end', '>', $asOf)
            ->with('accountMatchingRules.account.user')
            ->get();

        Log::info("MatchingReminderTrait: Found " . $allActiveRules->count() . " active rules");

        // 2. Group by account, separate expiring vs other
        $accountData = [];
        foreach ($allActiveRules as $rule) {
            $isExpiring = $rule->date_end <= $expirationThreshold;

            foreach ($rule->accountMatchingRules as $amr) {
                $used = $amr->getMatchConsideredAsOf($asOf, false);
                $possible = $rule->dollar_range_end - $rule->dollar_range_start;
                $remaining = max(0, $possible - $used);

                if ($remaining > 0) {
                    $accountId = $amr->account_id;
                    $accountData[$accountId]['account'] = $amr->account;

                    $ruleData = [
                        'rule' => $rule,
                        'remaining' => $remaining,
                        'total' => $possible,
                        'days_left' => $asOf->diffInDays($rule->date_end),
                        'is_expiring' => $isExpiring,
                    ];

                    if ($isExpiring) {
                        $accountData[$accountId]['expiring'][] = $ruleData;
                    }
                    $accountData[$accountId]['all_rules'][] = $ruleData;
                }
            }
        }

        Log::info("MatchingReminderTrait: Found " . count($accountData) . " accounts with remaining matching capacity");

        // 3. Only send to accounts with EXPIRING rules (trigger condition)
        $lastLog = null;
        $emailsSent = 0;

        foreach ($accountData as $accountId => $data) {
            if (empty($data['expiring'])) {
                continue;  // Skip if no expiring rules
            }

            $account = $data['account'];
            $user = $account->user;

            if ($user && $user->email) {
                Log::info("MatchingReminderTrait: Sending reminder to {$user->email} for account {$account->nickname}");

                // Send email with BOTH expiring and all active rules
                $error = $this->sendMail(
                    new MatchingExpirationReminderEmail($account, $data['expiring'], $data['all_rules']),
                    $user->email
                );

                if ($error === null) {
                    $emailsSent++;

                    // Create log entry
                    $lastLog = MatchingReminderLog::create([
                        'scheduled_job_id' => $schedule->id,
                        'account_id' => $accountId,
                        'sent_at' => $asOf,
                        'rule_details' => array_map(fn($r) => [
                            'rule_id' => $r['rule']->id,
                            'rule_name' => $r['rule']->name,
                            'remaining' => $r['remaining'],
                            'expires' => $r['rule']->date_end->toDateString(),
                            'is_expiring' => $r['is_expiring'],
                        ], $data['all_rules']),
                        'rules_count' => count($data['all_rules']),
                    ]);
                } else {
                    Log::error("MatchingReminderTrait: Failed to send email to {$user->email}: {$error}");
                }
            } else {
                Log::warning("MatchingReminderTrait: No email for account {$accountId}");
            }
        }

        Log::info("MatchingReminderTrait: Sent {$emailsSent} reminder emails");

        return $lastLog;
    }
}
