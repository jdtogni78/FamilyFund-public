<?php

namespace Tests\Feature;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\TransactionExt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Phase 9: feature tests for the credit-line payment simulator page.
 *
 * - GET /credit-lines/{line}/simulator
 * - admin-gated (non-admins get 403)
 * - results render when monthly_payment_usd is supplied
 */
class CreditLineSimulatorTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $admin;
    protected AccountCreditLine $line;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->df = new DataFactory();
        $this->df->createFund(1000, 1000, '2022-01-01');
        $this->df->createUser();
        $this->admin = $this->df->user;

        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $this->admin->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);

        $this->seedOwnBalance($this->df->userAccount, 500);

        // Open a line via the controller so we exercise the existing wiring.
        $this->actingAs($this->admin)->post(
            route('credit_lines.store', ['account' => $this->df->userAccount->id]),
            [
                'account_id'        => $this->df->userAccount->id,
                'principal_shares'  => 100,
                'term_months'       => 12,
                'payment_frequency' => 'monthly',
                'descr'             => 'Simulator feature-test line',
            ]
        );

        $this->line = AccountCreditLine::where('account_id', $this->df->userAccount->id)
            ->latest('id')
            ->first();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_admin_can_load_simulator_page(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('credit_lines.simulator', ['line' => $this->line->id])
        );

        $response->assertOk();
        $response->assertSee('Payment simulator', false);
        $response->assertSee('Monthly payment (USD)', false);
        $response->assertSee('Credit Line #' . $this->line->id, false);
    }

    public function test_simulator_returns_results_when_payment_supplied(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('credit_lines.simulator', ['line' => $this->line->id]) . '?monthly_payment_usd=50'
        );

        $response->assertOk();
        $response->assertSee('Scenario summary', false);
        $response->assertSee('Conservative', false);
        $response->assertSee('Expected', false);
        $response->assertSee('Aggressive', false);
        $response->assertSee('Cumulative-shares-paid projection', false);
    }

    public function test_non_admin_blocked(): void
    {
        // Create a second non-admin user.
        $df2 = new DataFactory();
        // Reuse the same fund.
        $df2->fund = $this->df->fund;
        $df2->createUser();
        $nonAdmin = $df2->user;

        $response = $this->actingAs($nonAdmin)->get(
            route('credit_lines.simulator', ['line' => $this->line->id])
        );

        // Admin gate aborts with 403.
        $this->assertTrue(
            $response->status() === 403 || $response->isRedirect(),
            'Expected 403 or redirect; got ' . $response->status()
        );
    }

    private function seedOwnBalance($account, float $shares): void
    {
        $tran = $this->df->createTransaction(
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
