<?php namespace Tests\Repositories;

use App\Models\AccountMatchingRule;
use App\Repositories\AccountMatchingRuleRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

use PHPUnit\Framework\Attributes\Test;
class AccountMatchingRuleRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var AccountMatchingRuleRepository
     */
    protected $accountMatchingRuleRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->accountMatchingRuleRepo = \App::make(AccountMatchingRuleRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_account_matching_rule()
    {
        $accountMatchingRule = AccountMatchingRule::factory()->make()->toArray();

        $createdAccountMatchingRule = $this->accountMatchingRuleRepo->create($accountMatchingRule);

        $createdAccountMatchingRule = $createdAccountMatchingRule->toArray();
        $this->assertArrayHasKey('id', $createdAccountMatchingRule);
        $this->assertNotNull($createdAccountMatchingRule['id'], 'Created AccountMatchingRule must have id specified');
        $this->assertNotNull(AccountMatchingRule::find($createdAccountMatchingRule['id']), 'AccountMatchingRule with given id must be in DB');
        $this->assertModelData($accountMatchingRule, $createdAccountMatchingRule);
    }

    /**
     * @test read
     */
    public function test_read_account_matching_rule()
    {
        $accountMatchingRule = AccountMatchingRule::factory()->create();

        $dbAccountMatchingRule = $this->accountMatchingRuleRepo->find($accountMatchingRule->id);

        $dbAccountMatchingRule = $dbAccountMatchingRule->toArray();
        $this->assertModelData($accountMatchingRule->toArray(), $dbAccountMatchingRule);
    }

    /**
     * @test update
     */
    public function test_update_account_matching_rule()
    {
        $accountMatchingRule = AccountMatchingRule::factory()->create();
        $fakeAccountMatchingRule = AccountMatchingRule::factory()->make()->toArray();

        $updatedAccountMatchingRule = $this->accountMatchingRuleRepo->update($fakeAccountMatchingRule, $accountMatchingRule->id);

        $this->assertModelData($fakeAccountMatchingRule, $updatedAccountMatchingRule->toArray());
        $dbAccountMatchingRule = $this->accountMatchingRuleRepo->find($accountMatchingRule->id);
        $this->assertModelData($fakeAccountMatchingRule, $dbAccountMatchingRule->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_account_matching_rule()
    {
        $accountMatchingRule = AccountMatchingRule::factory()->create();

        $resp = $this->accountMatchingRuleRepo->delete($accountMatchingRule->id);

        $this->assertTrue($resp);
        $this->assertNull(AccountMatchingRule::find($accountMatchingRule->id), 'AccountMatchingRule should not exist in DB');
    }
}
