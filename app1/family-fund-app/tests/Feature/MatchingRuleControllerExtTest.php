<?php

namespace Tests\Feature;

use App\Mail\AccountMatchingRuleEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for MatchingRuleControllerExt
 * Target: Get coverage from 10% to 50%+
 */
class MatchingRuleControllerExtTest extends TestCase
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

        Mail::fake();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Show Tests ====================

    public function test_show_displays_matching_rule_with_accounts()
    {
        $this->df->createMatching(500, 100, '2024-01-01', '2025-12-31');

        $response = $this->actingAs($this->user)->get('/matchingRules/' . $this->df->matchingRule->id);

        $response->assertStatus(200);
        $response->assertViewHas('matchingRule');
        $response->assertViewHas('accountMatchingRules');
    }

    public function test_show_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/matchingRules/99999');

        $response->assertRedirect(route('matchingRules.index'));
    }

    // ==================== Send All Emails Tests ====================

    public function test_send_all_emails_sends_to_accounts_with_email()
    {
        $this->df->createMatching(500, 100, '2024-01-01', '2025-12-31');

        // Set email on account
        $this->df->userAccount->email_cc = 'test@example.com';
        $this->df->userAccount->save();

        $response = $this->actingAs($this->user)->get('/matchingRules/' . $this->df->matchingRule->id . '/send-all-emails');

        $response->assertRedirect();
        $response->assertSessionHas('flash_notification');

        Mail::assertSent(AccountMatchingRuleEmail::class);
    }

    public function test_send_all_emails_skips_accounts_without_email()
    {
        $this->df->createMatching(500, 100, '2024-01-01', '2025-12-31');

        // Ensure no email is set
        $this->df->userAccount->email_cc = null;
        $this->df->userAccount->save();

        $response = $this->actingAs($this->user)->get('/matchingRules/' . $this->df->matchingRule->id . '/send-all-emails');

        $response->assertRedirect();
        $response->assertSessionHas('flash_notification');

        // Email should not be sent
        Mail::assertNotSent(AccountMatchingRuleEmail::class);
    }

    public function test_send_all_emails_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/matchingRules/99999/send-all-emails');

        $response->assertRedirect(route('matchingRules.index'));
    }

    // ==================== Clone Tests ====================

    public function test_clone_displays_form_with_accounts()
    {
        $this->df->createMatching(500, 100, '2024-01-01', '2025-12-31');

        $response = $this->actingAs($this->user)->get('/matchingRules/' . $this->df->matchingRule->id . '/clone');

        $response->assertStatus(200);
        $response->assertViewHas('matchingRule');
        $response->assertViewHas('accounts');
        $response->assertViewHas('funds');
        $response->assertViewHas('assignedAccountIds');
    }

    public function test_clone_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/matchingRules/99999/clone');

        $response->assertRedirect(route('matchingRules.index'));
    }

    // ==================== Store Clone Tests ====================

    public function test_store_clone_creates_matching_rule()
    {
        $response = $this->actingAs($this->user)->post('/matchingRules/store_clone', [
            'name' => 'Cloned Rule',
            'dollar_range_start' => 0,
            'dollar_range_end' => 1000,
            'date_start' => '2024-01-01',
            'date_end' => '2025-12-31',
            'match_percent' => 50,
        ]);

        $response->assertRedirect(route('matchingRules.index'));
        $this->assertDatabaseHas('matching_rules', [
            'name' => 'Cloned Rule',
        ]);
    }

    public function test_store_clone_with_account_assignments()
    {
        $response = $this->actingAs($this->user)->post('/matchingRules/store_clone', [
            'name' => 'Cloned Rule Assigned',
            'dollar_range_start' => 0,
            'dollar_range_end' => 500,
            'date_start' => '2024-01-01',
            'date_end' => '2025-12-31',
            'match_percent' => 100,
            'account_ids' => [$this->df->userAccount->id],
        ]);

        $response->assertRedirect(route('matchingRules.index'));

        // Verify account was assigned
        $this->assertDatabaseHas('account_matching_rules', [
            'account_id' => $this->df->userAccount->id,
        ]);
    }

    // ==================== Base Controller Tests ====================

    public function test_index_displays_all_matching_rules()
    {
        $response = $this->actingAs($this->user)->get('/matchingRules');

        $response->assertStatus(200);
        $response->assertViewHas('matchingRules');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)->get('/matchingRules/create');

        $response->assertStatus(200);
    }

    public function test_store_creates_new_matching_rule()
    {
        $response = $this->actingAs($this->user)->post('/matchingRules', [
            'name' => 'Test Rule',
            'dollar_range_start' => 0,
            'dollar_range_end' => 500,
            'date_start' => '2024-01-01',
            'date_end' => '2025-12-31',
            'match_percent' => 100,
        ]);

        $response->assertRedirect(route('matchingRules.index'));
        $this->assertDatabaseHas('matching_rules', [
            'name' => 'Test Rule',
        ]);
    }

    public function test_edit_displays_form()
    {
        $this->df->createMatchingRule(500, 100, '2024-01-01', '2025-12-31');

        $response = $this->actingAs($this->user)->get('/matchingRules/' . $this->df->matchingRule->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewHas('matchingRule');
    }

    public function test_edit_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/matchingRules/99999/edit');

        $response->assertRedirect(route('matchingRules.index'));
    }

    public function test_update_modifies_matching_rule()
    {
        $this->df->createMatchingRule(500, 100, '2024-01-01', '2025-12-31');
        $newName = 'Updated Rule';

        $response = $this->actingAs($this->user)->put('/matchingRules/' . $this->df->matchingRule->id, [
            'name' => $newName,
            'dollar_range_start' => 0,
            'dollar_range_end' => 500,
            'date_start' => '2024-01-01',
            'date_end' => '2025-12-31',
            'match_percent' => 100,
        ]);

        $response->assertRedirect(route('matchingRules.index'));
        $this->assertDatabaseHas('matching_rules', [
            'id' => $this->df->matchingRule->id,
            'name' => $newName,
        ]);
    }

    public function test_update_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->put('/matchingRules/99999', [
            'name' => 'Test',
            'dollar_range_start' => 0,
            'dollar_range_end' => 500,
            'date_start' => '2024-01-01',
            'date_end' => '2025-12-31',
            'match_percent' => 100,
        ]);

        $response->assertRedirect(route('matchingRules.index'));
    }
}
