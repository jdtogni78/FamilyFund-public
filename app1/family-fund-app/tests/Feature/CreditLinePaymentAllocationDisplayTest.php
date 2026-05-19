<?php

namespace Tests\Feature;

use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * The credit-line show page must surface ALL payments that fund a schedule
 * row, not just one paid_transaction_id. This is the user-visible payoff of
 * the allocation ledger: the credit-line-2 situation (a row co-funded by a
 * short payment + a later overflow) now shows both transactions.
 */
class CreditLinePaymentAllocationDisplayTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $scenario;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->scenario = CreditLineScenarioBuilder::make();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_show_page_lists_every_payment_funding_a_row(): void
    {
        $this->scenario->withBorrowingPower('main', 500);
        // 90 over 9 monthly => 10 shares/row.
        $line = $this->scenario->openLine(
            'main', 90, 9, 'monthly',
            Carbon::today()->subMonths(3),
            'alloc-display'
        );

        // Row 0 fully paid by its own tx.
        $this->scenario->repayRow($line, 0);

        // Row 1 gets a short payment (4 of 10) -> partial.
        $shortTran = $this->scenario->repayRow($line->refresh(), 1, 4.0);

        // Row 2 gets 16: covers row 2 (10) and overflows 6 back onto the
        // oldest open row (row 1), finishing it. Row 1 is now co-funded.
        $overflowTran = $this->scenario->repayRow($line->refresh(), 2, 16.0);

        $row1 = $this->scenario->rows($line->refresh())->values()->get(1);
        $this->assertSame(CreditLinePayment::STATUS_PAID, $row1->status);
        $this->assertCount(2, $row1->allocations, 'Row 1 should be funded by two transactions.');

        $response = $this->actingAs($this->scenario->admin)
            ->get(route('credit_lines.show', ['line' => $line->id]));

        $response->assertOk();
        // Both funding transactions are listed in the Payments column.
        $response->assertSee('#' . $shortTran->id);
        $response->assertSee('#' . $overflowTran->id);
        $response->assertSee('Payments');
    }
}
