<?php

namespace Tests\Feature;

use App\Models\AccountReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for AccountReportController
 * Target: Push from 30.3% to 50%+
 */
class AccountReportControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_index_displays_account_reports_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountReports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('account_reports.index');
        $response->assertViewHas('accountReports');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountReports.create'));

        $response->assertStatus(200);
        $response->assertViewIs('account_reports.create');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountReports.show', 99999));

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('accountReports.edit', 99999));

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('accountReports.destroy', 99999));

        $response->assertRedirect(route('accountReports.index'));
        $response->assertSessionHas('flash_notification');
    }
}
