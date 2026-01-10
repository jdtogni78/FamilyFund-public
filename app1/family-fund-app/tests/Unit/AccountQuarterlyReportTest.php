<?php

namespace Tests\Unit;

use App\Mail\AccountQuarterlyReport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for AccountQuarterlyReport mailable
 */
class AccountQuarterlyReportTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    public function test_account_quarterly_report_constructor_sets_properties()
    {
        $account = $this->factory->userAccount;
        $asOf = '2022-06-30';
        $pdf = '/tmp/test.pdf';

        $email = new AccountQuarterlyReport($account, $asOf, $pdf);

        $this->assertEquals($account->id, $email->account->id);
        $this->assertEquals($asOf, $email->asOf);
        $this->assertEquals($pdf, $email->pdf);
    }

    public function test_account_quarterly_report_can_be_sent()
    {
        Mail::fake();

        $account = $this->factory->userAccount;
        $asOf = '2022-06-30';

        // Create a temporary PDF file
        $pdfPath = sys_get_temp_dir() . '/test_account_report.pdf';
        file_put_contents($pdfPath, 'PDF content');

        $email = new AccountQuarterlyReport($account, $asOf, $pdfPath);

        Mail::to('test@example.com')->send($email);

        Mail::assertSent(AccountQuarterlyReport::class, function ($mail) use ($account) {
            return $mail->account->id === $account->id;
        });

        @unlink($pdfPath);
    }

    public function test_account_quarterly_report_build_sets_subject()
    {
        $account = $this->factory->userAccount;
        $asOf = '2022-06-30';

        // Create a temporary PDF file
        $pdfPath = sys_get_temp_dir() . '/test_account_report.pdf';
        file_put_contents($pdfPath, 'PDF content');

        $email = new AccountQuarterlyReport($account, $asOf, $pdfPath);
        $builtEmail = $email->build();

        $this->assertStringContainsString('Account Quarterly Report', $builtEmail->subject);
        $this->assertStringContainsString($asOf, $builtEmail->subject);

        @unlink($pdfPath);
    }
}
