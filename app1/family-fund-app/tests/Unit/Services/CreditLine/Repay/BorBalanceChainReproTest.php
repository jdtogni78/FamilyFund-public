<?php

namespace Tests\Unit\Services\CreditLine\Repay;

use App\Models\AccountBalance;
use App\Models\Asset;
use App\Models\TransactionExt;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Reproduce the user's live-data scenario (account 7, line "Buy Home"):
 *   BOR 3000 → REP 83.33 → REP 50 → REP 100
 * The user observed a BOR account_balance row of 3080 (80 shares extra)
 * for the first period. This test runs the same sequence through current
 * DrawService + RepayService and asserts the BOR balance chain is clean
 * (no mystery 80) — which would localize the live drift to stale data,
 * not current code.
 */
class BorBalanceChainReproTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bor_balance_chain_matches_principal_minus_payments(): void
    {
        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $factory = new DataFactory();
        $factory->createFund(1000, 1000, '2022-01-01');
        $factory->createUser();
        $account = $factory->userAccount;

        $this->seedOwnBalance($account, 5000.0);

        $calc = new OutstandingCalculator();
        $draw = new DrawService(new AmortizationScheduleBuilder(), $calc);
        $repay = new RepayService($calc);

        $line = $draw->open($account, 3000.0, 18, 'monthly', 'Buy Home', Carbon::today());

        $afterBor = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->whereDate('end_dt', '9999-12-31')
            ->first();
        $this->assertNotNull($afterBor, 'expected open BOR row after draw');
        $this->assertEquals(3000.0000, round((float) $afterBor->shares, 4),
            'BOR balance after draw should equal principal (3000), not 3080');

        $repay->repay($line, 83.3333, Carbon::today()->copy()->addMonth());
        $afterRep1 = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->whereDate('end_dt', '9999-12-31')
            ->first();
        $this->assertEquals(2916.6667, round((float) $afterRep1->shares, 4),
            'BOR balance after first 83.33 payment should be 2916.6667, not 2916.67 minus extra 80');

        $repay->repay($line, 50.0, Carbon::today()->copy()->addMonths(2)->addDays(14));
        $afterRep2 = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->whereDate('end_dt', '9999-12-31')
            ->first();
        $this->assertEquals(2866.6667, round((float) $afterRep2->shares, 4));

        $repay->repay($line, 100.0, Carbon::today()->copy()->addMonths(3)->addDays(9));
        $afterRep3 = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->whereDate('end_dt', '9999-12-31')
            ->first();
        $this->assertEquals(2766.6667, round((float) $afterRep3->shares, 4));

        $chain = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->orderBy('start_dt')
            ->get(['id', 'shares', 'transaction_id', 'start_dt', 'end_dt', 'previous_balance_id']);

        $summary = $chain->map(fn ($r) => [
            'ab_id'       => $r->id,
            'shares'      => (float) $r->shares,
            'tx_id'       => $r->transaction_id,
            'start'       => (string) $r->start_dt,
            'end'         => (string) $r->end_dt,
            'previous_id' => $r->previous_balance_id,
        ])->toArray();
        fwrite(STDERR, "\n--- BOR balance chain (current-code repro) ---\n"
            . json_encode($summary, JSON_PRETTY_PRINT) . "\n");
    }

    private function seedOwnBalance($account, float $shares): void
    {
        $factory = new DataFactory();
        $factory->userAccount = $account;
        $tran = (new DataFactory())->createTransaction(
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
}
