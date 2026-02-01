<?php

namespace Tests\Feature;

use App\Models\TradeBandReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for TradeBandReportController
 * Target: Get coverage from 12% to 50%+
 */
class TradeBandReportControllerTest extends TestCase
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

        // Give user admin access
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->user->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);

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

    public function test_index_displays_trade_band_reports()
    {
        $response = $this->actingAs($this->user)->get('/tradeBandReports');

        $response->assertStatus(200);
        $response->assertViewHas('tradeBandReports');
    }

    // ==================== Create Tests ====================

    public function test_create_displays_form_with_api_data()
    {
        $response = $this->actingAs($this->user)->get('/tradeBandReports/create');

        $response->assertStatus(200);
        $response->assertViewHas('api');
    }

    // ==================== Store Tests ====================

    public function test_store_creates_new_trade_band_report()
    {
        $response = $this->actingAs($this->user)->post('/tradeBandReports', [
            'fund_id' => $this->df->fund->id,
            'as_of' => '2024-06-30',
        ]);

        $response->assertRedirect(route('tradeBandReports.index'));
        $this->assertDatabaseHas('trade_band_reports', [
            'fund_id' => $this->df->fund->id,
        ]);
    }

    // ==================== Show Tests ====================

    public function test_show_displays_trade_band_report()
    {
        $report = TradeBandReport::factory()->create(['fund_id' => $this->df->fund->id]);

        $response = $this->actingAs($this->user)->get('/tradeBandReports/' . $report->id);

        $response->assertStatus(200);
        $response->assertViewHas('tradeBandReport');
    }

    public function test_show_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/tradeBandReports/99999');

        $response->assertRedirect(route('tradeBandReports.index'));
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form_with_api_data()
    {
        $report = TradeBandReport::factory()->create(['fund_id' => $this->df->fund->id]);

        $response = $this->actingAs($this->user)->get('/tradeBandReports/' . $report->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewHas('tradeBandReport');
        $response->assertViewHas('api');
    }

    public function test_edit_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/tradeBandReports/99999/edit');

        $response->assertRedirect(route('tradeBandReports.index'));
    }

    // ==================== Update Tests ====================

    public function test_update_modifies_trade_band_report()
    {
        $report = TradeBandReport::factory()->create(['fund_id' => $this->df->fund->id]);
        $newDate = '2025-01-15';

        $response = $this->actingAs($this->user)->put('/tradeBandReports/' . $report->id, [
            'fund_id' => $this->df->fund->id,
            'as_of' => $newDate,
        ]);

        $response->assertRedirect(route('tradeBandReports.index'));
        $this->assertDatabaseHas('trade_band_reports', [
            'id' => $report->id,
            'as_of' => $newDate,
        ]);
    }

    public function test_update_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->put('/tradeBandReports/99999', [
            'fund_id' => $this->df->fund->id,
            'as_of' => '2025-01-15',
        ]);

        $response->assertRedirect(route('tradeBandReports.index'));
    }

    // ==================== Destroy Tests ====================

    public function test_destroy_deletes_trade_band_report()
    {
        $report = TradeBandReport::factory()->create(['fund_id' => $this->df->fund->id]);

        $response = $this->actingAs($this->user)->delete('/tradeBandReports/' . $report->id);

        $response->assertRedirect(route('tradeBandReports.index'));
        // Model uses SoftDeletes, so check for soft deletion
        $this->assertSoftDeleted('trade_band_reports', [
            'id' => $report->id,
        ]);
    }

    public function test_destroy_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->delete('/tradeBandReports/99999');

        $response->assertRedirect(route('tradeBandReports.index'));
    }

    // ==================== ViewPdf Tests ====================

    public function test_view_pdf_redirects_to_fund_pdf()
    {
        $report = TradeBandReport::factory()->create(['fund_id' => $this->df->fund->id,
            'as_of' => '2024-06-30',
        ]);

        $response = $this->actingAs($this->user)->get('/tradeBandReports/' . $report->id . '/view-pdf');

        $response->assertRedirect();
    }

    public function test_view_pdf_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/tradeBandReports/99999/view-pdf');

        $response->assertRedirect(route('tradeBandReports.index'));
    }

    // ==================== Resend Tests ====================

    public function test_resend_queues_report_email()
    {
        Queue::fake();

        $report = TradeBandReport::factory()->create(['fund_id' => $this->df->fund->id,
            'as_of' => '2024-06-30',  // Not a template (not 9999)
        ]);

        $response = $this->actingAs($this->user)->post('/tradeBandReports/' . $report->id . '/resend');

        $response->assertRedirect(route('tradeBandReports.index'));
        $response->assertSessionHas('flash_notification');

        Queue::assertPushed(\App\Jobs\SendTradeBandReport::class);
    }

    public function test_resend_fails_for_template()
    {
        Queue::fake();

        $report = TradeBandReport::factory()->create(['fund_id' => $this->df->fund->id,
            'as_of' => '9999-12-31',  // Template
        ]);

        $response = $this->actingAs($this->user)->post('/tradeBandReports/' . $report->id . '/resend');

        $response->assertRedirect(route('tradeBandReports.index'));
        // Should have error flash about template
        $response->assertSessionHas('flash_notification');

        Queue::assertNotPushed(\App\Jobs\SendTradeBandReport::class);
    }

    public function test_resend_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->post('/tradeBandReports/99999/resend');

        $response->assertRedirect(route('tradeBandReports.index'));
    }
}
