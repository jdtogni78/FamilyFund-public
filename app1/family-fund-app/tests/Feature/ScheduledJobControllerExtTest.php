<?php

namespace Tests\Feature;

use App\Models\FundReport;
use App\Models\Schedule;
use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for ScheduledJobControllerExt
 * Target: Get coverage from 24% to 50%+
 */
class ScheduledJobControllerExtTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $user;
    protected Schedule $schedule;
    protected FundReport $fundReportTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();

        $this->user = $this->df->user;

        // Get or create a schedule (use existing or create basic one)
        $this->schedule = Schedule::first();
        if (!$this->schedule) {
            $this->schedule = Schedule::factory()->create();
        }

        // Create a fund report as template using factory defaults
        $this->fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Index Tests ====================

    public function test_index_displays_scheduled_jobs()
    {
        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $this->fundReportTemplate->id
        );

        $response = $this->actingAs($this->user)->get(route('scheduledJobs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.index');
        $response->assertViewHas('scheduledJobs');
    }

    public function test_index_orders_by_start_date_descending()
    {
        $job1 = $this->df->createScheduledJob($this->schedule, ScheduledJobExt::ENTITY_FUND_REPORT, $this->fundReportTemplate->id);
        $job1->start_dt = '2023-01-01';
        $job1->save();

        $job2 = $this->df->createScheduledJob($this->schedule, ScheduledJobExt::ENTITY_FUND_REPORT, $this->fundReportTemplate->id);
        $job2->start_dt = '2023-12-01';
        $job2->save();

        $response = $this->actingAs($this->user)->get(route('scheduledJobs.index'));

        $response->assertStatus(200);
        $scheduledJobs = $response->viewData('scheduledJobs');
        // Most recent should be first (descending order)
        $this->assertGreaterThanOrEqual(
            $scheduledJobs->skip(1)->first()->start_dt->format('Y-m-d'),
            $scheduledJobs->first()->start_dt->format('Y-m-d'),
            'Scheduled jobs should be ordered by start_dt descending'
        );
    }

    // ==================== Create Tests ====================

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)->get(route('scheduledJobs.create'));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.create');
        $response->assertViewHas('schedules');
        $response->assertViewHas('fundReportTemplates');
        $response->assertViewHas('transactionTemplates');
        $response->assertViewHas('entityTypes');
    }

    // ==================== Show Tests ====================

    public function test_show_displays_scheduled_job()
    {
        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $this->fundReportTemplate->id
        );

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.show', $scheduledJob->id));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.show');
        $response->assertViewHas('scheduledJob');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.show', 99999));

        $response->assertRedirect(route('scheduledJobs.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form()
    {
        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $this->fundReportTemplate->id
        );

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.edit', $scheduledJob->id));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.edit');
        $response->assertViewHas('scheduledJob');
        $response->assertViewHas('schedules');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.edit', 99999));

        $response->assertRedirect(route('scheduledJobs.index'));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Preview Tests ====================

    public function test_preview_scheduled_job_displays_preview()
    {
        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $this->fundReportTemplate->id
        );

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.preview', [$scheduledJob->id, $asOf]));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.preview');
        $response->assertViewHas('scheduledJob');
        $response->assertViewHas('asOf');
    }

    public function test_preview_redirects_for_invalid_job()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.preview', [99999, $asOf]));

        $response->assertRedirect();
    }

    // ==================== Run Tests ====================

    public function test_run_scheduled_job_redirects_to_show()
    {
        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $this->fundReportTemplate->id
        );

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.run', [$scheduledJob->id, $asOf]));

        $response->assertRedirect(route('scheduledJobs.show', $scheduledJob->id));
        $response->assertSessionHas('flash_notification');
    }

    public function test_run_scheduled_job_handles_invalid_id()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.run', [99999, $asOf]));

        $response->assertRedirect();
    }

    // ==================== Force Run Tests ====================

    public function test_force_run_scheduled_job_redirects_to_show()
    {
        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $this->fundReportTemplate->id
        );

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.force-run', [$scheduledJob->id, $asOf]));

        $response->assertRedirect(route('scheduledJobs.show', $scheduledJob->id));
        $response->assertSessionHas('flash_notification');
    }

    public function test_force_run_with_skip_data_check()
    {
        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $this->fundReportTemplate->id
        );

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.force-run', [$scheduledJob->id, $asOf]), [
                'skip_data_check' => true
            ]);

        $response->assertRedirect(route('scheduledJobs.show', $scheduledJob->id));
        $response->assertSessionHas('flash_notification');
    }

    public function test_force_run_handles_invalid_id()
    {
        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.force-run', [99999, $asOf]));

        $response->assertRedirect();
    }

    // ==================== Negative Tests ====================

    public function test_preview_handles_past_date()
    {
        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $this->fundReportTemplate->id
        );

        $asOf = now()->subMonths(3)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.preview', [$scheduledJob->id, $asOf]));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.preview');
    }

    public function test_run_handles_future_date()
    {
        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $this->fundReportTemplate->id
        );

        $asOf = now()->addMonths(3)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.run', [$scheduledJob->id, $asOf]));

        $response->assertRedirect(route('scheduledJobs.show', $scheduledJob->id));
        // Job may not run if not due yet
        $response->assertSessionHas('flash_notification');
    }
}
