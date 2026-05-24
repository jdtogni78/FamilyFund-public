<?php

namespace Tests\Feature;

use App\Jobs\SendFundReport;
use App\Models\FundReportExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\TestFixtures;
use Tests\TestCase;

/**
 * Phase 2 gap-fill for FundReportControllerExt: the write/dispatch paths the
 * base FundReportControllerExtTest doesn't reach -- store (happy + the
 * no-email catch branch), update (happy + template + not-found), resend
 * (happy + template-guard + not-found), and the destroy happy path.
 *
 * The store() catch branch also pins a latent bug: the controller caught
 * `Exception` without importing it, so a thrown \Exception resolved to a
 * nonexistent WebV1\Exception and fataled instead of flashing the error.
 */
class FundReportControllerExtAdditionalTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected \Tests\DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        // Full scenario: fund + portfolio + fund/user accounts with email_cc
        // + matching + assets-with-prices + transactions.
        $this->factory = TestFixtures::fundReportFixture();

        $this->admin = User::factory()->create();
        TestFixtures::makeSystemAdmin($this->admin);

        Queue::fake();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_store_creates_report_and_queues_send()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('fundReports.store'), [
                'fund_id' => $this->factory->fund->id,
                'type' => FundReportExt::TYPE_ALL,
                'as_of' => '2024-06-30',
            ]);

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
        $this->assertDatabaseHas('fund_reports', [
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
        ]);
        Queue::assertPushed(SendFundReport::class);
    }

    public function test_store_template_does_not_queue_send()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('fundReports.store'), [
                'fund_id' => $this->factory->fund->id,
                'type' => FundReportExt::TYPE_ALL,
                'as_of' => '9999-12-31',
            ]);

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
        Queue::assertNotPushed(SendFundReport::class);
    }

    /**
     * The no-email path: validateReportEmails() throws \Exception, which the
     * controller must catch and surface as a flash error (rather than fatal).
     */
    public function test_store_with_missing_email_flashes_error_and_does_not_queue()
    {
        // Force a report recipient with no email so validateReportEmails throws.
        $this->factory->userAccount->email_cc = null;
        $this->factory->userAccount->save();

        $response = $this->actingAs($this->admin)
            ->post(route('fundReports.store'), [
                'fund_id' => $this->factory->fund->id,
                'type' => FundReportExt::TYPE_ALL,
                'as_of' => '2024-06-30',
            ]);

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
        Queue::assertNotPushed(SendFundReport::class);
    }

    public function test_update_modifies_report_and_queues_send()
    {
        $report = FundReportExt::create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
            'as_of' => '2024-03-31',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('fundReports.update', $report->id), [
                'fund_id' => $this->factory->fund->id,
                'type' => FundReportExt::TYPE_ALL,
                'as_of' => '2024-06-30',
            ]);

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
        $this->assertDatabaseHas('fund_reports', [
            'id' => $report->id,
            'as_of' => '2024-06-30',
        ]);
        Queue::assertPushed(SendFundReport::class);
    }

    public function test_update_template_does_not_queue_send()
    {
        $report = FundReportExt::create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
            'as_of' => '2024-03-31',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('fundReports.update', $report->id), [
                'fund_id' => $this->factory->fund->id,
                'type' => FundReportExt::TYPE_ALL,
                'as_of' => '9999-12-31',
            ]);

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
        Queue::assertNotPushed(SendFundReport::class);
    }

    public function test_update_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->admin)
            ->put(route('fundReports.update', 99999), [
                'fund_id' => $this->factory->fund->id,
                'type' => FundReportExt::TYPE_ALL,
                'as_of' => '2024-06-30',
            ]);

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
    }

    public function test_resend_requeues_and_redirects_to_show()
    {
        $report = FundReportExt::create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
            'as_of' => '2024-03-31',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('fundReports.resend', $report->id));

        $response->assertRedirect(route('fundReports.show', $report->id));
        $response->assertSessionHas('flash_notification');
        Queue::assertPushed(SendFundReport::class);
    }

    public function test_resend_template_is_rejected()
    {
        $report = FundReportExt::create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
            'as_of' => '9999-12-31',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('fundReports.resend', $report->id));

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
        Queue::assertNotPushed(SendFundReport::class);
    }

    public function test_resend_redirects_for_invalid_id()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('fundReports.resend', 99999));

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
        Queue::assertNotPushed(SendFundReport::class);
    }

    public function test_destroy_deletes_report()
    {
        $report = FundReportExt::create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
            'as_of' => '2024-03-31',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('fundReports.destroy', $report->id));

        $response->assertRedirect(route('fundReports.index'));
        $response->assertSessionHas('flash_notification');
        $this->assertSoftDeleted('fund_reports', ['id' => $report->id]);
    }
}
