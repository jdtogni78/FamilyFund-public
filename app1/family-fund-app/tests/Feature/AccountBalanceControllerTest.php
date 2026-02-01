<?php

namespace Tests\Feature;

use App\Models\AccountBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for AccountBalanceController
 * Target: Push from 27% to 70%+
 */
class AccountBalanceControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected DataFactory $df;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();
        $this->user = $this->df->user;
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Index Tests ====================

    public function test_index_displays_account_balances_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountBalances.index'));

        $response->assertStatus(200);
        $response->assertViewIs('account_balances.index');
        $response->assertViewHas('accountBalances');
    }

    public function test_index_with_fund_filter()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountBalances.index', ['fund_id' => $this->df->fund->id]));

        $response->assertStatus(200);
        $response->assertViewIs('account_balances.index');
    }

    public function test_index_with_account_filter()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountBalances.index', ['account_id' => $this->df->userAccount->id]));

        $response->assertStatus(200);
        $response->assertViewIs('account_balances.index');
    }

    // ==================== Create Tests ====================

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountBalances.create'));

        $response->assertStatus(200);
        $response->assertViewIs('account_balances.create');
        $response->assertViewHas('api');
    }

    // ==================== Store Tests ====================

    public function test_store_creates_account_balance()
    {
        $transaction = $this->df->createTransaction(100);

        $response = $this->actingAs($this->user)
            ->post(route('accountBalances.store'), [
                'account_id' => $this->df->userAccount->id,
                'transaction_id' => $transaction->id,
                'type' => 'OWN',
                'shares' => 10.5,
                'start_dt' => '2024-01-01',
                'end_dt' => '9999-12-31',
            ]);

        $response->assertRedirect(route('accountBalances.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('account_balances', [
            'account_id' => $this->df->userAccount->id,
            'transaction_id' => $transaction->id,
            'type' => 'OWN',
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->post(route('accountBalances.store'), []);

        $response->assertSessionHasErrors(['shares', 'transaction_id', 'start_dt', 'end_dt']);
    }

    public function test_store_validates_shares_minimum()
    {
        $transaction = $this->df->createTransaction(100);

        $response = $this->actingAs($this->user)
            ->post(route('accountBalances.store'), [
                'transaction_id' => $transaction->id,
                'shares' => 0, // Below minimum
                'start_dt' => '2024-01-01',
                'end_dt' => '9999-12-31',
            ]);

        $response->assertSessionHasErrors(['shares']);
    }

    // ==================== Show Tests ====================

    public function test_show_displays_account_balance()
    {
        $accountBalance = AccountBalance::factory()->create([
            'account_id' => $this->df->userAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountBalances.show', $accountBalance->id));

        $response->assertStatus(200);
        $response->assertViewIs('account_balances.show');
        $response->assertViewHas('accountBalance');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountBalances.show', 99999));

        $response->assertRedirect(route('accountBalances.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form()
    {
        $accountBalance = AccountBalance::factory()->create([
            'account_id' => $this->df->userAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountBalances.edit', $accountBalance->id));

        $response->assertStatus(200);
        $response->assertViewIs('account_balances.edit');
        $response->assertViewHas('accountBalance');
        $response->assertViewHas('api');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountBalances.edit', 99999));

        $response->assertRedirect(route('accountBalances.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Update Tests ====================

    public function test_update_modifies_account_balance()
    {
        $transaction = $this->df->createTransaction(100);
        $accountBalance = AccountBalance::factory()->create([
            'account_id' => $this->df->userAccount->id,
            'transaction_id' => $transaction->id,
            'shares' => 10.0,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('accountBalances.update', $accountBalance->id), [
                'account_id' => $this->df->userAccount->id,
                'transaction_id' => $transaction->id,
                'type' => 'OWN',
                'shares' => 20.0,
                'start_dt' => '2024-01-01',
                'end_dt' => '9999-12-31',
            ]);

        $response->assertRedirect(route('accountBalances.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('account_balances', [
            'id' => $accountBalance->id,
            'shares' => 20.0,
        ]);
    }

    public function test_update_redirects_for_invalid_id()
    {
        $transaction = $this->df->createTransaction(100);

        $response = $this->actingAs($this->user)
            ->put(route('accountBalances.update', 99999), [
                'transaction_id' => $transaction->id,
                'shares' => 10.0,
                'start_dt' => '2024-01-01',
                'end_dt' => '9999-12-31',
            ]);

        $response->assertRedirect(route('accountBalances.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_update_validates_required_fields()
    {
        $accountBalance = AccountBalance::factory()->create([
            'account_id' => $this->df->userAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('accountBalances.update', $accountBalance->id), []);

        $response->assertSessionHasErrors(['shares', 'transaction_id', 'start_dt', 'end_dt']);
    }

    // ==================== Destroy Tests ====================

    public function test_destroy_deletes_account_balance()
    {
        $accountBalance = AccountBalance::factory()->create([
            'account_id' => $this->df->userAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('accountBalances.destroy', $accountBalance->id));

        $response->assertRedirect(route('accountBalances.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('accountBalances.destroy', 99999));

        $response->assertRedirect(route('accountBalances.index'));
        $response->assertSessionHas('flash_notification');
    }
}
