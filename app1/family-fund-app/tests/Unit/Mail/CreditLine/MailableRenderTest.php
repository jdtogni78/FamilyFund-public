<?php

namespace Tests\Unit\Mail\CreditLine;

use App\Mail\CreditLine\DelayNotificationMail;
use App\Mail\CreditLine\MismatchAlertMail;
use App\Mail\CreditLine\ReminderMail;
use App\Mail\CreditLine\StatusUpdateMail;
use App\Mail\CreditLine\TransactionDetectedMail;
use App\Mail\CreditLine\TransactionReceivedMail;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\AccountExt;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Services\CreditLine\Reporting\LoansSummaryBuilder;
use App\Services\CreditLine\Reporting\TrajectoryBuilder;
use App\Services\Detection\DetectionResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Smoke-tests: assert each mailable renders without throwing.
 */
class MailableRenderTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    private function makeBorTransaction(): TransactionExt
    {
        $account = $this->factory->userAccount;
        $account->email_cc = 'test@example.com';
        $account->save();

        $tran = $this->factory->createTransaction(
            100,
            $account,
            TransactionExt::TYPE_BORROW,
            TransactionExt::STATUS_PENDING
        );
        return TransactionExt::find($tran->id);
    }

    private function makeActiveLine(): AccountCreditLine
    {
        return AccountCreditLine::create([
            'account_id'         => $this->factory->userAccount->id,
            'principal_shares'   => 10,
            'outstanding_shares' => 8,
            'term_months'        => 12,
            'origination_date'   => Carbon::today()->subMonths(1)->toDateString(),
            'maturity_date'      => Carbon::today()->addMonths(11)->toDateString(),
            'payment_frequency'  => AccountCreditLineExt::FREQUENCY_MONTHLY,
            'status'             => AccountCreditLineExt::STATUS_ACTIVE,
        ]);
    }

    private function makePayment(AccountCreditLine $line): CreditLinePayment
    {
        return CreditLinePayment::create([
            'account_credit_line_id' => $line->id,
            'due_date'               => Carbon::today()->addDays(7)->toDateString(),
            'shares_due'             => 1,
            'status'                 => CreditLinePayment::STATUS_SCHEDULED,
        ]);
    }

    public function test_transaction_received_mail_renders(): void
    {
        $tran   = $this->makeBorTransaction();
        $result = DetectionResult::autoMatched(1);
        $mail   = new TransactionReceivedMail($tran, $result);

        $rendered = $mail->build();
        $this->assertNotNull($rendered);
        $this->assertStringContainsString('Borrow', $mail->subject);
    }

    public function test_transaction_detected_mail_renders(): void
    {
        $tran   = $this->makeBorTransaction();
        $result = DetectionResult::autoMatched(1);
        $mail   = new TransactionDetectedMail($tran, $result);

        $rendered = $mail->build();
        $this->assertNotNull($rendered);
        $this->assertStringContainsString('System', $mail->subject);
    }

    public function test_mismatch_alert_mail_renders(): void
    {
        $tran   = $this->makeBorTransaction();
        $result = DetectionResult::ambiguous('two lines match');
        $mail   = new MismatchAlertMail($tran, $result);

        $rendered = $mail->build();
        $this->assertNotNull($rendered);
        $this->assertStringContainsString('review', strtolower($mail->subject));
        $this->assertStringContainsString((string) $tran->id, $mail->resolveUrl);
    }

    public function test_reminder_mail_renders(): void
    {
        $line    = $this->makeActiveLine();
        $payment = $this->makePayment($line);
        $mail    = new ReminderMail($line, $payment, 7);

        $rendered = $mail->build();
        $this->assertNotNull($rendered);
        $this->assertStringContainsString('7', $mail->subject);
    }

    public function test_delay_notification_mail_renders(): void
    {
        $line    = $this->makeActiveLine();
        $payment = $this->makePayment($line);
        $mail    = new DelayNotificationMail($line, $payment, 1);

        $rendered = $mail->build();
        $this->assertNotNull($rendered);
        $this->assertSame(1, $mail->notificationCount);
    }

    public function test_status_update_mail_renders(): void
    {
        $line    = $this->makeActiveLine();
        $this->makePayment($line);
        $account = AccountExt::find($this->factory->userAccount->id);

        $summary = (new LoansSummaryBuilder())->forAccount($account);
        $lines   = [['line' => $line, 'trajectory' => (new TrajectoryBuilder())->build($line)]];
        $mail    = new StatusUpdateMail($account, $summary, $lines, Carbon::today()->toDateString());

        $rendered = $mail->build();
        $this->assertNotNull($rendered);
        $this->assertStringContainsString('status update', strtolower($mail->subject));
    }
}
