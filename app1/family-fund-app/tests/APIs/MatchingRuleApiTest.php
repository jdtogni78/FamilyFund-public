<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\MatchingRule;

use PHPUnit\Framework\Attributes\Test;
class MatchingRuleApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    #[Test]
    public function test_create_matching_rule()
    {
        $matchingRule = MatchingRule::factory()->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/matching_rules', $matchingRule
        );

        $this->assertApiResponse($matchingRule);
    }

    #[Test]
    public function test_read_matching_rule()
    {
        $matchingRule = MatchingRule::factory()->create();

        $this->response = $this->json(
            'GET',
            '/api/matching_rules/'.$matchingRule->id
        );

        $this->assertApiResponse($matchingRule->toArray());
    }

    #[Test]
    public function test_update_matching_rule()
    {
        $matchingRule = MatchingRule::factory()->create();
        $editedMatchingRule = MatchingRule::factory()->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/matching_rules/'.$matchingRule->id,
            $editedMatchingRule
        );

        $this->assertApiResponse($editedMatchingRule);
    }

    #[Test]
    public function test_delete_matching_rule()
    {
        $matchingRule = MatchingRule::factory()->create();

        $this->response = $this->json(
            'DELETE',
             '/api/matching_rules/'.$matchingRule->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/matching_rules/'.$matchingRule->id
        );

        $this->response->assertStatus(404);
    }
}
