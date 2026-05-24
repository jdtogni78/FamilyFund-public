<?php

namespace Tests\Feature;

use App\Http\Controllers\Traits\AccountTrait;
use App\Mail\AccountQuarterlyReport;
use App\Models\AccountReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\Fixtures\TestFixtures;
use Tests\TestCase;

/**
 * Phase 2 gap-fill for the account-report email-send path:
 * sendAccountReport -> createAccountViewData -> new AccountPDF -> accountEmailReport.
 *
 * PDFTest builds an AccountPDF but with no trade portfolios (so
 * AccountPDF::createPortfolioComparisonGraph took its empty-portfolios early
 * return) and never mails, so accountEmailReport's send/no-send branches were
 * uncovered. We drive the whole path with Mail::fake(): the happy path with a
 * trade portfolio so the comparison graph renders, and the no-email branch.
 *
 * The fund-report email path is already covered by FundReportTest::testEmail.
 */
class AccountReportEmailSendTest extends TestCase
{
    use DatabaseTransactions;
    use AccountTrait;

    private DataFactory $factory;
    private string $asOf;

    protected function setUp(): void
    {
        parent::setUp();
        // fund + portfolio + user account with email_cc + matching
        // + assets-with-prices + transactions.
        $this->factory = TestFixtures::fundReportFixture();
        $this->asOf = Carbon::tomorrow()->format('Y-m-d');
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_send_account_report_mails_quarterly_report()
    {
        Mail::fake();

        // Trade portfolio on the fund's portfolio so the account view data
        // carries tradePortfolios -> AccountPDF::createPortfolioComparisonGraph
        // renders instead of taking its empty-portfolios early return.
        $this->factory->createTradePortfolio(Carbon::parse('2022-01-01'));

        $accountReport = AccountReport::create([
            'account_id' => $this->factory->userAccount->id,
            'type' => 'ALL',
            'as_of' => $this->asOf,
        ]);

        $this->sendAccountReport($accountReport);

        Mail::assertSent(AccountQuarterlyReport::class);
    }

    public function test_send_account_report_with_no_email_sends_nothing()
    {
        Mail::fake();

        $this->factory->userAccount->email_cc = null;
        $this->factory->userAccount->save();

        $accountReport = AccountReport::create([
            'account_id' => $this->factory->userAccount->id,
            'type' => 'ALL',
            'as_of' => $this->asOf,
        ]);

        $this->sendAccountReport($accountReport);

        Mail::assertNothingSent();
    }
}
