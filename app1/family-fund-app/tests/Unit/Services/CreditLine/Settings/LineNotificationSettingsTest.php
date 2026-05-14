<?php

namespace Tests\Unit\Services\CreditLine\Settings;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\TransactionExt;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Settings\LineNotificationSettings;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Wave-2 review (2026-05-14): exercise the `delay_notification_enabled`
 * column-backed flag (was previously hard-coded `return true;`).
 */
class LineNotificationSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $drawService;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        $this->drawService = new DrawService(new AmortizationScheduleBuilder(), new OutstandingCalculator());
    }

    private function seedOwnBalance($account, float $shares): void
    {
        $tran = $this->factory->createTransaction(
            $shares * 10,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            Carbon::today()->toDateString()
        );
        $tran->shares = $shares;
        $tran->save();

        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => Carbon::today()->toDateString(),
            'end_dt'         => '9999-12-31',
        ]);
    }

    private function newLine(): AccountCreditLine
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);
        return $this->drawService->open($account, 50.0, 6, 'monthly');
    }

    public function test_delay_notification_enabled_defaults_true(): void
    {
        $line = $this->newLine();
        $this->assertTrue(LineNotificationSettings::for($line)->delayNotificationEnabled());
    }

    public function test_delay_notification_enabled_reads_column_when_false(): void
    {
        $line = $this->newLine();
        $line->delay_notification_enabled = false;
        $line->save();

        $line->refresh();
        $this->assertFalse(LineNotificationSettings::for($line)->delayNotificationEnabled());
    }

    public function test_delay_notification_enabled_reads_column_when_true(): void
    {
        $line = $this->newLine();
        $line->delay_notification_enabled = true;
        $line->save();

        $line->refresh();
        $this->assertTrue(LineNotificationSettings::for($line)->delayNotificationEnabled());
    }
}
