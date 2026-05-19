<?php

namespace Tests\Unit\Services\CreditLine;

use App\Jobs\CreditLine\SendStatusUpdateJob;
use App\Mail\CreditLine\StatusUpdateMail;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\CreditLinePayment;
use App\Services\CreditLine\Settings\LineNotificationSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

class SendStatusUpdateJobTest extends TestCase
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

    private function makeActiveLine(bool $withEmail = true): AccountCreditLine
    {
        $account = $this->factory->userAccount;
        $account->email_cc = $withEmail ? 'test@example.com' : null;
        $account->save();

        $line = AccountCreditLine::create([
            'account_id'         => $account->id,
            'principal_shares'   => 12,
            'outstanding_shares' => 9,
            'term_months'        => 12,
            'origination_date'   => Carbon::today()->subMonths(3)->toDateString(),
            'maturity_date'      => Carbon::today()->addMonths(9)->toDateString(),
            'payment_frequency'  => AccountCreditLineExt::FREQUENCY_MONTHLY,
            'status'             => AccountCreditLineExt::STATUS_ACTIVE,
        ]);

        CreditLinePayment::create([
            'account_credit_line_id' => $line->id,
            'due_date'               => Carbon::today()->addDays(7)->toDateString(),
            'shares_due'             => 1,
            'status'                 => CreditLinePayment::STATUS_SCHEDULED,
        ]);

        return $line;
    }

    public function test_sends_one_digest_per_account_with_active_line(): void
    {
        Mail::fake();
        Cache::flush();

        $this->makeActiveLine();

        (new SendStatusUpdateJob(Carbon::today()))->handle();

        Mail::assertSent(StatusUpdateMail::class, fn ($m) =>
            $m->account->id === $this->factory->userAccount->id);
    }

    public function test_skips_account_without_email(): void
    {
        Mail::fake();
        Cache::flush();

        $this->makeActiveLine(withEmail: false);

        (new SendStatusUpdateJob(Carbon::today()))->handle();

        Mail::assertNotSent(StatusUpdateMail::class, fn ($m) =>
            $m->account->id === $this->factory->userAccount->id);
    }

    public function test_does_not_double_send_within_same_quarter(): void
    {
        Mail::fake();
        Cache::flush();

        $this->makeActiveLine();
        $accountId = $this->factory->userAccount->id;

        (new SendStatusUpdateJob(Carbon::today()))->handle();
        (new SendStatusUpdateJob(Carbon::today()))->handle();

        Mail::assertSent(
            StatusUpdateMail::class,
            fn ($m) => $m->account->id === $accountId ? true : false
        );
        $count = collect(Mail::sent(StatusUpdateMail::class))
            ->filter(fn ($m) => $m->account->id === $accountId)
            ->count();
        $this->assertSame(1, $count, 'Digest must be sent at most once per quarter.');
    }

    public function test_status_update_enabled_defaults_to_true(): void
    {
        $line = $this->makeActiveLine();
        $this->assertTrue(LineNotificationSettings::for($line)->statusUpdateEnabled());
    }
}
