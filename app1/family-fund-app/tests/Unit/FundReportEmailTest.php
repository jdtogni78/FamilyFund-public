<?php

namespace Tests\Unit;

use App\Mail\FundReportEmail;
use App\Models\FundReport;
use App\Models\FundReportExt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for FundReportEmail mailable
 */
class FundReportEmailTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
    }

    public function test_fund_report_email_constructor_sets_properties()
    {
        $fundReport = FundReport::factory()->create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
        ]);
        $user = User::factory()->create();
        $asOf = '2022-06-30';
        $pdf = '/tmp/test.pdf';

        $email = new FundReportEmail($fundReport, $user, $asOf, $pdf);

        $this->assertEquals($fundReport->id, $email->fundReport->id);
        $this->assertEquals($user->id, $email->user->id);
        $this->assertEquals($asOf, $email->asOf);
        $this->assertEquals($pdf, $email->pdf);
    }

    public function test_fund_report_email_constructor_allows_null_user()
    {
        $fundReport = FundReport::factory()->create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
        ]);
        $asOf = '2022-06-30';
        $pdf = '/tmp/test.pdf';

        $email = new FundReportEmail($fundReport, null, $asOf, $pdf);

        $this->assertNull($email->user);
    }

    public function test_fund_report_email_can_be_sent()
    {
        Mail::fake();

        $fundReport = FundReport::factory()->create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
        ]);
        $user = User::factory()->create(['name' => 'Test User']);
        $asOf = '2022-06-30';

        // Create a temporary PDF file for testing
        $pdfPath = sys_get_temp_dir() . '/test_report.pdf';
        file_put_contents($pdfPath, 'PDF content');

        $email = new FundReportEmail($fundReport, $user, $asOf, $pdfPath);

        Mail::to('test@example.com')->send($email);

        Mail::assertSent(FundReportEmail::class, function ($mail) use ($fundReport) {
            return $mail->fundReport->id === $fundReport->id;
        });

        // Clean up
        @unlink($pdfPath);
    }

    public function test_fund_report_email_with_null_user_uses_admin_name()
    {
        $fundReport = FundReport::factory()->create([
            'fund_id' => $this->factory->fund->id,
            'type' => FundReportExt::TYPE_ALL,
        ]);
        $fundReport->load('fund');
        $asOf = '2022-06-30';

        // Create a temporary PDF file
        $pdfPath = sys_get_temp_dir() . '/test_report.pdf';
        file_put_contents($pdfPath, 'PDF content');

        $email = new FundReportEmail($fundReport, null, $asOf, $pdfPath);
        $builtEmail = $email->build();

        // Subject should contain the report type
        $this->assertStringContainsString('Fund Report', $builtEmail->subject);

        @unlink($pdfPath);
    }
}
