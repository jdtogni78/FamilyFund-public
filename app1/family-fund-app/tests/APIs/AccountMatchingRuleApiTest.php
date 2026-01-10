<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\AccountMatchingRule;

use PHPUnit\Framework\Attributes\Test;
class AccountMatchingRuleApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_account_matching_rule()
    {
        $accountMatchingRule = AccountMatchingRule::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/account_matching_rules', $accountMatchingRule
        );

        $this->assertApiResponse($accountMatchingRule);
    }

    #[Test]
    public function test_read_account_matching_rule()
    {
        $accountMatchingRule = AccountMatchingRule::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/account_matching_rules/'.$accountMatchingRule->id
        );

        $this->assertApiResponse($accountMatchingRule->toArray());
    }

    #[Test]
    public function test_update_account_matching_rule()
    {
        $accountMatchingRule = AccountMatchingRule::factory()->create();
        $editedAccountMatchingRule = AccountMatchingRule::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/account_matching_rules/'.$accountMatchingRule->id,
            $editedAccountMatchingRule
        );

        $this->assertApiResponse($editedAccountMatchingRule);
    }

    #[Test]
    public function test_delete_account_matching_rule()
    {
        $accountMatchingRule = AccountMatchingRule::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/account_matching_rules/'.$accountMatchingRule->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/account_matching_rules/'.$accountMatchingRule->id
        );

        $this->response->assertStatus(404);
    }
}
