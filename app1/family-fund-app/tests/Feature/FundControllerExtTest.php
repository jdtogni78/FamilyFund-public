<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for FundControllerExt
 * Target: Get coverage from 3% to 50%+
 */
class FundControllerExtTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    // ==================== Show Tests ====================

    public function test_show_displays_fund_details()
    {
        $response = $this->actingAs($this->user)->get('/funds/' . $this->df->fund->id);

        $response->assertStatus(200);
        $response->assertViewHas('api');
        $response->assertViewHas('asOf');
    }

    public function test_show_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/funds/99999');

        $response->assertRedirect(route('funds.index'));
    }

    // ==================== ShowAsOf Tests ====================

    public function test_show_as_of_displays_fund_at_specific_date()
    {
        $asOf = now()->subMonths(1)->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/funds/' . $this->df->fund->id . '/as_of/' . $asOf);

        $response->assertStatus(200);
        $response->assertViewHas('api');
        $response->assertViewHas('asOf');
    }

    public function test_show_as_of_redirects_when_not_found()
    {
        $asOf = now()->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/funds/99999/as_of/' . $asOf);

        $response->assertRedirect(route('funds.index'));
    }

    // ==================== Trade Bands Tests ====================

    /**
     * Note: Trade bands routes require complex data setup with assets and prices.
     * Test only verifies 404 handling works.
     */
    public function test_trade_bands_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/funds/99999/trade_bands');

        $response->assertRedirect(route('funds.index'));
    }

    public function test_trade_bands_as_of_redirects_when_not_found()
    {
        $asOf = now()->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/funds/99999/trade_bands_as_of/' . $asOf);

        $response->assertRedirect(route('funds.index'));
    }

    // ==================== PDF Tests ====================

    /**
     * Note: PDF generation requires wkhtmltopdf and chart service.
     * These tests verify the route responds without 500 error.
     */
    public function test_show_pdf_as_of_redirects_when_not_found()
    {
        $asOf = now()->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/funds/99999/pdf_as_of/' . $asOf);

        $response->assertRedirect(route('funds.index'));
    }

    public function test_trade_bands_pdf_as_of_redirects_when_not_found()
    {
        $asOf = now()->format('Y-m-d');
        $response = $this->actingAs($this->user)->get('/funds/99999/trade_bands_pdf_as_of/' . $asOf);

        $response->assertRedirect(route('funds.index'));
    }

    // ==================== Base Controller Tests ====================

    public function test_index_displays_all_funds()
    {
        $response = $this->actingAs($this->user)->get('/funds');

        $response->assertStatus(200);
        $response->assertViewHas('funds');
    }

    public function test_create_displays_form()
    {
        $response = $this->actingAs($this->user)->get('/funds/create');

        $response->assertStatus(200);
    }

    public function test_edit_displays_form()
    {
        $response = $this->actingAs($this->user)->get('/funds/' . $this->df->fund->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewHas('fund');
    }

    public function test_edit_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->get('/funds/99999/edit');

        $response->assertRedirect(route('funds.index'));
    }

    public function test_update_modifies_fund()
    {
        $newName = 'Updated Fund';  // Max 30 chars

        $response = $this->actingAs($this->user)->put('/funds/' . $this->df->fund->id, [
            'name' => $newName,
        ]);

        $response->assertRedirect(route('funds.index'));
        $this->assertDatabaseHas('funds', [
            'id' => $this->df->fund->id,
            'name' => $newName,
        ]);
    }

    public function test_update_redirects_when_not_found()
    {
        $response = $this->actingAs($this->user)->put('/funds/99999', [
            'name' => 'Test',
        ]);

        $response->assertRedirect(route('funds.index'));
    }

    public function test_store_creates_new_fund()
    {
        $fundName = 'New Test Fund';  // Max 30 chars

        $response = $this->actingAs($this->user)->post('/funds', [
            'name' => $fundName,
        ]);

        $response->assertRedirect(route('funds.index'));
        $this->assertDatabaseHas('funds', [
            'name' => $fundName,
        ]);
    }
}
