<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\ScheduledJob;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for ScheduledJobController
 * Target: Push from 11.8% to 50%+
 */
class ScheduledJobControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->schedule = Schedule::factory()->create();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_index_displays_scheduled_jobs_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.index');
        $response->assertViewHas('scheduledJobs');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.create'));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.create');
        $response->assertViewHas('schedules');
        $response->assertViewHas('fundReportTemplates');
        $response->assertViewHas('tradeBandReportTemplates');
        $response->assertViewHas('transactionTemplates');
    }

    public function test_show_displays_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->create([
            'schedule_id' => $this->schedule->id,
            'entity_descr' => 'matching_reminder',
            'entity_id' => 0,
        ]);

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

    public function test_edit_displays_form_for_existing_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->create([
            'schedule_id' => $this->schedule->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.edit', $scheduledJob->id));

        $response->assertStatus(200);
        $response->assertViewIs('scheduled_jobs.edit');
        $response->assertViewHas('scheduledJob');
        $response->assertViewHas('schedules');
        $response->assertViewHas('fundReportTemplates');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('scheduledJobs.edit', 99999));

        $response->assertRedirect(route('scheduledJobs.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_scheduled_job()
    {
        $scheduledJob = ScheduledJob::factory()->create([
            'schedule_id' => $this->schedule->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('scheduledJobs.destroy', $scheduledJob->id));

        $response->assertRedirect(route('scheduledJobs.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('scheduledJobs.destroy', 99999));

        $response->assertRedirect(route('scheduledJobs.index'));
        $response->assertSessionHas('flash_notification');
    }
}
