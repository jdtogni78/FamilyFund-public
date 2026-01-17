<?php

namespace Tests\Unit;

use App\Http\Requests\CreateFundWithSetupRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for CreateFundWithSetupRequest validation rules
 *
 * Tests all validation scenarios for fund setup form
 */
class CreateFundWithSetupRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected CreateFundWithSetupRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new CreateFundWithSetupRequest();
    }

    /**
     * Helper method to validate data against request rules
     */
    protected function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->request->rules());
    }

    // ==================== Authorization Tests ====================

    public function test_authorize_returns_true()
    {
        $this->assertTrue($this->request->authorize());
    }

    // ==================== Fund Name Validation ====================

    public function test_fund_name_is_required()
    {
        $validator = $this->validate([
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_fund_name_accepts_valid_string()
    {
        $validator = $this->validate([
            'name' => 'Valid Fund Name',
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('name'));
    }

    public function test_fund_name_must_be_string()
    {
        $validator = $this->validate([
            'name' => 12345, // Not a string
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_fund_name_max_length_30()
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 31), // 31 characters
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_fund_name_accepts_exactly_30_characters()
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 30), // Exactly 30 characters
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('name'));
    }

    // ==================== Goal Validation ====================

    public function test_goal_is_optional()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('goal'));
    }

    public function test_goal_accepts_valid_string()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'goal' => 'Investment goal description',
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('goal'));
    }

    public function test_goal_max_length_1024()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'goal' => str_repeat('a', 1025), // 1025 characters
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('goal', $validator->errors()->toArray());
    }

    public function test_goal_accepts_exactly_1024_characters()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'goal' => str_repeat('a', 1024), // Exactly 1024 characters
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('goal'));
    }

    // ==================== Account Nickname Validation ====================

    public function test_account_nickname_is_optional()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('account_nickname'));
    }

    public function test_account_nickname_accepts_valid_string()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'account_nickname' => 'Custom Account Name',
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('account_nickname'));
    }

    public function test_account_nickname_max_length_100()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'account_nickname' => str_repeat('a', 101), // 101 characters
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('account_nickname', $validator->errors()->toArray());
    }

    // ==================== Portfolio Source Validation ====================

    public function test_portfolio_source_is_required()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('portfolio_source', $validator->errors()->toArray());
    }

    public function test_portfolio_source_accepts_single_string()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'SINGLE_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('portfolio_source'));
    }

    public function test_portfolio_source_accepts_array()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => ['SOURCE_1', 'SOURCE_2'],
        ]);

        $this->assertFalse($validator->errors()->has('portfolio_source'));
    }

    public function test_portfolio_source_max_length_30()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => str_repeat('a', 31), // 31 characters
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_portfolio_source_array_validates_each_item()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => [
                'VALID_SOURCE',
                str_repeat('a', 31), // Invalid - too long
            ],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('portfolio_source.1', $validator->errors()->toArray());
    }

    // ==================== Initial Shares Validation ====================

    public function test_initial_shares_is_optional()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('initial_shares'));
    }

    public function test_initial_shares_must_be_numeric()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_shares' => 'not-a-number',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('initial_shares', $validator->errors()->toArray());
    }

    public function test_initial_shares_minimum_value()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_shares' => 0, // Less than minimum
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('initial_shares', $validator->errors()->toArray());
    }

    public function test_initial_shares_accepts_minimum_valid_value()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_shares' => 0.00000001, // Minimum valid
        ]);

        $this->assertFalse($validator->errors()->has('initial_shares'));
    }

    public function test_initial_shares_maximum_value()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_shares' => 9999999999999.9992, // Exceeds maximum
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('initial_shares', $validator->errors()->toArray());
    }

    public function test_initial_shares_accepts_maximum_valid_value()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_shares' => 9999999999999.9991, // Maximum valid
        ]);

        $this->assertFalse($validator->errors()->has('initial_shares'));
    }

    public function test_initial_shares_accepts_high_precision_decimal()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_shares' => 123.45678901,
        ]);

        $this->assertFalse($validator->errors()->has('initial_shares'));
    }

    // ==================== Initial Value Validation ====================

    public function test_initial_value_is_optional()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('initial_value'));
    }

    public function test_initial_value_must_be_numeric()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_value' => 'not-a-number',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('initial_value', $validator->errors()->toArray());
    }

    public function test_initial_value_minimum()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_value' => 0, // Less than minimum
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('initial_value', $validator->errors()->toArray());
    }

    public function test_initial_value_accepts_minimum_valid_value()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_value' => 0.01, // Minimum valid
        ]);

        $this->assertFalse($validator->errors()->has('initial_value'));
    }

    public function test_initial_value_maximum()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_value' => 100000000000, // Exceeds maximum
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('initial_value', $validator->errors()->toArray());
    }

    public function test_initial_value_accepts_maximum_valid_value()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'initial_value' => 99999999999.99, // Maximum valid
        ]);

        $this->assertFalse($validator->errors()->has('initial_value'));
    }

    // ==================== Transaction Description Validation ====================

    public function test_transaction_description_is_optional()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('transaction_description'));
    }

    public function test_transaction_description_accepts_valid_string()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'transaction_description' => 'Custom transaction description',
        ]);

        $this->assertFalse($validator->errors()->has('transaction_description'));
    }

    public function test_transaction_description_max_length_255()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'transaction_description' => str_repeat('a', 256), // 256 characters
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('transaction_description', $validator->errors()->toArray());
    }

    // ==================== Create Initial Transaction Validation ====================

    public function test_create_initial_transaction_is_optional()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('create_initial_transaction'));
    }

    public function test_create_initial_transaction_accepts_boolean()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'create_initial_transaction' => true,
        ]);

        $this->assertFalse($validator->errors()->has('create_initial_transaction'));
    }

    // ==================== Preview Validation ====================

    public function test_preview_is_optional()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
        ]);

        $this->assertFalse($validator->errors()->has('preview'));
    }

    public function test_preview_accepts_boolean()
    {
        $validator = $this->validate([
            'name' => 'Test Fund',
            'portfolio_source' => 'TEST_SOURCE',
            'preview' => true,
        ]);

        $this->assertFalse($validator->errors()->has('preview'));
    }

    // ==================== Complete Valid Data Tests ====================

    public function test_validates_complete_valid_data()
    {
        $validator = $this->validate([
            'name' => 'Complete Test Fund',
            'goal' => 'Long-term investment growth',
            'account_nickname' => 'Primary Fund Account',
            'portfolio_source' => 'COMPLETE_TEST',
            'create_initial_transaction' => true,
            'initial_shares' => 1000.12345678,
            'initial_value' => 5000.00,
            'transaction_description' => 'Initial fund setup transaction',
            'preview' => false,
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_validates_minimal_valid_data()
    {
        $validator = $this->validate([
            'name' => 'Minimal Fund',
            'portfolio_source' => 'MINIMAL_TEST',
        ]);

        $this->assertFalse($validator->fails());
    }

    // ==================== Custom Messages Tests ====================

    public function test_custom_error_messages_exist()
    {
        $messages = $this->request->messages();

        $this->assertArrayHasKey('portfolio_source.required', $messages);
        $this->assertArrayHasKey('portfolio_source.*.required', $messages);
        $this->assertArrayHasKey('initial_shares.min', $messages);
        $this->assertArrayHasKey('initial_value.min', $messages);
    }

    public function test_custom_attribute_names_exist()
    {
        $attributes = $this->request->attributes();

        $this->assertArrayHasKey('portfolio_source', $attributes);
        $this->assertArrayHasKey('initial_shares', $attributes);
        $this->assertArrayHasKey('initial_value', $attributes);
        $this->assertArrayHasKey('account_nickname', $attributes);
        $this->assertArrayHasKey('transaction_description', $attributes);
    }
}
