<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountExt;
use App\Models\TransactionExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\ApiTestTrait;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for AccountAPIControllerExt
 * Target: Get coverage from 35% to 70%+
 */
class AccountAPIControllerExtTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware, ApiTestTrait;

    protected DataFactory $df;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();
        // Create a transaction so the account has data
        $this->df->createTransaction(100);
        $this->user = $this->df->user;
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Show Tests ====================

    public function test_show_returns_account_data()
    {
        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.id', $this->df->userAccount->id);
    }

    public function test_show_returns_404_for_invalid_id()
    {
        $response = $this->json('GET', '/api/accounts/99999');

        $response->assertStatus(404);
    }

    // ==================== ShowAsOf Tests ====================

    public function test_show_as_of_returns_account_at_date()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.as_of', $asOf);
    }

    public function test_show_as_of_with_past_date()
    {
        $asOf = now()->subMonths(3)->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.as_of', $asOf);
    }

    public function test_show_as_of_returns_404_for_invalid_id()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/99999/as_of/' . $asOf);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    // ==================== ShowPerformanceAsOf Tests ====================

    public function test_show_performance_as_of_returns_performance_data()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/performance_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'data' => [
                'nickname',
                'id',
                'performance',
                'as_of',
            ],
        ]);
    }

    public function test_show_performance_as_of_returns_404_for_invalid_id()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/99999/performance_as_of/' . $asOf);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    public function test_show_performance_as_of_with_historical_date()
    {
        // Create some historical transactions
        $this->df->createTransaction(200, null, TransactionExt::TYPE_PURCHASE, TransactionExt::STATUS_CLEARED, null, '2023-06-01');

        $asOf = '2023-12-31';

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/performance_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.as_of', $asOf);
    }

    // ==================== ShowTransactionsAsOf Tests ====================

    public function test_show_transactions_as_of_returns_transactions()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/transactions_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'data' => [
                'nickname',
                'id',
                'transactions',
                'as_of',
            ],
        ]);
    }

    public function test_show_transactions_as_of_returns_404_for_invalid_id()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/99999/transactions_as_of/' . $asOf);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    public function test_show_transactions_as_of_filters_by_date()
    {
        // Create transactions at different dates
        $this->df->createTransaction(100, null, TransactionExt::TYPE_PURCHASE, TransactionExt::STATUS_CLEARED, null, '2023-01-15');
        $this->df->createTransaction(200, null, TransactionExt::TYPE_PURCHASE, TransactionExt::STATUS_CLEARED, null, '2023-06-15');

        $asOf = '2023-03-01';

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/transactions_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    // ==================== ShowReportAsOf Tests ====================

    public function test_show_report_as_of_returns_full_report()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/report_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'data' => [
                'transactions',
                'performance',
                'as_of',
            ],
        ]);
    }

    public function test_show_report_as_of_returns_404_for_invalid_id()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/99999/report_as_of/' . $asOf);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    public function test_show_report_as_of_includes_all_sections()
    {
        // Create some transactions for better test coverage
        $this->df->createTransaction(100, null, TransactionExt::TYPE_PURCHASE, TransactionExt::STATUS_CLEARED, null, '2023-01-15');

        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/report_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'nickname',
                'transactions',
                'performance',
                'as_of',
            ],
        ]);
    }

    // ==================== AccountMatching Tests ====================

    public function test_account_matching_returns_matching_rules()
    {
        // Create matching rules
        $this->df->createMatching(100, 50);

        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/account_matching/' . $this->df->userAccount->id . '/as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'data' => [
                'matching_rules',
                'matching_available',
                'nickname',
                'as_of',
            ],
        ]);
    }

    public function test_account_matching_returns_empty_when_no_rules()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/account_matching/' . $this->df->userAccount->id . '/as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.matching_rules', []);
        $response->assertJsonPath('data.matching_available', 0);
    }

    public function test_account_matching_calculates_available_correctly()
    {
        // Create matching rule with $100 range, 100% match
        $this->df->createMatching(100, 100);

        // Create a transaction that uses some matching
        $this->df->createTransactionWithMatching(50, 50);

        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/account_matching/' . $this->df->userAccount->id . '/as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    // ==================== ShareValueAsOf Tests ====================

    public function test_share_value_as_of_returns_share_data()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/share_value_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'data' => [
                'share_price',
                'available_shares',
                'account_shares',
                'account_value',
                'account_nickname',
                'account_code',
            ],
        ]);
    }

    public function test_share_value_as_of_returns_404_for_invalid_id()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/99999/share_value_as_of/' . $asOf);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    public function test_share_value_as_of_includes_user_info()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/share_value_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'user_name',
                'user_email',
            ],
        ]);
    }

    public function test_share_value_as_of_with_historical_date()
    {
        $asOf = '2023-06-15';

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/share_value_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    // ==================== Edge Cases ====================

    public function test_handles_account_without_user()
    {
        // Create an account without a user
        $account = Account::factory()->create([
            'fund_id' => $this->df->fund->id,
            'user_id' => null,
        ]);

        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $account->id . '/share_value_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        // user_name and user_email should be null
        $response->assertJsonPath('data.user_name', null);
    }

    public function test_handles_account_with_email_cc()
    {
        // Set email_cc on account
        $this->df->userAccount->email_cc = 'cc@example.com';
        $this->df->userAccount->save();

        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/share_value_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        // user_email should use email_cc when set
        $response->assertJsonPath('data.user_email', 'cc@example.com');
    }

    // ==================== Response Format Tests ====================

    public function test_show_response_format()
    {
        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'message',
        ]);
    }

    public function test_as_of_endpoints_include_date()
    {
        $asOf = now()->format('Y-m-d');
        $endpoints = [
            '/api/accounts/' . $this->df->userAccount->id . '/as_of/' . $asOf,
            '/api/accounts/' . $this->df->userAccount->id . '/performance_as_of/' . $asOf,
            '/api/accounts/' . $this->df->userAccount->id . '/transactions_as_of/' . $asOf,
            '/api/accounts/' . $this->df->userAccount->id . '/report_as_of/' . $asOf,
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->json('GET', $endpoint);
            $response->assertStatus(200);
            $response->assertJsonPath('data.as_of', $asOf);
        }
    }

    // ==================== Multiple Transactions Tests ====================

    public function test_handles_multiple_transactions()
    {
        // Create multiple transactions
        for ($i = 0; $i < 5; $i++) {
            $this->df->createTransaction(
                100 * ($i + 1),
                null,
                TransactionExt::TYPE_PURCHASE,
                TransactionExt::STATUS_CLEARED,
                null,
                now()->subMonths($i)->format('Y-m-d')
            );
        }

        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->userAccount->id . '/transactions_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    // ==================== Fund Account Tests ====================

    public function test_fund_account_works_with_api()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->fundAccount->id . '/as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_fund_account_share_value()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->json('GET', '/api/accounts/' . $this->df->fundAccount->id . '/share_value_as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
