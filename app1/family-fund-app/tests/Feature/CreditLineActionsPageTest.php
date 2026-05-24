<?php

namespace Tests\Feature;

use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * #62 — Loan Share admin-actions page layout.
 *
 * The admin-actions page (account_credit_lines/actions.blade.php) was split so
 * its three actions are distinct:
 *   - Register payment is now a *button* linking to the loan-share show page
 *     (the inline repay form was removed — payments are recorded there via the
 *     "Make payment" modal / per-row register / allocate flows).
 *   - Readjust keeps its form, in its own card.
 *   - Delete stays a button (Cancel line), behaviour unchanged.
 */
class CreditLineActionsPageTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array{0: CreditLineScenarioBuilder, 1: \App\Models\AccountCreditLine} */
    private function makeActiveLine(): array
    {
        $s = CreditLineScenarioBuilder::make();
        $s->withBorrowingPower('main', 5000);
        $line = $s->openLine('main', 120, 12, 'monthly', Carbon::today(), 'actions-page test');

        return [$s, $line];
    }

    public function test_register_payment_is_a_button_not_an_inline_form(): void
    {
        [$s, $line] = $this->makeActiveLine();

        $html = $this->actingAs($s->admin)
            ->get(route('credit_lines.actions', ['line' => $line->id]))
            ->assertOk()
            ->getContent();

        // The payment action is now a labelled button linking to the dedicated
        // create-payment page for the next open installment (earliest unpaid row).
        $this->assertStringContainsString('Register payment', $html);

        $nextOpenRow = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->whereIn('status', [
                CreditLinePayment::STATUS_SCHEDULED,
                CreditLinePayment::STATUS_PARTIAL,
                CreditLinePayment::STATUS_LATE,
            ])
            ->orderBy('due_date')
            ->first();
        $this->assertNotNull($nextOpenRow, 'fixture should have an open installment to target');
        $this->assertStringContainsString(
            route('credit_lines.payments.register_form', [
                'line' => $line->id,
                'payment' => $nextOpenRow->id,
            ]),
            $html,
            'Register payment button should link to the create-payment page for the next open row.'
        );

        // ...and the inline repay form is gone: nothing on this page should post
        // to the repay endpoint, and the old submit label is absent.
        $this->assertStringNotContainsString(
            route('credit_lines.repay', ['line' => $line->id]),
            $html,
            'The admin-actions page must not embed the repay form (payment is a button now).'
        );
        $this->assertStringNotContainsString('Record repayment', $html);

        while (ob_get_level() > 1) { @ob_end_clean(); }
    }

    public function test_readjust_form_and_delete_button_remain(): void
    {
        [$s, $line] = $this->makeActiveLine();

        $html = $this->actingAs($s->admin)
            ->get(route('credit_lines.actions', ['line' => $line->id]))
            ->assertOk()
            ->getContent();

        // Readjust keeps its own form posting to the readjust endpoint.
        $this->assertStringContainsString(
            route('credit_lines.readjust', ['line' => $line->id]),
            $html,
            'Readjust form should still be present in its own section.'
        );
        $this->assertStringContainsString('Readjust', $html);

        // Delete stays a button posting to the cancel endpoint.
        $this->assertStringContainsString(
            route('credit_lines.cancel', ['line' => $line->id]),
            $html,
            'The Cancel line (delete) button should remain.'
        );
        $this->assertStringContainsString('Cancel line', $html);

        while (ob_get_level() > 1) { @ob_end_clean(); }
    }
}
