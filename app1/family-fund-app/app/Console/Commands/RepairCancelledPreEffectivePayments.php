<?php

namespace App\Console\Commands;

use App\Services\CreditLine\Adjust\PaymentGenerationRepairer;
use Illuminate\Console\Command;

/**
 * Idempotent enforcer of the credit-line payment generation / supersession
 * invariant (see {@see PaymentGenerationRepairer} for the full contract).
 *
 * The forward-dating bug (pre-fix step 4 cancelled *every* scheduled row
 * regardless of due date, vaporising installments due before the effective
 * date) is now retired by the backfill migration, which runs this same
 * repairer once on deploy. This command remains as a manual audit / re-run
 * tool: it backfills any missing generation stamps and restores rows wrongly
 * cancelled before their effective date, without ever deleting a row or
 * double-billing a due_date.
 *
 * Dry-run by default; pass --apply to persist. A second run over consistent
 * data is a strict no-op.
 */
class RepairCancelledPreEffectivePayments extends Command
{
    protected $signature = 'credit-lines:repair-cancelled-pre-effective {--apply : Persist changes (default is dry-run)}';

    protected $description = 'Backfill payment generation stamps and restore rows wrongly cancelled before a forward-dated readjustment effective date';

    public function handle(PaymentGenerationRepairer $repairer): int
    {
        $apply = (bool) $this->option('apply');

        $result = $repairer->repair($apply);

        $restored = $result['restored'];
        $stamped  = $result['stamped'];
        $lines    = $result['linesTouched'];

        if ($restored === 0 && $stamped === 0) {
            $this->info('✓ Generation model already consistent — nothing to repair.');
            return self::SUCCESS;
        }

        $summary = sprintf(
            '%d row(s) restored, %d generation stamp(s) backfilled across %d line(s).',
            $restored,
            $stamped,
            $lines
        );

        if ($apply) {
            $this->info("✓ {$summary}");
        } else {
            $this->warn("DRY RUN — {$summary} Re-run with --apply to persist.");
        }

        return self::SUCCESS;
    }
}
