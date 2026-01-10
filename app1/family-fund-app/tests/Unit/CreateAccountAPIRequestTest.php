<?php

namespace Tests\Unit;

use App\Http\Requests\API\CreateAccountAPIRequest;
use App\Models\Account;
use Tests\TestCase;

/**
 * Unit tests for CreateAccountAPIRequest
 */
class CreateAccountAPIRequestTest extends TestCase
{
    private CreateAccountAPIRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new CreateAccountAPIRequest();
    }

    public function test_authorize_returns_true()
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_rules_returns_account_rules()
    {
        $result = $this->request->rules();

        $this->assertIsArray($result);
        $this->assertEquals(Account::$rules, $result);
    }

    public function test_rules_contains_expected_fields()
    {
        $rules = $this->request->rules();

        // Check common expected fields exist
        $this->assertArrayHasKey('nickname', $rules);
        $this->assertArrayHasKey('fund_id', $rules);
    }
}
