<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundReportExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for FundReportControllerExt
 * Target: Push from 42.9% to 50%+
 */
class FundReportControllerExtTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Fund $fund;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->fund = Fund::factory()->create();
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

    public function test_show_displays_fund_report()
    {
        // Create a simple fund report record
        $fundReport = FundReportExt::create([
            'fund_id' => $this->fund->id,
            'as_of' => now(),
            'type' => 'PDF',
            'subject' => 'Test Report',
            'text' => 'Test content',
        ]);

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

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('fundReports.create'));

        $response->assertStatus(200);
        $response->assertViewIs('fund_reports.create');
        $response->assertViewHas('api');
    }

    public function test_edit_displays_form()
    {
        // Create a simple fund report record
        $fundReport = FundReportExt::create([
            'fund_id' => $this->fund->id,
            'as_of' => now(),
            'type' => 'PDF',
            'subject' => 'Test Report',
            'text' => 'Test content',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('fundReports.edit', $fundReport->id));

        $response->assertStatus(200);
        $response->assertViewIs('fund_reports.edit');
        $response->assertViewHas('fundReport');
        $response->assertViewHas('api');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('fundReports.edit', 99999));

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
