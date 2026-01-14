<?php

namespace Tests\Unit;

use App\Mail\MatchingExpirationReminderEmail;
use App\Models\Account;
use App\Models\MatchingRule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MatchingExpirationReminderEmailTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mailable_can_be_instantiated()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user, 'user')->create();
        $rule = MatchingRule::factory()->create([
            'date_end' => Carbon::now()->addDays(30),
        ]);

        $expiringRules = [
            [
                'rule' => $rule,
                'remaining' => 300,
                'total' => 500,
                'days_left' => 30,
                'is_expiring' => true,
            ]
        ];

        $allRules = $expiringRules;

        $mailable = new MatchingExpirationReminderEmail($account, $expiringRules, $allRules);

        $this->assertInstanceOf(MatchingExpirationReminderEmail::class, $mailable);
        $this->assertEquals($account->id, $mailable->account->id);
        $this->assertCount(1, $mailable->expiringRules);
        $this->assertCount(1, $mailable->allRules);
    }

    public function test_mailable_subject_singular()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user, 'user')->create();
        $rule = MatchingRule::factory()->create();

        $expiringRules = [
            ['rule' => $rule, 'remaining' => 300, 'total' => 500, 'days_left' => 30, 'is_expiring' => true]
        ];

        $mailable = new MatchingExpirationReminderEmail($account, $expiringRules, $expiringRules);
        $mailable->build();

        $this->assertEquals('Matching Opportunity Expiring Soon', $mailable->subject);
    }

    public function test_mailable_subject_plural()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user, 'user')->create();
        $rule1 = MatchingRule::factory()->create();
        $rule2 = MatchingRule::factory()->create();

        $expiringRules = [
            ['rule' => $rule1, 'remaining' => 300, 'total' => 500, 'days_left' => 30, 'is_expiring' => true],
            ['rule' => $rule2, 'remaining' => 200, 'total' => 400, 'days_left' => 15, 'is_expiring' => true],
        ];

        $mailable = new MatchingExpirationReminderEmail($account, $expiringRules, $expiringRules);
        $mailable->build();

        $this->assertEquals('2 Matching Opportunities Expiring Soon', $mailable->subject);
    }

    public function test_mailable_api_data()
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $account = Account::factory()->for($user, 'user')->create(['nickname' => 'Test Account']);
        $rule = MatchingRule::factory()->create();

        $expiringRules = [
            ['rule' => $rule, 'remaining' => 300, 'total' => 500, 'days_left' => 30, 'is_expiring' => true]
        ];
        $allRules = [
            ['rule' => $rule, 'remaining' => 300, 'total' => 500, 'days_left' => 30, 'is_expiring' => true],
            ['rule' => MatchingRule::factory()->create(), 'remaining' => 1000, 'total' => 1000, 'days_left' => 100, 'is_expiring' => false],
        ];

        $mailable = new MatchingExpirationReminderEmail($account, $expiringRules, $allRules);
        $mailable->build();

        $this->assertEquals('Test User', $mailable->api['to']);
        $this->assertEquals($account->id, $mailable->api['account']->id);
        $this->assertCount(1, $mailable->api['expiringRules']);
        $this->assertCount(2, $mailable->api['allRules']);
        $this->assertEquals('Matching Expiration Reminder', $mailable->api['report_name']);
    }

    public function test_mailable_uses_account_nickname_when_no_user()
    {
        $account = Account::factory()->create(['nickname' => 'Orphan Account']);
        // Ensure no user is associated
        $account->user_id = null;
        $account->save();

        $rule = MatchingRule::factory()->create();
        $expiringRules = [
            ['rule' => $rule, 'remaining' => 300, 'total' => 500, 'days_left' => 30, 'is_expiring' => true]
        ];

        $mailable = new MatchingExpirationReminderEmail($account, $expiringRules, $expiringRules);
        $mailable->build();

        $this->assertEquals('Orphan Account', $mailable->api['to']);
    }

    public function test_mailable_renders_without_errors()
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user, 'user')->create();
        $rule = MatchingRule::factory()->create([
            'name' => 'Test Matching Rule',
            'dollar_range_start' => 0,
            'dollar_range_end' => 500,
            'match_percent' => 100,
            'date_end' => Carbon::now()->addDays(30),
        ]);

        $expiringRules = [
            ['rule' => $rule, 'remaining' => 300, 'total' => 500, 'days_left' => 30, 'is_expiring' => true]
        ];

        $mailable = new MatchingExpirationReminderEmail($account, $expiringRules, $expiringRules);

        // This will throw an exception if the view has errors
        $rendered = $mailable->render();

        $this->assertStringContainsString('Matching Expiring Soon', $rendered);
        $this->assertStringContainsString('Test Matching Rule', $rendered);
        $this->assertStringContainsString('$300 of $500', $rendered);
    }
}
