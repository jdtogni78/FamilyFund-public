<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for AccountControllerExt
 * Target: Get coverage from 3% to 50%+
 */
class AccountControllerExtTest extends TestCase
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
        // Create a transaction so the account has data to display
        $this->df->createTransaction(100);
        $this->user = $this->df->user;

        // Give user admin access
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->user->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Show Tests ====================

    public function test_show_displays_account_details()
    {
        $response = $this->actingAs($this->user)->get('/accounts/' . $this->df->userAccount->id);

        $response->assertStatus(200);
        $response->assertViewHas('account');
        $response->assertViewHas('api');
    }

    public function test_show_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/accounts/99999');

        $response->assertRedirect(route('accounts.index'));
    }

    // ==================== ShowAsOf Tests ====================

    public function test_show_as_of_displays_account_at_specific_date()
    {
        $asOf = now()->subMonths(1)->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/accounts/' . $this->df->userAccount->id . '/as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertViewHas('account');
        $response->assertViewHas('api');
    }

    public function test_show_as_of_redirects_when_not_found()
    {
        $asOf = now()->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/accounts/99999/as_of/' . $asOf);

        $response->assertRedirect(route('accounts.index'));
    }

    // ==================== PDF Tests ====================

    // Note: PDF generation requires wkhtmltopdf service.
    // Test verifies route responds without 500 error.
    public function test_show_pdf_as_of_redirects_when_not_found()
    {
        $asOf = now()->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/accounts/99999/pdf_as_of/' . $asOf);

        $response->assertRedirect(route('accounts.index'));
    }

    // ==================== Base Controller Tests ====================

    public function test_index_displays_all_accounts()
    {
        $response = $this->actingAs($this->user)->get('/accounts');

        $response->assertStatus(200);
        $response->assertViewHas('accounts');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)->get('/accounts/create');

        $response->assertStatus(200);
    }

    public function test_edit_displays_form()
    {
        $response = $this->actingAs($this->user)->get('/accounts/' . $this->df->userAccount->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewHas('account');
    }

    public function test_edit_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/accounts/99999/edit');

        $response->assertRedirect(route('accounts.index'));
    }

    public function test_update_modifies_account()
    {
        $newNickname = 'Updated';

        $response = $this->actingAs($this->user)->put('/accounts/' . $this->df->userAccount->id, [
            'code' => $this->df->userAccount->code,
            'nickname' => $newNickname,
            'fund_id' => $this->df->fund->id,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'id' => $this->df->userAccount->id,
            'nickname' => $newNickname,
        ]);
    }

    public function test_update_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->put('/accounts/99999', [
            'code' => 'TEST',
            'nickname' => 'Test',
            'fund_id' => $this->df->fund->id,
        ]);

        $response->assertRedirect(route('accounts.index'));
    }
}
