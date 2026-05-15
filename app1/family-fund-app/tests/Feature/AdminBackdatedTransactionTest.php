<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\TransactionExt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * UC-46: admin can create a transaction with an arbitrary (backdated) timestamp.
 *
 * The form bypasses the regular pending-processing path so admins can record
 * historical records verbatim; the `TransactionObserver` still fires after
 * `save()`, so the detection pipeline runs over the row.
 */
class AdminBackdatedTransactionTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected User $admin;

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
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_admin_can_create_backdated_rep_transaction(): void
    {
        $account = $this->df->userAccount;

        // Form renders.
        $form = $this->actingAs($this->admin)->get(route('admin.transactions.create'));
        $form->assertOk();
        $form->assertSee('Create transaction', false);
        $form->assertSee('Timestamp', false);

        $backdate = Carbon::parse('2024-03-15 12:00:00');

        $response = $this->actingAs($this->admin)->post(
            route('admin.transactions.store'),
            [
                'account_id' => $account->id,
                'type'       => TransactionExt::TYPE_REPAY,
                'status'     => TransactionExt::STATUS_CLEARED,
                'value'      => 100,
                'shares'     => 10,
                'timestamp'  => $backdate->format('Y-m-d\TH:i:s'),
                'descr'      => 'Backfilled historical REP',
            ]
        );
        $response->assertRedirect(route('admin.transactions.create'));

        $tran = TransactionExt::where('account_id', $account->id)
            ->where('type', TransactionExt::TYPE_REPAY)
            ->latest('id')
            ->first();
        $this->assertNotNull($tran);
        $this->assertSame($backdate->toDateTimeString(), Carbon::parse($tran->timestamp)->toDateTimeString());
        $this->assertSame('Backfilled historical REP', $tran->descr);
        $this->assertSame(100.0, (float) $tran->value);
    }

    public function test_non_admin_cannot_post_to_admin_create(): void
    {
        // Fresh user without system-admin role.
        $df2 = new DataFactory();
        $df2->createFund(1000, 1000, '2022-01-01');
        $df2->createUser();
        $nonAdmin = $df2->user;

        $response = $this->actingAs($nonAdmin)->post(
            route('admin.transactions.store'),
            [
                'account_id' => $df2->userAccount->id,
                'type'       => TransactionExt::TYPE_REPAY,
                'status'     => TransactionExt::STATUS_CLEARED,
                'value'      => 100,
                'timestamp'  => '2024-03-15T12:00:00',
            ]
        );
        // FormRequest::authorize() returns false → 403.
        $this->assertSame(403, $response->status());
    }
}
