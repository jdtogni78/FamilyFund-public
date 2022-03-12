<?php namespace Tests\Repositories;

use App\Models\MatchingRule;
use App\Repositories\MatchingRuleRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class MatchingRuleRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var MatchingRuleRepository
     */
    protected $matchingRuleRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->matchingRuleRepo = \App::make(MatchingRuleRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_matching_rule()
    {
        $matchingRule = MatchingRule::factory()->make()->toArray();

        $createdMatchingRule = $this->matchingRuleRepo->create($matchingRule);

        $createdMatchingRule = $createdMatchingRule->toArray();
        $this->assertArrayHasKey('id', $createdMatchingRule);
        $this->assertNotNull($createdMatchingRule['id'], 'Created MatchingRule must have id specified');
        $this->assertNotNull(MatchingRule::find($createdMatchingRule['id']), 'MatchingRule with given id must be in DB');
        $this->assertModelData($matchingRule, $createdMatchingRule);
    }

    /**
     * @test read
     */
    public function test_read_matching_rule()
    {
        $matchingRule = MatchingRule::factory()->create();

        $dbMatchingRule = $this->matchingRuleRepo->find($matchingRule->id);

        $dbMatchingRule = $dbMatchingRule->toArray();
        $this->assertModelData($matchingRule->toArray(), $dbMatchingRule);
    }

    /**
     * @test update
     */
    public function test_update_matching_rule()
    {
        $matchingRule = MatchingRule::factory()->create();
        $fakeMatchingRule = MatchingRule::factory()->make()->toArray();

        $updatedMatchingRule = $this->matchingRuleRepo->update($fakeMatchingRule, $matchingRule->id);

        $this->assertModelData($fakeMatchingRule, $updatedMatchingRule->toArray());
        $dbMatchingRule = $this->matchingRuleRepo->find($matchingRule->id);
        $this->assertModelData($fakeMatchingRule, $dbMatchingRule->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_matching_rule()
    {
        $matchingRule = MatchingRule::factory()->create();

        $resp = $this->matchingRuleRepo->delete($matchingRule->id);

        $this->assertTrue($resp);
        $this->assertNull(MatchingRule::find($matchingRule->id), 'MatchingRule should not exist in DB');
    }
}
