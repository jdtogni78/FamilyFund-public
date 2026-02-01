<?php

namespace Tests\Feature;

use App\Http\Controllers\Traits\MatchingReminderTrait;
use App\Http\Controllers\Traits\MailTrait;
use App\Mail\MatchingExpirationReminderEmail;
use App\Models\MatchingReminderLog;
use App\Models\MatchingRule;
use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use App\Models\ScheduleExt;
use App\Models\TransactionExt;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

class MatchingReminderTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;

    public function setUp(): void
    {
        parent::setUp();
        $this->df = new DataFactory();
        $this->df->createFund();
        Mail::fake();
    }

    // Helper to create a matching reminder scheduled job
    protected function createMatchingReminderJob(): ScheduledJob
    {
        $schedule = $this->df->createSchedule(ScheduleExt::TYPE_DAY_OF_MONTH, 1);
        return $this->df->createScheduledJob(
            $schedule,
            ScheduledJobExt::ENTITY_MATCHING_REMINDER,
            0,
            Carbon::now()->subDay()->format('Y-m-d')
        );
    }

    public function test_no_emails_for_non_expiring_rules_only()
    {
        Mail::fake();

        $this->df->createUser();
        // Create a rule that expires far in the future (more than 45 days)
        $this->df->createMatching(500, 100, '2024-01-01', Carbon::now()->addDays(100)->format('Y-m-d'));

        $job = $this->createMatchingReminderJob();
        $handler = new TestMatchingReminderHandler();
        $result = $handler->callHandler(Carbon::now(), $job, Carbon::now());

        // For this specific user/account, no email should be sent since their rule is not expiring within 45 days
        // Note: Other accounts in the test DB may still receive emails
        $sentToOurAccount = Mail::sent(MatchingExpirationReminderEmail::class, function ($mail) {
            return $mail->account->id === $this->df->userAccount->id;
        })->count();

        $this->assertEquals(0, $sentToOurAccount, 'No email should be sent to account with only non-expiring rules');
    }

    public function test_email_sent_for_expiring_rule_with_remaining_capacity()
    {
        $this->df->createUser();
        // Create a rule expiring in 30 days with full capacity remaining
        $expirationDate = Carbon::now()->addDays(30)->format('Y-m-d');
        $this->df->createMatching(500, 100, '2024-01-01', $expirationDate);

        $job = $this->createMatchingReminderJob();
        $handler = new TestMatchingReminderHandler();
        $result = $handler->callHandler(Carbon::now(), $job, Carbon::now());

        // Email should be sent
        Mail::assertSent(MatchingExpirationReminderEmail::class, function ($mail) {
            return $mail->account->id === $this->df->userAccount->id;
        });

        // Log entry should be created
        $this->assertNotNull($result);
        $this->assertInstanceOf(MatchingReminderLog::class, $result);
        $this->assertEquals($this->df->userAccount->id, $result->account_id);
    }

    public function test_remaining_capacity_calculation()
    {
        Mail::fake();

        $this->df->createUser();
        // Create a rule expiring in 30 days
        $expirationDate = Carbon::now()->addDays(30)->format('Y-m-d');
        $mr = $this->df->createMatching(500, 100, '2024-01-01', $expirationDate);

        $job = $this->createMatchingReminderJob();
        $handler = new TestMatchingReminderHandler();
        $result = $handler->callHandler(Carbon::now(), $job, Carbon::now());

        // Email should be sent since rule has remaining capacity
        Mail::assertSent(MatchingExpirationReminderEmail::class);

        // Check that the remaining value is calculated correctly
        $this->assertNotNull($result);
        $this->assertEquals(500, $result->rule_details[0]['remaining']);
    }

    public function test_email_includes_all_active_rules()
    {
        Mail::fake();

        $this->df->createUser();

        // Create an expiring rule (within 45 days)
        $expiringDate = Carbon::now()->addDays(20)->format('Y-m-d');
        $this->df->createMatchingRule(300, 100, '2024-01-01', $expiringDate);
        $this->df->createAccountMatching();

        // Create a non-expiring rule (more than 45 days away)
        $futureDate = Carbon::now()->addDays(100)->format('Y-m-d');
        $this->df->createMatchingRule(500, 50, '2024-01-01', $futureDate);
        $this->df->createAccountMatching();

        $job = $this->createMatchingReminderJob();
        $handler = new TestMatchingReminderHandler();
        $result = $handler->callHandler(Carbon::now(), $job, Carbon::now());

        // Email should be sent (triggered by expiring rule)
        // Check that our user's email contains both rules
        Mail::assertSent(MatchingExpirationReminderEmail::class, function ($mail) {
            return $mail->account->id === $this->df->userAccount->id
                && count($mail->expiringRules) >= 1
                && count($mail->allRules) >= 2;
        });
    }

    public function test_multiple_accounts_get_separate_emails()
    {
        Mail::fake();

        // Create first user with expiring rule
        $this->df->createUser();
        $expiringDate = Carbon::now()->addDays(20)->format('Y-m-d');
        $this->df->createMatching(300, 100, '2024-01-01', $expiringDate);
        $account1 = $this->df->userAccount;

        // Create second user with same expiring rule
        $this->df->createUser();
        $this->df->createAccountMatching(); // Associates with the same matching rule

        $job = $this->createMatchingReminderJob();
        $handler = new TestMatchingReminderHandler();
        $result = $handler->callHandler(Carbon::now(), $job, Carbon::now());

        // Both accounts should receive emails - verify at least 2 were sent
        $sentCount = Mail::sent(MatchingExpirationReminderEmail::class)->count();
        $this->assertGreaterThanOrEqual(2, $sentCount, "Expected at least 2 emails to be sent");

        // Verify emails were sent to both our test accounts
        Mail::assertSent(MatchingExpirationReminderEmail::class, function ($mail) use ($account1) {
            return $mail->account->id === $account1->id;
        });
        Mail::assertSent(MatchingExpirationReminderEmail::class, function ($mail) {
            return $mail->account->id === $this->df->userAccount->id;
        });
    }

    public function test_log_entry_contains_rule_details()
    {
        $this->df->createUser();
        $expirationDate = Carbon::now()->addDays(30)->format('Y-m-d');
        $mr = $this->df->createMatching(500, 100, '2024-01-01', $expirationDate);

        $job = $this->createMatchingReminderJob();
        $handler = new TestMatchingReminderHandler();
        $result = $handler->callHandler(Carbon::now(), $job, Carbon::now());

        $this->assertNotNull($result);
        $this->assertIsArray($result->rule_details);
        $this->assertCount(1, $result->rule_details);
        $this->assertEquals($mr->id, $result->rule_details[0]['rule_id']);
        $this->assertEquals(500, $result->rule_details[0]['remaining']);
        $this->assertTrue($result->rule_details[0]['is_expiring']);
    }

    public function test_scheduled_job_ext_maps_include_matching_reminder()
    {
        $this->assertArrayHasKey(ScheduledJobExt::ENTITY_MATCHING_REMINDER, ScheduledJobExt::$entityMap);
        $this->assertEquals('Matching Reminder', ScheduledJobExt::$entityMap[ScheduledJobExt::ENTITY_MATCHING_REMINDER]);
    }

    public function test_matching_reminder_log_tracks_last_run_date()
    {
        $this->df->createUser();
        $expirationDate = Carbon::now()->addDays(30)->format('Y-m-d');
        $this->df->createMatching(500, 100, '2024-01-01', $expirationDate);

        $job = $this->createMatchingReminderJob();
        $handler = new TestMatchingReminderHandler();

        $asOf = Carbon::now();
        $result = $handler->callHandler($asOf, $job, $asOf);

        // Verify log was created with correct sent_at date
        $this->assertNotNull($result);
        $this->assertEquals($asOf->toDateString(), $result->sent_at->toDateString());

        // Verify lastGeneratedReportDate works
        $jobExt = ScheduledJobExt::find($job->id);
        $lastRun = $jobExt->lastGeneratedReportDate();
        $this->assertNotNull($lastRun);
        $this->assertEquals($asOf->toDateString(), $lastRun->toDateString());
    }
}

// Test helper class to expose the protected handler method
class TestMatchingReminderHandler
{
    use MatchingReminderTrait;

    public function callHandler($shouldRunBy, $schedule, $asOf, $skipDataCheck = false)
    {
        return $this->matchingReminderScheduleDue($shouldRunBy, $schedule, $asOf, $skipDataCheck);
    }
}
