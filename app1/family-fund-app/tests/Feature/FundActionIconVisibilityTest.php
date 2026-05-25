<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\Fixtures\TestFixtures;
use Tests\TestCase;

/**
 * Per-role visibility of the fund action icons.
 *
 * The funds index row icons (funds/actions.blade.php) and the "New Fund" button
 * (funds/index.blade.php) are gated so each icon only renders for users whose
 * access matches the target route's authorization — a beneficiary must not see
 * admin icons that would 403 on click. This test pins that mapping so a future
 * edit can't silently un-gate an icon.
 *
 * The fund show-page "Edit Allocations" button (funds/show_ext.blade.php) uses
 * the *same* full-access predicate (getAccessibleFundIds()['full'] keyed on the
 * fund id), so the financial-manager/fund-admin index cases below cover that
 * gate too. It isn't asserted over HTTP here because the show page prices every
 * portfolio asset and the trade-portfolio factory items are unpriced (it throws
 * "Cant find asset …"); existing tests bypass that controller path for the same
 * reason. The show-page gate was verified live across roles on the preview env.
 *
 * Gating recap:
 *   View Fund / Trade Bands      -> @can('view')         (any role in fund)
 *   Rebalance Analysis           -> full access to fund  (fund-admin/fin-mgr/sys)
 *   Edit Allocations             -> full access to fund
 *   Edit Fund                    -> @can('update')       (fund-admin/sys)
 *   Delete Fund                  -> @can('delete')       (sys only)
 *   New Fund button              -> @can('create')       (sys only)
 */
class FundActionIconVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    protected DataFactory $df;
    protected array $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        // Fund comes with one portfolio (so Rebalance Analysis can render); add an
        // active trade portfolio (end_dt=9999) so Edit Allocations can render.
        $this->df->createFund();
        $this->df->createTradePortfolio('2022-01-01');

        // One user per role, scoped to this fund (beneficiary owns an account).
        $this->users = TestFixtures::aclUsers($this->df->fund);
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_index_icons_for_beneficiary(): void
    {
        $res = $this->actingAs($this->users['beneficiary'])->get('/funds');
        $res->assertStatus(200);
        // Read-only icons it may use.
        $res->assertSee('View Fund');
        $res->assertSee('Trade Bands');
        // Full-access / admin icons it must not see.
        $res->assertDontSee('Rebalance Analysis');
        $res->assertDontSee('Edit Allocations');
        $res->assertDontSee('Edit Fund');
        $res->assertDontSee('Delete Fund');
        $res->assertDontSee('New Fund');
    }

    public function test_index_icons_for_financial_manager(): void
    {
        $res = $this->actingAs($this->users['financialManager'])->get('/funds');
        $res->assertStatus(200);
        $res->assertSee('View Fund');
        $res->assertSee('Rebalance Analysis');
        $res->assertSee('Edit Allocations');
        // financial-manager is full-access but not a fund-admin -> no update/delete/create.
        $res->assertDontSee('Edit Fund');
        $res->assertDontSee('Delete Fund');
        $res->assertDontSee('New Fund');
    }

    public function test_index_icons_for_fund_admin(): void
    {
        $res = $this->actingAs($this->users['fundAdmin'])->get('/funds');
        $res->assertStatus(200);
        $res->assertSee('Rebalance Analysis');
        $res->assertSee('Edit Allocations');
        $res->assertSee('Edit Fund');
        // delete + create are system-admin only.
        $res->assertDontSee('Delete Fund');
        $res->assertDontSee('New Fund');
    }

    public function test_index_icons_for_system_admin(): void
    {
        $res = $this->actingAs($this->users['systemAdmin'])->get('/funds');
        $res->assertStatus(200);
        $res->assertSee('Edit Fund');
        $res->assertSee('Delete Fund');
        $res->assertSee('New Fund');
    }
}
