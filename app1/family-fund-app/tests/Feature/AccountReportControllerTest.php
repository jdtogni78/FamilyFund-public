<?php

namespace Tests\Feature;

use App\Models\AccountReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for AccountReportController
 * Target: Push from 39% to 70%+
 */
class AccountReportControllerTest extends TestCase
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

        // Fake the queue to prevent actual report sending
        Queue::fake();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Index Tests ====================

    public function test_index_displays_account_reports_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountReports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('account_reports.index');
        $response->assertViewHas('accountReports');
    }

    public function test_index_with_fund_filter()
    {
        AccountReport::factory()->create([
            'account_id' => $this->df->userAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountReports.index', ['fund_id' => $this->df->fund->id]));

        $response->assertStatus(200);
        $response->assertViewIs('account_reports.index');
    }

    public function test_index_with_account_filter()
    {
        AccountReport::factory()->create([
            'account_id' => $this->df->userAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountReports.index', ['account_id' => $this->df->userAccount->id]));

        $response->assertStatus(200);
        $response->assertViewIs('account_reports.index');
    }

    public function test_index_with_date_filters()
    {
        AccountReport::factory()->create([
            'account_id' => $this->df->userAccount->id,
            'as_of' => '2024-06-15',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountReports.index', [
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
            ]));

        $response->assertStatus(200);
        $response->assertViewIs('account_reports.index');
    }

    public function test_index_with_all_filters()
    {
        AccountReport::factory()->create([
            'account_id' => $this->df->userAccount->id,
            'as_of' => '2024-06-15',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountReports.index', [
                'fund_id' => $this->df->fund->id,
                'account_id' => $this->df->userAccount->id,
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
            ]));

        $response->assertStatus(200);
        $response->assertViewIs('account_reports.index');
    }

    // ==================== Create Tests ====================

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountReports.create'));

        $response->assertStatus(200);
        $response->assertViewIs('account_reports.create');
        $response->assertViewHas('api');
    }

    // ==================== Store Tests ====================

    public function test_store_creates_account_report()
    {
        $response = $this->actingAs($this->user)
            ->post(route('accountReports.store'), [
                'account_id' => $this->df->userAccount->id,
                'type' => 'ALL',
                'as_of' => '2024-06-30',
            ]);

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('account_reports', [
            'account_id' => $this->df->userAccount->id,
            'type' => 'ALL',
        ]);
    }

    public function test_store_creates_template_report()
    {
        $response = $this->actingAs($this->user)
            ->post(route('accountReports.store'), [
                'account_id' => $this->df->userAccount->id,
                'type' => 'ALL',
                'as_of' => '9999-12-31', // Template date
            ]);

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('account_reports', [
            'account_id' => $this->df->userAccount->id,
            'type' => 'ALL',
            'as_of' => '9999-12-31',
        ]);
    }

    public function test_store_validates_required_account_id()
    {
        $response = $this->actingAs($this->user)
            ->post(route('accountReports.store'), [
                'type' => 'ALL',
                'as_of' => '2024-06-30',
            ]);

        $response->assertSessionHasErrors(['account_id']);
    }

    public function test_store_validates_required_type()
    {
        $response = $this->actingAs($this->user)
            ->post(route('accountReports.store'), [
                'account_id' => $this->df->userAccount->id,
                'as_of' => '2024-06-30',
            ]);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_store_validates_required_as_of()
    {
        $response = $this->actingAs($this->user)
            ->post(route('accountReports.store'), [
                'account_id' => $this->df->userAccount->id,
                'type' => 'ALL',
            ]);

        $response->assertSessionHasErrors(['as_of']);
    }

    public function test_store_validates_type_must_be_all()
    {
        $response = $this->actingAs($this->user)
            ->post(route('accountReports.store'), [
                'account_id' => $this->df->userAccount->id,
                'type' => 'INVALID',
                'as_of' => '2024-06-30',
            ]);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_store_validates_all_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->post(route('accountReports.store'), []);

        $response->assertSessionHasErrors(['account_id', 'type', 'as_of']);
    }

    // ==================== Show Tests ====================

    public function test_show_displays_account_report()
    {
        $accountReport = AccountReport::factory()->create([
            'account_id' => $this->df->userAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountReports.show', $accountReport->id));

        $response->assertStatus(200);
        $response->assertViewIs('account_reports.show');
        $response->assertViewHas('accountReport');
        $response->assertViewHas('api');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountReports.show', 99999));

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form()
    {
        $accountReport = AccountReport::factory()->create([
            'account_id' => $this->df->userAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accountReports.edit', $accountReport->id));

        $response->assertStatus(200);
        $response->assertViewIs('account_reports.edit');
        $response->assertViewHas('accountReport');
        $response->assertViewHas('api');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountReports.edit', 99999));

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Update Tests ====================

    public function test_update_modifies_account_report()
    {
        $accountReport = AccountReport::factory()->create([
            'account_id' => $this->df->userAccount->id,
            'as_of' => '2024-03-31',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('accountReports.update', $accountReport->id), [
                'account_id' => $this->df->userAccount->id,
                'type' => 'ALL',
                'as_of' => '2024-06-30',
            ]);

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');

        $this->assertDatabaseHas('account_reports', [
            'id' => $accountReport->id,
            'as_of' => '2024-06-30',
        ]);
    }

    public function test_update_modifies_template_report()
    {
        $accountReport = AccountReport::factory()->create([
            'account_id' => $this->df->userAccount->id,
            'as_of' => '2024-03-31',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('accountReports.update', $accountReport->id), [
                'account_id' => $this->df->userAccount->id,
                'type' => 'ALL',
                'as_of' => '9999-12-31', // Template date
            ]);

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_update_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->put(route('accountReports.update', 99999), [
                'account_id' => $this->df->userAccount->id,
                'type' => 'ALL',
                'as_of' => '2024-06-30',
            ]);

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_update_validates_required_fields()
    {
        $accountReport = AccountReport::factory()->create([
            'account_id' => $this->df->userAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('accountReports.update', $accountReport->id), []);

        $response->assertSessionHasErrors(['account_id', 'type', 'as_of']);
    }

    // ==================== Destroy Tests ====================

    public function test_destroy_deletes_account_report()
    {
        $accountReport = AccountReport::factory()->create([
            'account_id' => $this->df->userAccount->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('accountReports.destroy', $accountReport->id));

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');

        // Verify soft delete
        $this->assertSoftDeleted('account_reports', [
            'id' => $accountReport->id,
        ]);
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('accountReports.destroy', 99999));

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');
    }
}
