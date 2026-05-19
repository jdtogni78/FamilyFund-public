<?php

namespace Tests\Feature;

use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Models\TransactionReversal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * Regression suite for the credit-line payment *edit* flow.
 *
 * The old "Edit" control was a POST that reversed the payment the instant it
 * was clicked, then redirected to a blank register form — so abandoning the
 * form left the row permanently deregistered ("deregisters too early").
 *
 * The edit flow is now: a GET that mutates nothing and pre-fills the form,
 * and a single POST that reverses + re-registers atomically. These tests pin
 * both halves of that contract.
 */
class CreditLinePaymentEditTest extends TestCase
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

    /** An active line with a freshly registered payment on its first row. */
    private function lineWithRegisteredPayment(): array
    {
        $this->scenario->withBorrowingPower('main', 500);
        $line = $this->scenario->openLine(
            'main', 90, 9, 'monthly',
            Carbon::today()->subMonths(3),
            'edit-flow: active'
        );

        $tran = $this->scenario->repayRow($line, 0);     // settle row 0
        $row  = $this->scenario->rows($line->refresh())->values()->first();

        $this->assertSame(CreditLinePayment::STATUS_PAID, $row->status);
        $this->assertSame($tran->id, (int) $row->paid_transaction_id);

        return [$line->refresh(), $row->refresh(), $tran->refresh()];
    }

    /**
     * The core regression: merely *opening* the edit form must not touch the
     * payment. Previously this click reversed the transaction outright.
     */
    public function test_opening_edit_form_does_not_deregister_the_payment(): void
    {
        [$line, $row, $tran] = $this->lineWithRegisteredPayment();
        $reversalsBefore = TransactionReversal::where('transaction_id', $tran->id)->count();

        $response = $this->actingAs($this->scenario->admin)
            ->get(route('credit_lines.payments.edit_form', [
                'line'    => $line->id,
                'payment' => $row->id,
            ]));

        $response->assertOk();
        $response->assertSee(number_format((float) $tran->shares, 4, '.', ''));

        $row->refresh();
        $tran->refresh();
        $this->assertSame(CreditLinePayment::STATUS_PAID, $row->status,
            'opening the edit form must not reopen the schedule row');
        $this->assertSame($tran->id, (int) $row->paid_transaction_id,
            'opening the edit form must not clear paid_transaction_id');
        $this->assertFalse((bool) $tran->reversed,
            'opening the edit form must not reverse the transaction');
        $this->assertSame($reversalsBefore,
            TransactionReversal::where('transaction_id', $tran->id)->count(),
            'opening the edit form must not write a reversal audit row');
    }

    /**
     * Submitting the edit reverses the old REP and re-registers a new one in a
     * single step: the row ends up paid by a different transaction, the old
     * one is flagged reversed, and an audit row is written.
     */
    public function test_submitting_edit_reverses_and_reregisters_atomically(): void
    {
        [$line, $row, $oldTran] = $this->lineWithRegisteredPayment();
        $newShares = round((float) $oldTran->shares + 1.0, 4);

        $response = $this->actingAs($this->scenario->admin)
            ->post(route('credit_lines.payments.update', [
                'line'    => $line->id,
                'payment' => $row->id,
            ]), [
                'shares' => $newShares,
                'date'   => Carbon::today()->toDateString(),
            ]);

        $response->assertRedirect(route('credit_lines.show', ['line' => $line->id]));
        $response->assertSessionHas('flash_notification');

        $oldTran->refresh();
        $row->refresh();

        $this->assertTrue((bool) $oldTran->reversed, 'the original REP must be reversed');
        $this->assertSame(1,
            TransactionReversal::where('transaction_id', $oldTran->id)->count(),
            'the edit must leave exactly one reversal audit row');

        $this->assertSame(CreditLinePayment::STATUS_PAID, $row->status,
            'the row must be re-registered after the edit');
        $this->assertNotNull($row->paid_transaction_id);
        $this->assertNotSame($oldTran->id, (int) $row->paid_transaction_id,
            'the row must point at the new REP, not the reversed one');

        $newTran = TransactionExt::find($row->paid_transaction_id);
        $this->assertSame(TransactionExt::TYPE_REPAY, $newTran->type);
        $this->assertFalse((bool) $newTran->reversed);
        $this->assertEqualsWithDelta($newShares, (float) $newTran->shares, 0.0001);
    }

    /**
     * A rejected edit (invalid amount) must leave the original payment exactly
     * as it was — nothing reversed, nothing reopened.
     */
    public function test_rejected_edit_leaves_the_original_payment_intact(): void
    {
        [$line, $row, $tran] = $this->lineWithRegisteredPayment();

        $response = $this->actingAs($this->scenario->admin)
            ->from(route('credit_lines.payments.edit_form', [
                'line' => $line->id, 'payment' => $row->id,
            ]))
            ->post(route('credit_lines.payments.update', [
                'line'    => $line->id,
                'payment' => $row->id,
            ]), [
                'shares' => 0,   // fails RegisterCreditLinePaymentRequest (min:0.0001)
            ]);

        $response->assertSessionHasErrors('shares');

        $row->refresh();
        $tran->refresh();
        $this->assertSame(CreditLinePayment::STATUS_PAID, $row->status);
        $this->assertSame($tran->id, (int) $row->paid_transaction_id);
        $this->assertFalse((bool) $tran->reversed);
        $this->assertSame(0,
            TransactionReversal::where('transaction_id', $tran->id)->count());
    }
}
