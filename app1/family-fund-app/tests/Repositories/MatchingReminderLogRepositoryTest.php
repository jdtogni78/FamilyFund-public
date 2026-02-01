<?php

namespace Tests\Repositories;

use App\Models\MatchingReminderLog;
use App\Repositories\MatchingReminderLogRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class MatchingReminderLogRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    protected MatchingReminderLogRepository $matchingReminderLogRepo;

    public function setUp(): void
    {
        parent::setUp();
        $this->matchingReminderLogRepo = \App::make(MatchingReminderLogRepository::class);
    }
    public function test_create_matching_reminder_log()
    {
        $matchingReminderLog = MatchingReminderLog::factory()->make()->toArray();

        $createdLog = $this->matchingReminderLogRepo->create($matchingReminderLog);

        $createdLog = $createdLog->toArray();
        $this->assertArrayHasKey('id', $createdLog);
        $this->assertNotNull($createdLog['id'], 'Created MatchingReminderLog must have id specified');
        $this->assertNotNull(MatchingReminderLog::find($createdLog['id']), 'MatchingReminderLog with given id must be in DB');
    }
    public function test_read_matching_reminder_log()
    {
        $matchingReminderLog = MatchingReminderLog::factory()->create();

        $dbLog = $this->matchingReminderLogRepo->find($matchingReminderLog->id);

        $this->assertNotNull($dbLog);
        $this->assertEquals($matchingReminderLog->id, $dbLog->id);
        $this->assertEquals($matchingReminderLog->account_id, $dbLog->account_id);
    }
    public function test_update_matching_reminder_log()
    {
        $matchingReminderLog = MatchingReminderLog::factory()->create();
        $newRulesCount = 5;

        $updatedLog = $this->matchingReminderLogRepo->update(['rules_count' => $newRulesCount], $matchingReminderLog->id);

        $this->assertEquals($newRulesCount, $updatedLog->rules_count);
        $dbLog = $this->matchingReminderLogRepo->find($matchingReminderLog->id);
        $this->assertEquals($newRulesCount, $dbLog->rules_count);
    }
    public function test_delete_matching_reminder_log()
    {
        $matchingReminderLog = MatchingReminderLog::factory()->create();

        $resp = $this->matchingReminderLogRepo->delete($matchingReminderLog->id);

        $this->assertTrue($resp);
        $this->assertNull(MatchingReminderLog::find($matchingReminderLog->id), 'MatchingReminderLog should not exist in DB');
    }
    public function test_matching_reminder_log_relationships()
    {
        $matchingReminderLog = MatchingReminderLog::factory()->create();

        $this->assertNotNull($matchingReminderLog->account);
        $this->assertNotNull($matchingReminderLog->scheduledJob);
    }
    public function test_rule_details_is_array()
    {
        $ruleDetails = [
            ['rule_id' => 1, 'rule_name' => 'Test Rule', 'remaining' => 100, 'expires' => '2025-12-31', 'is_expiring' => true],
            ['rule_id' => 2, 'rule_name' => 'Test Rule 2', 'remaining' => 200, 'expires' => '2026-06-30', 'is_expiring' => false],
        ];

        $matchingReminderLog = MatchingReminderLog::factory()->create([
            'rule_details' => $ruleDetails,
            'rules_count' => 2,
        ]);

        $dbLog = MatchingReminderLog::find($matchingReminderLog->id);
        $this->assertIsArray($dbLog->rule_details);
        $this->assertCount(2, $dbLog->rule_details);
        $this->assertEquals('Test Rule', $dbLog->rule_details[0]['rule_name']);
    }
}
