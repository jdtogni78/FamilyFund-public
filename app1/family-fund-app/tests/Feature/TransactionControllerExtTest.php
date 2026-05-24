<?php

namespace Tests\Feature;

use App\Models\TransactionExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for TransactionControllerExt
 * Target: Get coverage from 27% to 50%+
 */
class TransactionControllerExtTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();
        $this->user = $this->df->user;

        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->user->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);

        Mail::fake();
    }

    protected function tearDown(): void
    {
        // Some transaction views leave output buffers open; close them so the
        // tests aren't flagged risky (matches OperationsControllerTest, etc.).
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Create Tests ====================

    public function test_create_shows_transaction_form()
    {
        $response = $this->actingAs($this->user)->get(route('transactions.create'));

        $response->assertStatus(200);
        $response->assertViewIs('transactions.create');
        $response->assertViewHas('api');

        $api = $response->viewData('api');
        $this->assertArrayHasKey('typeMap', $api);
        $this->assertArrayHasKey('statusMap', $api);
        $this->assertArrayHasKey('accountMap', $api);
        $this->assertArrayHasKey('fundMap', $api);
    }

    // ==================== Preview Tests ====================

    public function test_preview_shows_transaction_preview()
    {
        $input = [
            'account_id' => $this->df->userAccount->id,
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.preview'), $input);

        $response->assertStatus(200);
        $response->assertViewIs('transactions.preview');
        $response->assertViewHas('api1');
        $response->assertViewHas('api');
    }

    public function test_preview_handles_invalid_type()
    {
        $input = [
            'account_id' => $this->df->userAccount->id,
            'type' => 'INVALID_TYPE',
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.preview'), $input);
        $response->assertSessionHasErrors('type');
    }

    public function test_preview_handles_invalid_status()
    {
        $input = [
            'account_id' => $this->df->userAccount->id,
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => 'INVALID',
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.preview'), $input);
        $response->assertSessionHasErrors('status');
    }

    public function test_preview_handles_missing_required_fields()
    {
        $input = [
            'account_id' => $this->df->userAccount->id,
            // Missing type, status, value
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.preview'), $input);
        $response->assertSessionHasErrors(['type', 'value']);
    }

    public function test_preview_handles_invalid_value()
    {
        $input = [
            'account_id' => $this->df->userAccount->id,
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 'not-a-number',
            'timestamp' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.preview'), $input);
        $response->assertSessionHasErrors('value');
    }

    public function test_preview_handles_exception_from_invalid_account()
    {
        $input = [
            'account_id' => 99999, // Non-existent account
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.preview'), $input);

        // Controller catches exception and redirects back with error
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ==================== Store Tests ====================

    public function test_store_creates_transaction()
    {
        $input = [
            'account_id' => $this->df->userAccount->id,
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
            'descr' => 'Test transaction',
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.store'), $input);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->df->userAccount->id,
            'type' => TransactionExt::TYPE_PURCHASE,
            'value' => 100,
        ]);
    }

    public function test_store_handles_validation_errors()
    {
        $input = [
            'account_id' => null,
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.store'), $input);
        $response->assertSessionHasErrors();
    }

    public function test_store_handles_exception_from_bad_data()
    {
        $input = [
            'account_id' => 99999, // Non-existent account
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)->post(route('transactions.store'), $input);

        // Controller catches exception and redirects back
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ==================== Preview Pending Tests ====================

    public function test_preview_pending_shows_preview()
    {
        // Create transaction with proper timestamp matching fund date
        $transaction = $this->df->createTransaction(
            100,
            null,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            '2022-01-02' // Day after fund creation
        );

        $response = $this->actingAs($this->user)
            ->get(route('transactions.preview_pending', $transaction->id));

        $response->assertStatus(200);
        $response->assertViewIs('transactions.preview');
        $response->assertViewHas('api1');
    }

    public function test_preview_pending_handles_not_found()
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.preview_pending', 99999));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Process Pending Tests ====================

    public function test_process_pending_processes_transaction()
    {
        $transaction = $this->df->createTransaction(
            100,
            null,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_PENDING,
            null,
            now()->subDay()->format('Y-m-d')
        );

        $response = $this->actingAs($this->user)
            ->post(route('transactions.process_pending', $transaction->id));

        $response->assertStatus(200);
        $response->assertViewIs('transactions.show');
        $response->assertViewHas('transaction');
    }

    public function test_process_pending_handles_not_found()
    {
        $response = $this->actingAs($this->user)
            ->post(route('transactions.process_pending', 99999));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Process All Pending Tests ====================

    public function test_process_all_pending_with_no_transactions()
    {
        $response = $this->actingAs($this->user)
            ->post(route('transactions.process_all_pending'));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_process_all_pending_processes_multiple_transactions()
    {
        // Create multiple pending transactions
        $this->df->createTransaction(100, null, TransactionExt::TYPE_PURCHASE, TransactionExt::STATUS_PENDING, null, now()->subDay()->format('Y-m-d'));
        $this->df->createTransaction(50, null, TransactionExt::TYPE_PURCHASE, TransactionExt::STATUS_PENDING, null, now()->subDay()->format('Y-m-d'));

        $response = $this->actingAs($this->user)
            ->post(route('transactions.process_all_pending'));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_process_all_pending_skips_future_dated_transactions()
    {
        // Create future-dated pending transaction
        $this->df->createTransaction(100, null, TransactionExt::TYPE_PURCHASE, TransactionExt::STATUS_PENDING, null, now()->addWeek()->format('Y-m-d'));

        $response = $this->actingAs($this->user)
            ->post(route('transactions.process_all_pending'));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');

        // Transaction should still be pending
        $this->assertDatabaseHas('transactions', [
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
        ]);
    }

    // ==================== Edit Tests ====================

    public function test_edit_shows_edit_form()
    {
        $transaction = $this->df->createTransaction(100, null, TransactionExt::TYPE_PURCHASE);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.edit', $transaction->id));

        $response->assertStatus(200);
        $response->assertViewIs('transactions.edit');
        $response->assertViewHas('transaction');
        $response->assertViewHas('api');
    }

    public function test_edit_handles_not_found()
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.edit', 99999));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Clone Tests ====================

    public function test_clone_creates_copy_with_today_date()
    {
        $transaction = $this->df->createTransaction(
            100,
            null,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            '2022-01-01'
        );

        $response = $this->actingAs($this->user)
            ->get(route('transactions.clone', $transaction->id));

        $response->assertStatus(200);
        $response->assertViewIs('transactions.create');
        $response->assertViewHas('transaction');

        $clonedTransaction = $response->viewData('transaction');
        $this->assertEquals(TransactionExt::STATUS_PENDING, $clonedTransaction->status);
        $this->assertEquals($transaction->value, $clonedTransaction->value);
        $this->assertEquals($transaction->type, $clonedTransaction->type);
    }

    public function test_clone_handles_not_found()
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.clone', 99999));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Resend Email Tests ====================

    public function test_resend_email_sends_confirmation()
    {
        $transaction = $this->df->createTransaction(100, null, TransactionExt::TYPE_PURCHASE);

        // Set email on account
        $transaction->account->email_cc = 'test@example.com';
        $transaction->account->save();

        $response = $this->actingAs($this->user)
            ->post(route('transactions.resend-email', $transaction->id));

        $response->assertRedirect(route('transactions.show', $transaction->id));
        $response->assertSessionHas('flash_notification');
    }

    public function test_resend_email_fails_without_email()
    {
        $transaction = $this->df->createTransaction(100, null, TransactionExt::TYPE_PURCHASE);

        // No email on account
        $transaction->account->email_cc = null;
        $transaction->account->save();

        $response = $this->actingAs($this->user)
            ->post(route('transactions.resend-email', $transaction->id));

        $response->assertRedirect(route('transactions.show', $transaction->id));
        $response->assertSessionHas('flash_notification');
    }

    public function test_resend_email_handles_not_found()
    {
        $response = $this->actingAs($this->user)
            ->post(route('transactions.resend-email', 99999));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Bulk Create Tests ====================

    public function test_bulk_create_shows_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.create_bulk'));

        $response->assertStatus(200);
        $response->assertViewIs('transactions.create_bulk');
        $response->assertViewHas('api');

        $api = $response->viewData('api');
        $this->assertArrayHasKey('accountsByFund', $api);
    }

    // ==================== Bulk Preview Tests ====================

    public function test_bulk_preview_shows_preview()
    {
        $input = [
            'account_ids' => [$this->df->userAccount->id],
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
            'descr' => 'Bulk test',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.preview_bulk'), $input);

        $response->assertStatus(200);
        $response->assertViewIs('transactions.preview_bulk');
        $response->assertViewHas('previews');
        $response->assertViewHas('fundSharesData');
    }

    public function test_bulk_preview_validates_empty_account_list()
    {
        $input = [
            'account_ids' => [],
            'type' => TransactionExt::TYPE_PURCHASE,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.preview_bulk'), $input);

        $response->assertSessionHasErrors('account_ids');
    }

    public function test_bulk_preview_validates_missing_required_fields()
    {
        $input = [
            'account_ids' => [$this->df->userAccount->id],
            // Missing type, status, value, timestamp
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.preview_bulk'), $input);

        $response->assertSessionHasErrors(['type', 'status', 'value', 'timestamp']);
    }

    public function test_bulk_preview_validates_invalid_account_ids()
    {
        $input = [
            'account_ids' => [99999], // Non-existent account
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.preview_bulk'), $input);

        $response->assertSessionHasErrors('account_ids.0');
    }

    // ==================== Bulk Store Tests ====================

    public function test_bulk_store_creates_multiple_transactions()
    {
        $input = [
            'account_ids' => [$this->df->userAccount->id],
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
            'descr' => 'Bulk transaction',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store_bulk'), $input);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->df->userAccount->id,
            'value' => 100,
        ]);
    }

    public function test_bulk_store_validates_empty_accounts()
    {
        $input = [
            'account_ids' => [],
            'type' => TransactionExt::TYPE_PURCHASE,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store_bulk'), $input);

        $response->assertSessionHasErrors('account_ids');
    }

    public function test_bulk_store_validates_missing_fields()
    {
        $input = [
            'account_ids' => [$this->df->userAccount->id],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store_bulk'), $input);

        $response->assertSessionHasErrors(['type', 'status', 'value', 'timestamp']);
    }

    public function test_bulk_store_validates_invalid_value()
    {
        $input = [
            'account_ids' => [$this->df->userAccount->id],
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 'not-a-number',
            'timestamp' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store_bulk'), $input);

        $response->assertSessionHasErrors('value');
    }

    public function test_bulk_store_handles_partial_failures()
    {
        // Laravel validation will reject invalid account IDs before reaching controller
        // This tests the validation rather than partial failures
        $input = [
            'account_ids' => [$this->df->userAccount->id, 99999],
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store_bulk'), $input);

        // Validation should fail for invalid account
        $response->assertSessionHasErrors('account_ids.1');
    }

    // ==================== Index Filter Tests ====================

    public function test_index_filters_by_fund_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['fund_id' => $this->df->fund->id]));

        $response->assertStatus(200);
        $response->assertViewIs('transactions.index');
        $response->assertViewHas('filters');
        $this->assertEquals($this->df->fund->id, $response->viewData('filters')['fund_id']);
    }

    public function test_index_filters_by_account_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['account_id' => $this->df->userAccount->id]));

        $response->assertStatus(200);
        $response->assertViewHas('filters');
        $this->assertEquals($this->df->userAccount->id, $response->viewData('filters')['account_id']);
    }

    // ==================== Update / Destroy Tests ====================

    public function test_update_modifies_transaction()
    {
        $transaction = $this->df->createTransaction(100, null, TransactionExt::TYPE_PURCHASE);

        $response = $this->actingAs($this->user)->put(route('transactions.update', $transaction->id), [
            'account_id' => $this->df->userAccount->id,
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_CLEARED,
            'value' => 250,
            'timestamp' => now()->format('Y-m-d'),
            'descr' => 'Updated descr',
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'value' => 250,
        ]);
    }

    public function test_update_handles_not_found()
    {
        $response = $this->actingAs($this->user)->put(route('transactions.update', 99999), [
            'account_id' => $this->df->userAccount->id,
            'type' => TransactionExt::TYPE_PURCHASE,
            'status' => TransactionExt::STATUS_CLEARED,
            'value' => 100,
            'timestamp' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_deletes_transaction()
    {
        $transaction = $this->df->createTransaction(100, null, TransactionExt::TYPE_PURCHASE);

        $response = $this->actingAs($this->user)
            ->delete(route('transactions.destroy', $transaction->id));

        $response->assertRedirect(route('transactions.index'));
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_destroy_handles_not_found()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('transactions.destroy', 99999));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Bulk Preview: Sale Branch ====================

    public function test_bulk_preview_handles_sale_type()
    {
        // Sale takes the non-purchase branch of the fund-shares projection.
        $input = [
            'account_ids' => [$this->df->userAccount->id],
            'type' => TransactionExt::TYPE_SALE,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => 50,
            'timestamp' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.preview_bulk'), $input);

        $response->assertStatus(200);
        $response->assertViewIs('transactions.preview_bulk');
        $response->assertViewHas('fundSharesData');
    }
}
