<?php

namespace Tests\Feature;

use App\Models\ChangeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for ChangeLogController
 * Target: Push from 15.2% to 50%+
 */
class ChangeLogControllerTest extends TestCase
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

    public function test_index_displays_change_logs_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('changeLogs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('change_logs.index');
        $response->assertViewHas('changeLogs');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('changeLogs.create'));

        $response->assertStatus(200);
        $response->assertViewIs('change_logs.create');
    }

    public function test_show_displays_change_log()
    {
        $changeLog = ChangeLog::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('changeLogs.show', $changeLog->id));

        $response->assertStatus(200);
        $response->assertViewIs('change_logs.show');
        $response->assertViewHas('changeLog');
    }

    public function test_show_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('changeLogs.show', 99999));

        $response->assertRedirect(route('changeLogs.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_edit_displays_form_for_existing_change_log()
    {
        $changeLog = ChangeLog::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('changeLogs.edit', $changeLog->id));

        $response->assertStatus(200);
        $response->assertViewIs('change_logs.edit');
        $response->assertViewHas('changeLog');
    }

    public function test_edit_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->get(route('changeLogs.edit', 99999));

        $response->assertRedirect(route('changeLogs.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_handles_change_log()
    {
        $changeLog = ChangeLog::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('changeLogs.destroy', $changeLog->id));

        $response->assertRedirect(route('changeLogs.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_destroy_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->user)
            ->delete(route('changeLogs.destroy', 99999));

        $response->assertRedirect(route('changeLogs.index'));
        $response->assertSessionHas('flash_notification');
    }
}
