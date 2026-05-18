<?php

namespace App\Console\Commands;

use App\Models\CreditLineAdjustment;
use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off, idempotent data repair for the ReadjustService forward-dating bug.
 *
 * Before the fix, ReadjustService step-4 cancelled *every* scheduled payment
 * regardless of due date. When the new schedule was forward-dated, that wiped
 * installments due before the effective date and never regenerated them,
 * leaving a multi-month obligation void (the "gap" on the payoff-trajectory
 * chart). This restores those wrongly-cancelled rows to `scheduled`.
 *
 * Manual invocation only — intentionally NOT a migration, so it never
 * auto-runs on deploy. Dry-run by default; pass --apply to mutate.
 *
 * A row is restored iff ALL hold:
 *   - status = cancelled
 *   - the adjustment that cancelled it (first adjustment whose adjusted_at is
 *     after the row's created_at) is forward-dated relative to the row, i.e.
 *     row.due_date < adjustment.effective_date  ← the bug signature
 *   - no surviving (non-cancelled) payment on the same line shares that
 *     due_date  ← never create a duplicate obligation
 *
 * Idempotent: a second run finds the rows already `scheduled` and is a no-op.
 */
class RepairCancelledPreEffectivePayments extends Command
{
    protected $signature = 'credit-lines:repair-cancelled-pre-effective {--apply : Persist changes (default is dry-run)}';

    protected $description = 'Restore credit-line payments wrongly cancelled before a forward-dated readjustment effective date';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $lineIds = CreditLineAdjustment::query()
            ->distinct()
            ->pluck('account_credit_line_id');

        if ($lineIds->isEmpty()) {
            $this->info('No credit-line adjustments found — nothing to repair.');
            return self::SUCCESS;
        }

        $totalRestored = 0;
        $linesTouched  = 0;

        foreach ($lineIds as $lineId) {
            $adjustments = CreditLineAdjustment::where('account_credit_line_id', $lineId)
                ->orderBy('adjusted_at')
                ->get();

            $payments = CreditLinePayment::where('account_credit_line_id', $lineId)
                ->orderBy('due_date')
                ->get();

            // due_dates with a surviving (non-cancelled) obligation — restoring
            // onto one of these would double-bill the borrower.
            $coveredDueDates = $payments
                ->where('status', '!=', CreditLinePayment::STATUS_CANCELLED)
                ->map(fn ($p) => Carbon::parse($p->due_date)->toDateString())
                ->unique()
                ->flip();

            $candidates = [];
            foreach ($payments as $p) {
                if ($p->status !== CreditLinePayment::STATUS_CANCELLED || !$p->created_at) {
                    continue;
                }

                // The adjustment whose step-4 cancelled this row = the first
                // adjustment written after the row was created.
                $cancelledBy = $adjustments->first(
                    fn ($a) => $a->adjusted_at && $a->adjusted_at->gt($p->created_at)
                );
                if (!$cancelledBy) {
                    continue;
                }

                $due       = Carbon::parse($p->due_date);
                $effective = Carbon::parse($cancelledBy->effective_date);

                // Bug signature: row was due before the new plan took effect.
                if ($due->gte($effective)) {
                    continue;
                }

                // Don't resurrect onto a date a live row already covers.
                if ($coveredDueDates->has($due->toDateString())) {
                    continue;
                }

                $candidates[] = [
                    'id'         => $p->id,
                    'due_date'   => $due->toDateString(),
                    'shares_due' => $p->shares_due,
                    'adj_id'     => $cancelledBy->id,
                    'effective'  => $effective->toDateString(),
                ];
            }

            if (empty($candidates)) {
                continue;
            }

            $linesTouched++;
            $this->line('');
            $this->info("Credit line #{$lineId}: " . count($candidates) . ' row(s) to restore');
            $this->table(
                ['payment_id', 'due_date', 'shares_due', 'cancelled_by_adj', 'adj_effective_date'],
                array_map(fn ($c) => [
                    $c['id'], $c['due_date'], $c['shares_due'], $c['adj_id'], $c['effective'],
                ], $candidates)
            );

            if ($apply) {
                DB::transaction(function () use ($candidates) {
                    CreditLinePayment::whereIn('id', array_column($candidates, 'id'))
                        ->update(['status' => CreditLinePayment::STATUS_SCHEDULED]);
                });
            }

            $totalRestored += count($candidates);
        }

        $this->line('');
        if ($totalRestored === 0) {
            $this->info('✓ No wrongly-cancelled pre-effective payments found.');
            return self::SUCCESS;
        }

        if ($apply) {
            $this->info("✓ Restored {$totalRestored} payment row(s) to scheduled across {$linesTouched} line(s).");
        } else {
            $this->warn("DRY RUN — {$totalRestored} row(s) across {$linesTouched} line(s) would be restored. Re-run with --apply to persist.");
        }

        return self::SUCCESS;
    }
}
