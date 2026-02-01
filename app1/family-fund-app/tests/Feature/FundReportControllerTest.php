<?php

namespace Tests\Feature;

use App\Models\FundReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for FundReportController
 * Target: Push from 17.6% to 50%+
 */
class FundReportControllerTest extends TestCase
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

    public function test_index_displays_fund_reports_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('fundReports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('fund_reports.index');
        $response->assertViewHas('fundReports');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('fundReports.create'));

        $response->assertStatus(200);
        $response->assertViewIs('fund_reports.create');
    }

    public function test_show_displays_fund_report()
    {
        $fundReport = FundReport::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('fundReports.show', $fundReport->id));

        $response->assertStatus(200);
        $response->assertViewIs('fund_reports.show');
        $response->assertViewHas('fundReport');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('fundReports.show', 99999));

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_edit_displays_form_for_existing_fund_report()
    {
        $fundReport = FundReport::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('fundReports.edit', $fundReport->id));

        $response->assertStatus(200);
        $response->assertViewIs('fund_reports.edit');
        $response->assertViewHas('fundReport');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('fundReports.edit', 99999));

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_fund_report()
    {
        $fundReport = FundReport::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('fundReports.destroy', $fundReport->id));

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('fundReports.destroy', 99999));

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
    }
}
