<?php

namespace Tests\Feature;

use App\Models\OperationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for EmailController
 * Target: Get coverage from 3% to 50%+
 */
class EmailControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund();
        $this->df->createUser();

        // Find existing admin user (user ID 1 or specific admin email)
        $userOne = User::find(1);
        if ($userOne) {
            $this->adminUser = $userOne;
        } else {
            $existingAdmin = User::where('email', 'admin@dev.familyfund.local')->first();
            if ($existingAdmin) {
                $this->adminUser = $existingAdmin;
            } else {
                // Create an admin user
                $this->adminUser = User::factory()->create(['email' => 'admin-email-test-' . uniqid() . '@example.com']);
            }
        }

        // Create regular user (not admin)
        $this->regularUser = $this->df->user;

        Mail::fake();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Index Tests ====================

    public function test_index_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->get('/emails');

        $response->assertRedirect('/');
        $response->assertSessionHas('flash_notification');
    }

    public function test_index_displays_for_admin()
    {
        $response = $this->actingAs($this->adminUser)->get('/emails');

        $response->assertStatus(200);
        $response->assertViewHas('emailConfig');
        $response->assertViewHas('emailLogs');
    }

    public function test_index_handles_search_parameter()
    {
        $response = $this->actingAs($this->adminUser)->get('/emails?search=test');

        $response->assertStatus(200);
        $response->assertViewHas('emailSearch', 'test');
    }

    public function test_index_handles_date_filter()
    {
        $response = $this->actingAs($this->adminUser)->get('/emails?date_from=2024-01-01&date_to=2024-12-31');

        $response->assertStatus(200);
        $response->assertViewHas('emailDateFrom', '2024-01-01');
        $response->assertViewHas('emailDateTo', '2024-12-31');
    }

    public function test_index_handles_per_page_parameter()
    {
        $response = $this->actingAs($this->adminUser)->get('/emails?per_page=50');

        $response->assertStatus(200);
        $response->assertViewHas('emailPerPage', 50);
    }

    public function test_index_handles_invalid_per_page_defaults_to_20()
    {
        $response = $this->actingAs($this->adminUser)->get('/emails?per_page=999');

        $response->assertStatus(200);
        $response->assertViewHas('emailPerPage', 20);
    }

    // ==================== Show Tests ====================

    public function test_show_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->get('/emails/20240101_120000_test.json');

        $response->assertRedirect('/');
        $response->assertSessionHas('flash_notification');
    }

    public function test_show_redirects_when_file_not_found()
    {
        $response = $this->actingAs($this->adminUser)->get('/emails/20240101_120000_nonexistent.json');

        $response->assertRedirect(route('emails.index'));
    }

    public function test_show_redirects_for_invalid_filename_format()
    {
        $response = $this->actingAs($this->adminUser)->get('/emails/invalid_filename.json');

        $response->assertRedirect(route('emails.index'));
    }

    // ==================== SendTest Tests ====================

    public function test_send_test_denies_access_to_non_admin()
    {
        $response = $this->actingAs($this->regularUser)->post('/emails/send-test', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(403);
    }

    public function test_send_test_validates_email()
    {
        $response = $this->actingAs($this->adminUser)->post('/emails/send-test', [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_send_test_executes_for_admin()
    {
        $response = $this->actingAs($this->adminUser)->post('/emails/send-test', [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('emails.index'));

        // Check operation log was created
        $this->assertTrue(
            OperationLog::where('operation', OperationLog::OP_SEND_TEST_EMAIL)->exists(),
            'Operation log should be created'
        );
    }

    // ==================== Download Attachment Tests ====================

    public function test_download_attachment_denies_access_to_non_admin()
    {
        $hash = str_repeat('a', 32); // Valid MD5 hash format
        $response = $this->actingAs($this->regularUser)->get('/emails/attachment/' . $hash . '/file.pdf');

        $response->assertRedirect('/');
    }

    public function test_download_attachment_rejects_invalid_hash()
    {
        // Route may return 404 before reaching controller validation for malformed hashes
        // The controller validates MD5 format (32 hex chars) - shorter strings will be rejected
        $response = $this->actingAs($this->adminUser)->get('/emails/attachment/notvalid/file.pdf');

        // Accept either 404 (route rejection) or redirect (controller rejection)
        $this->assertTrue(
            $response->isRedirection() || $response->status() === 404,
            'Should reject invalid hash format'
        );
    }

    public function test_download_attachment_redirects_when_not_found()
    {
        $hash = str_repeat('a', 32); // Valid but non-existent hash
        $response = $this->actingAs($this->adminUser)->get('/emails/attachment/' . $hash . '/file.pdf');

        $response->assertRedirect(route('emails.index'));
    }
}
