<?php

namespace Tests\Feature;

use App\Models\FundReport;
use App\Models\Schedule;
use App\Models\ScheduledJob;
use App\Models\ScheduledJobExt;
use App\Models\ScheduleExt;
use App\Models\TradeBandReport;
use App\Models\TransactionExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Additional tests for ScheduledJobControllerExt to improve coverage
 * Target: Get coverage from 16% to 50%+
 */
class ScheduledJobControllerExtAdditionalTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $user;
    protected Schedule $schedule;

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

        // Create a schedule (use DOM = Day of Month, value 1 = 1st of month)
        $this->schedule = $this->df->createSchedule(ScheduleExt::TYPE_DAY_OF_MONTH, 1);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Index With Different Entity Types ====================

    public function test_index_shows_fund_report_scheduled_jobs()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $fundReportTemplate->id
        );

        $response = $this->actingAs($this->user)->get(route('scheduledJobs.index'));

        $response->assertStatus(200);
        $response->assertViewHas('scheduledJobs');

        $jobs = $response->viewData('scheduledJobs');
        $this->assertTrue($jobs->contains('id', $scheduledJob->id));
    }

    public function test_index_shows_transaction_scheduled_jobs()
    {
        $transactionTemplate = $this->df->createTransaction(
            100,
            $this->df->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_SCHEDULED
        );

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_TRANSACTION,
            $transactionTemplate->id
        );

        $response = $this->actingAs($this->user)->get(route('scheduledJobs.index'));

        $response->assertStatus(200);
        $response->assertViewHas('scheduledJobs');

        $jobs = $response->viewData('scheduledJobs');
        $this->assertTrue($jobs->contains('id', $scheduledJob->id));
    }

    public function test_index_shows_trade_band_report_scheduled_jobs()
    {
        $tradeBandReportTemplate = TradeBandReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_TRADE_BAND_REPORT,
            $tradeBandReportTemplate->id
        );

        $response = $this->actingAs($this->user)->get(route('scheduledJobs.index'));

        $response->assertStatus(200);
        $response->assertViewHas('scheduledJobs');

        $jobs = $response->viewData('scheduledJobs');
        $this->assertTrue($jobs->contains('id', $scheduledJob->id));
    }

    // ==================== Create Form Tests ====================

    public function test_create_form_has_all_required_data()
    {
        $response = $this->actingAs($this->user)->get(route('scheduledJobs.create'));

        $response->assertStatus(200);
        $response->assertViewHas('schedules');
        $response->assertViewHas('fundReportTemplates');
        $response->assertViewHas('tradeBandReportTemplates');
        $response->assertViewHas('transactionTemplates');
        $response->assertViewHas('entityTypes');
    }

    // ==================== Show With Different Entity Types ====================

    public function test_show_displays_fund_report_job_details()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $fundReportTemplate->id
        );

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.show', $scheduledJob->id));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.show');
        $response->assertViewHas('scheduledJob');

        $viewJob = $response->viewData('scheduledJob');
        $this->assertEquals($scheduledJob->id, $viewJob->id);
        $this->assertEquals(ScheduledJobExt::ENTITY_FUND_REPORT, $viewJob->entity_descr);
    }

    public function test_show_displays_transaction_job_details()
    {
        $transactionTemplate = $this->df->createTransaction(
            100,
            $this->df->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_SCHEDULED
        );

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_TRANSACTION,
            $transactionTemplate->id
        );

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.show', $scheduledJob->id));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.show');

        $viewJob = $response->viewData('scheduledJob');
        $this->assertEquals(ScheduledJobExt::ENTITY_TRANSACTION, $viewJob->entity_descr);
    }

    // ==================== Edit Tests ====================

    public function test_edit_displays_form_with_job_data()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $fundReportTemplate->id
        );

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.edit', $scheduledJob->id));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.edit');
        $response->assertViewHas('scheduledJob');
        $response->assertViewHas('schedules');
        $response->assertViewHas('fundReportTemplates');
        $response->assertViewHas('transactionTemplates');
    }

    // ==================== Preview With Different Entity Types ====================

    public function test_preview_fund_report_scheduled_job()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $fundReportTemplate->id
        );

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.preview', [$scheduledJob->id, $asOf]));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.preview');
        $response->assertViewHas('scheduledJob');
        $response->assertViewHas('data');
        $response->assertViewHas('asOf');
        $response->assertViewHas('shouldRunBy');
    }

    public function test_preview_transaction_scheduled_job()
    {
        $transactionTemplate = $this->df->createTransaction(
            100,
            $this->df->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_SCHEDULED
        );

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_TRANSACTION,
            $transactionTemplate->id
        );

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.preview', [$scheduledJob->id, $asOf]));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.preview');
        $response->assertViewHas('scheduledJob');
    }

    public function test_preview_shows_children_for_fund_report()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $fundReportTemplate->id
        );

        // Create some child fund reports
        FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
            'scheduled_job_id' => $scheduledJob->id,
        ]);

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.preview', [$scheduledJob->id, $asOf]));

        $response->assertStatus(200);
        $response->assertViewHas('children');
    }

    // ==================== Run Tests ====================

    public function test_run_fund_report_job()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $fundReportTemplate->id
        );

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.run', [$scheduledJob->id, $asOf]));

        $response->assertRedirect(route('scheduledJobs.show', $scheduledJob->id));
        $response->assertSessionHas('flash_notification');
    }

    public function test_run_transaction_job()
    {
        $transactionTemplate = $this->df->createTransaction(
            100,
            $this->df->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_SCHEDULED
        );

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_TRANSACTION,
            $transactionTemplate->id
        );

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.run', [$scheduledJob->id, $asOf]));

        $response->assertRedirect(route('scheduledJobs.show', $scheduledJob->id));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Force Run Tests ====================

    public function test_force_run_fund_report_job()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $fundReportTemplate->id
        );

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.force-run', [$scheduledJob->id, $asOf]));

        $response->assertRedirect(route('scheduledJobs.show', $scheduledJob->id));
        $response->assertSessionHas('flash_notification');
    }

    public function test_force_run_with_skip_data_check_flag()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $fundReportTemplate->id
        );

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.force-run', [$scheduledJob->id, $asOf]), [
                'skip_data_check' => '1'
            ]);

        $response->assertRedirect(route('scheduledJobs.show', $scheduledJob->id));
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Error Handling Tests ====================

    public function test_preview_handles_exception_gracefully()
    {
        // Create a scheduled job with invalid entity id
        $scheduledJob = ScheduledJob::factory()->create([
            'schedule_id' => $this->schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_FUND_REPORT,
            'entity_id' => 99999,  // Invalid ID
            'start_dt' => now()->subYear(),
            'end_dt' => '9999-12-31',
        ]);

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.preview', [$scheduledJob->id, $asOf]));

        // Should still render the preview page (possibly with errors)
        $response->assertStatus(200);
    }

    public function test_run_handles_job_not_due()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        // Create job that starts in the future
        $scheduledJob = ScheduledJob::factory()->create([
            'schedule_id' => $this->schedule->id,
            'entity_descr' => ScheduledJobExt::ENTITY_FUND_REPORT,
            'entity_id' => $fundReportTemplate->id,
            'start_dt' => now()->addYear(),
            'end_dt' => '9999-12-31',
        ]);

        $asOf = now()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.run', [$scheduledJob->id, $asOf]));

        $response->assertRedirect(route('scheduledJobs.show', $scheduledJob->id));
        // Should show warning about job not due
        $response->assertSessionHas('flash_notification');
    }

    // ==================== Store and Update Tests ====================

    public function test_store_creates_new_scheduled_job()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('scheduledJobs.store'), [
                'schedule_id' => $this->schedule->id,
                'entity_descr' => ScheduledJobExt::ENTITY_FUND_REPORT,
                'entity_id' => $fundReportTemplate->id,
                'start_dt' => now()->format('Y-m-d'),
                'end_dt' => '9999-12-31',
            ]);

        $response->assertRedirect(route('scheduledJobs.index'));
    }

    public function test_update_modifies_scheduled_job()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $fundReportTemplate->id
        );

        $newEndDate = now()->addYears(2)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->put(route('scheduledJobs.update', $scheduledJob->id), [
                'schedule_id' => $this->schedule->id,
                'entity_descr' => ScheduledJobExt::ENTITY_FUND_REPORT,
                'entity_id' => $fundReportTemplate->id,
                'start_dt' => $scheduledJob->start_dt->format('Y-m-d'),
                'end_dt' => $newEndDate,
            ]);

        $response->assertRedirect(route('scheduledJobs.index'));
    }

    public function test_destroy_deletes_scheduled_job()
    {
        $fundReportTemplate = FundReport::factory()->create([
            'fund_id' => $this->df->fund->id,
        ]);

        $scheduledJob = $this->df->createScheduledJob(
            $this->schedule,
            ScheduledJobExt::ENTITY_FUND_REPORT,
            $fundReportTemplate->id
        );

        $response = $this->actingAs($this->user)
            ->delete(route('scheduledJobs.destroy', $scheduledJob->id));

        $response->assertRedirect(route('scheduledJobs.index'));

        // ScheduledJob uses soft deletes, so check it's soft-deleted
        $this->assertSoftDeleted('scheduled_jobs', [
            'id' => $scheduledJob->id,
        ]);
    }
}
