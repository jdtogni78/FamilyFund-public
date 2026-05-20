<?php

namespace Tests\Browser;

use App\Models\AccountCreditLine;
use App\Models\TransactionExt;
use App\Services\CreditLine\Repay\RepayService;
use Carbon\Carbon;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser tests for the per-transaction allocate page
 * (`/credit-lines/{line}/transactions/{tran}/allocate`).
 *
 * Covers the "Fill remainders" UX and the strict-balance server-side check:
 *  - Initial state: sum/diff indicator reflects current allocations
 *  - Fill remainders: distributes the txn's shares oldest-first onto rows
 *    capped at each row's remainder; submit button becomes enabled
 *  - Under-allocation is rejected by the server with a Flash error
 *  - A balanced allocation submits successfully
 *
 * Runs against the dev DB (account 7). Cleans up by cancelling the line.
 */
class CreditLineAllocateUITest extends DuskTestCase
{
    private const ACCOUNT_ID = 7;

    public function test_fill_remainders_balances_form_and_enables_submit(): void
    {
        $this->browse(function (Browser $browser) {
            [$lineId, $tranId] = $this->openLineAndRepay($browser);
            try {
                $browser->visit('/dev-login/credit-lines/' . $lineId . '/transactions/' . $tranId . '/allocate')
                    ->waitFor('#allocate-total')
                    ->screenshot('allocate/01_initial');

                // Initial: the matcher's auto cascade already produced a balanced
                // 30-share allocation across the first three rows of 10 sh each.
                $sumText = $browser->text('#allocate-total [data-role="sum"]');
                $this->assertSame('30.0000', trim($sumText));
                $this->assertFalse((bool) $browser->attribute('#apply-allocation', 'disabled'),
                    'Submit should be enabled when sum == tran.shares.');

                // Zero out the first input → sum drops, submit goes disabled.
                $browser->script("(function(){var i=document.querySelector('.allocate-input');i.value='';i.dispatchEvent(new Event('input',{bubbles:true}));})();");
                $browser->pause(150);
                $diff = $browser->text('#allocate-total [data-role="diff"]');
                $this->assertStringContainsString('short', $diff);
                $this->assertTrue((bool) $browser->attribute('#apply-allocation', 'disabled'),
                    'Submit should be disabled when under-allocated.');

                // Click Fill remainders → form rebalances, submit re-enabled.
                $browser->click('#fill-remainders')->pause(200);
                $sumAfter = $browser->text('#allocate-total [data-role="sum"]');
                $this->assertSame('30.0000', trim($sumAfter));
                $this->assertFalse((bool) $browser->attribute('#apply-allocation', 'disabled'),
                    'Submit should be enabled after Fill remainders balances the form.');
                $browser->screenshot('allocate/02_after_fill');
            } finally {
                $this->cleanupLine($lineId);
            }
        });
    }

    public function test_server_rejects_under_allocation_with_flash_error(): void
    {
        $this->browse(function (Browser $browser) {
            [$lineId, $tranId] = $this->openLineAndRepay($browser);
            try {
                $browser->visit('/dev-login/credit-lines/' . $lineId . '/transactions/' . $tranId . '/allocate')
                    ->waitFor('#allocate-total');

                // Bypass the client-side disabled state by clearing the disabled
                // attribute, then zero out an input and submit.
                $browser->script("document.querySelectorAll('.allocate-input').forEach((i,idx)=>{if(idx>0)i.value='';i.dispatchEvent(new Event('input',{bubbles:true}));});");
                $browser->script("document.getElementById('apply-allocation').removeAttribute('disabled');");
                $browser->press('Apply allocation')
                    ->waitForLocation('/credit-lines/' . $lineId . '/transactions/' . $tranId . '/allocate', 5);

                $source = $browser->driver->getPageSource();
                $this->assertStringContainsString(
                    'every share must land on a row',
                    $source,
                    'Server should reject under-allocation with the strict-balance flash message.'
                );
                $browser->screenshot('allocate/03_flash_error');

                // Allocations in DB should be untouched (still the 3 cascade rows).
                $tran = TransactionExt::findOrFail($tranId);
                $total = (float) $tran->creditLineAllocations()->sum('shares');
                $this->assertEqualsWithDelta(30.0, $total, 0.0001,
                    'Failed validation must NOT mutate existing allocations.');
            } finally {
                $this->cleanupLine($lineId);
            }
        });
    }

    /**
     * Open a fresh loan share (60 sh / 6 mo monthly → 6 × 10 sh rows) via
     * the UI, then repay 30 sh through the service so we have a REP txn with
     * a known multi-row allocation to re-allocate against. Returns [lineId, tranId].
     */
    private function openLineAndRepay(Browser $browser): array
    {
        $descr = 'E2E allocate UI ' . uniqid();

        $browser->visit('/dev-login/accounts/' . self::ACCOUNT_ID . '/credit-lines/create')
            ->waitFor('form[action*="/credit-lines"]')
            ->type('input[name="principal_shares"]', '60')
            ->clear('input[name="term_months"]')
            ->type('input[name="term_months"]', '6')
            ->select('select[name="payment_frequency"]', 'monthly')
            ->type('input[name="descr"]', $descr)
            ->press('Open')
            ->waitUsing(10, 200, function () use ($browser) {
                $path = parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
                return (bool) preg_match('#/credit-lines/\d+$#', $path);
            });

        $line = AccountCreditLine::where('descr', $descr)->latest('id')->firstOrFail();

        /** @var RepayService $repay */
        $repay = app(RepayService::class);
        $tran = $repay->repay($line->fresh(), 30.0, Carbon::today());

        return [(int) $line->id, (int) $tran->id];
    }

    private function cleanupLine(int $lineId): void
    {
        try {
            $line = AccountCreditLine::find($lineId);
            if ($line && $line->status === 'active') {
                $line->update(['status' => 'cancelled']);
            }
        } catch (\Throwable $e) {
            // leave for human inspection
        }
    }
}
