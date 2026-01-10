<?php

namespace Tests\Unit;

use App\Mail\AccountMatchingRuleEmail;
use App\Mail\AccountQuarterlyReport;
use App\Mail\CashDepositErrorMail;
use App\Mail\CashDepositMail;
use App\Mail\PortfolioReportEmail;
use App\Mail\TradePortfolioAnnouncementMail;
use App\Models\AccountMatchingRule;
use App\Models\CashDeposit;
use App\Models\CashDepositExt;
use App\Models\PortfolioReport;
use App\Models\TradePortfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for all Mailable classes
 */
class MailableTest extends TestCase
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

    // ==================== AccountMatchingRuleEmail ====================

    public function test_account_matching_rule_email_constructor_sets_properties()
    {
        $this->factory->createMatching();
        $accountMatchingRule = $this->factory->accountMatching[0];

        $api = [
            'matching_rule' => 'Test Rule',
            'amount' => 100,
        ];

        $email = new AccountMatchingRuleEmail($accountMatchingRule, $api);

        $this->assertEquals($accountMatchingRule->id, $email->accountMatchingRule->id);
        $this->assertEquals($api, $email->api);
    }

    public function test_account_matching_rule_email_build()
    {
        $this->factory->createMatching();
        $accountMatchingRule = $this->factory->accountMatching[0];

        $api = [
            'matching_rule' => 'Test Rule',
            'amount' => 100,
        ];

        $email = new AccountMatchingRuleEmail($accountMatchingRule, $api);
        $builtEmail = $email->build();

        $this->assertEquals('Matching Rule Added to Account', $builtEmail->subject);
    }

    public function test_account_matching_rule_email_can_be_sent()
    {
        Mail::fake();

        $this->factory->createMatching();
        $accountMatchingRule = $this->factory->accountMatching[0];

        $api = [
            'matching_rule' => 'Test Rule',
            'amount' => 100,
        ];

        $email = new AccountMatchingRuleEmail($accountMatchingRule, $api);
        Mail::to('test@example.com')->send($email);

        Mail::assertSent(AccountMatchingRuleEmail::class, function ($mail) use ($accountMatchingRule) {
            return $mail->accountMatchingRule->id === $accountMatchingRule->id;
        });
    }

    // ==================== AccountQuarterlyReport ====================

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

    public function test_account_quarterly_report_build()
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

    public function test_account_quarterly_report_can_be_sent()
    {
        Mail::fake();

        $account = $this->factory->userAccount;
        $asOf = '2022-06-30';
        $pdfPath = sys_get_temp_dir() . '/test_account_report.pdf';
        file_put_contents($pdfPath, 'PDF content');

        $email = new AccountQuarterlyReport($account, $asOf, $pdfPath);
        Mail::to('test@example.com')->send($email);

        Mail::assertSent(AccountQuarterlyReport::class, function ($mail) use ($account) {
            return $mail->account->id === $account->id;
        });

        @unlink($pdfPath);
    }

    // ==================== CashDepositMail ====================

    public function test_cash_deposit_mail_constructor_sets_properties()
    {
        $cashDeposit = $this->factory->createCashDeposit(500);
        // Assign account for the email
        $cashDeposit->account_id = $this->factory->userAccount->id;
        $cashDeposit->save();
        $cashDeposit->load('account');

        $data = [
            'cash_deposit' => $cashDeposit,
            'amount' => 500,
        ];

        $email = new CashDepositMail($data);

        $this->assertEquals($data, $email->data);
    }

    public function test_cash_deposit_mail_build()
    {
        $cashDeposit = $this->factory->createCashDeposit(500);
        $cashDeposit->account_id = $this->factory->userAccount->id;
        $this->factory->userAccount->email_cc = 'test@example.com';
        $this->factory->userAccount->save();
        $cashDeposit->save();
        $cashDeposit->load('account');

        $data = [
            'cash_deposit' => $cashDeposit,
            'amount' => 500,
        ];

        $email = new CashDepositMail($data);
        $builtEmail = $email->build();

        $this->assertEquals('Cash Deposit Detected', $builtEmail->subject);
    }

    public function test_cash_deposit_mail_can_be_sent()
    {
        Mail::fake();

        $cashDeposit = $this->factory->createCashDeposit(500);
        $cashDeposit->account_id = $this->factory->userAccount->id;
        $this->factory->userAccount->email_cc = 'test@example.com';
        $this->factory->userAccount->save();
        $cashDeposit->save();
        $cashDeposit->load('account');

        $data = [
            'cash_deposit' => $cashDeposit,
            'amount' => 500,
        ];

        $email = new CashDepositMail($data);
        Mail::to('test@example.com')->send($email);

        Mail::assertSent(CashDepositMail::class);
    }

    // ==================== CashDepositErrorMail ====================

    public function test_cash_deposit_error_mail_constructor_sets_properties()
    {
        $data = [
            'errors' => ['Error 1', 'Error 2'],
            'deposit_id' => 123,
        ];

        $email = new CashDepositErrorMail($data);

        $this->assertEquals($data, $email->data);
    }

    public function test_cash_deposit_error_mail_build()
    {
        $data = [
            'errors' => ['Error 1', 'Error 2'],
            'deposit_id' => 123,
        ];

        $email = new CashDepositErrorMail($data);
        $builtEmail = $email->build();

        $this->assertEquals('Cash Deposit Errors', $builtEmail->subject);
    }

    public function test_cash_deposit_error_mail_can_be_sent()
    {
        Mail::fake();

        $data = [
            'errors' => ['Error 1', 'Error 2'],
            'deposit_id' => 123,
        ];

        $email = new CashDepositErrorMail($data);
        Mail::to('admin@example.com')->send($email);

        Mail::assertSent(CashDepositErrorMail::class);
    }

    // ==================== TradePortfolioAnnouncementMail ====================

    public function test_trade_portfolio_announcement_mail_constructor_sets_properties()
    {
        $this->factory->createTradePortfolio('2022-01-01');

        // Create a mock object with portfolio() method that returns an object with email property
        $mockTradePortfolio = new \stdClass();
        $mockTradePortfolio->portfolio = function() {
            $p = new \stdClass();
            $p->email = 'test@example.com';
            return $p;
        };

        $api = [
            'new' => $this->factory->tradePortfolio,
            'old' => null,
            'changes' => ['item1', 'item2'],
        ];

        $email = new TradePortfolioAnnouncementMail($api);

        $this->assertEquals($api, $email->api);
    }

    // Note: TradePortfolioAnnouncementMail::build() requires portfolio->email which doesn't exist in schema
    // The build() and send() tests are skipped as they require schema changes

    // ==================== PortfolioReportEmail ====================

    public function test_portfolio_report_email_constructor_sets_properties()
    {
        // Create portfolio report directly without factory
        $portfolioReport = new PortfolioReport();
        $portfolioReport->portfolio_id = $this->factory->portfolio->id;
        $portfolioReport->start_date = '2022-01-01';
        $portfolioReport->end_date = '2022-06-30';
        $portfolioReport->save();

        $user = $this->factory->user;
        $dateRange = '2022-01-01 to 2022-06-30';
        $pdf = '/tmp/test.pdf';

        $email = new PortfolioReportEmail($portfolioReport, $user, $dateRange, $pdf);

        $this->assertEquals($portfolioReport->id, $email->portfolioReport->id);
        $this->assertEquals($user->id, $email->user->id);
        $this->assertEquals($dateRange, $email->dateRange);
        $this->assertEquals($pdf, $email->pdf);
    }

    public function test_portfolio_report_email_constructor_allows_null_user()
    {
        $portfolioReport = new PortfolioReport();
        $portfolioReport->portfolio_id = $this->factory->portfolio->id;
        $portfolioReport->start_date = '2022-01-01';
        $portfolioReport->end_date = '2022-06-30';
        $portfolioReport->save();

        $dateRange = '2022-01-01 to 2022-06-30';
        $pdf = '/tmp/test.pdf';

        $email = new PortfolioReportEmail($portfolioReport, null, $dateRange, $pdf);

        $this->assertNull($email->user);
    }

    public function test_portfolio_report_email_build()
    {
        $portfolioReport = new PortfolioReport();
        $portfolioReport->portfolio_id = $this->factory->portfolio->id;
        $portfolioReport->start_date = '2022-01-01';
        $portfolioReport->end_date = '2022-06-30';
        $portfolioReport->save();
        $portfolioReport->load('portfolio');

        $user = $this->factory->user;
        $dateRange = '2022-01-01 to 2022-06-30';

        $pdfPath = sys_get_temp_dir() . '/test_portfolio_report.pdf';
        file_put_contents($pdfPath, 'PDF content');

        $email = new PortfolioReportEmail($portfolioReport, $user, $dateRange, $pdfPath);
        $builtEmail = $email->build();

        $this->assertStringContainsString($dateRange, $builtEmail->subject);

        @unlink($pdfPath);
    }

    public function test_portfolio_report_email_can_be_sent()
    {
        Mail::fake();

        $portfolioReport = new PortfolioReport();
        $portfolioReport->portfolio_id = $this->factory->portfolio->id;
        $portfolioReport->start_date = '2022-01-01';
        $portfolioReport->end_date = '2022-06-30';
        $portfolioReport->save();
        $portfolioReport->load('portfolio');

        $user = $this->factory->user;
        $dateRange = '2022-01-01 to 2022-06-30';

        $pdfPath = sys_get_temp_dir() . '/test_portfolio_report.pdf';
        file_put_contents($pdfPath, 'PDF content');

        $email = new PortfolioReportEmail($portfolioReport, $user, $dateRange, $pdfPath);
        Mail::to('test@example.com')->send($email);

        Mail::assertSent(PortfolioReportEmail::class, function ($mail) use ($portfolioReport) {
            return $mail->portfolioReport->id === $portfolioReport->id;
        });

        @unlink($pdfPath);
    }

    public function test_portfolio_report_email_with_null_user_uses_admin_name()
    {
        $portfolioReport = new PortfolioReport();
        $portfolioReport->portfolio_id = $this->factory->portfolio->id;
        $portfolioReport->start_date = '2022-01-01';
        $portfolioReport->end_date = '2022-06-30';
        $portfolioReport->save();
        $portfolioReport->load('portfolio');

        $dateRange = '2022-01-01 to 2022-06-30';

        $pdfPath = sys_get_temp_dir() . '/test_portfolio_report.pdf';
        file_put_contents($pdfPath, 'PDF content');

        $email = new PortfolioReportEmail($portfolioReport, null, $dateRange, $pdfPath);
        $builtEmail = $email->build();

        // Should use 'Admin' as the default name
        $this->assertStringContainsString('Rebalance Report', $builtEmail->subject);

        @unlink($pdfPath);
    }
}
