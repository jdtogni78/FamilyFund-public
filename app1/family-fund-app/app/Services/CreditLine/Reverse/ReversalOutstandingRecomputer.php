<?php

namespace App\Services\CreditLine\Reverse;

use App\Models\AccountCreditLine;
use App\Services\CreditLine\Support\OutstandingCalculator;

/**
 * @deprecated Phase 2 dedupe — delegates to OutstandingCalculator::recomputeForLine().
 *
 * Kept for backwards compatibility with wave 1e's ReverseService construction signature.
 * Both this class and the canonical calculator now produce identical results
 * (`reversed = false` filter) — the only divergence (the previous duplicate query)
 * has been removed.
 */
class ReversalOutstandingRecomputer
{
    private OutstandingCalculator $calculator;

    public function __construct(?OutstandingCalculator $calculator = null)
    {
        $this->calculator = $calculator ?? new OutstandingCalculator();
    }

    /**
     * Recompute outstanding_shares for the given line and persist it.
     */
    public function recompute(AccountCreditLine $line): float
    {
        return $this->calculator->recomputeForLine($line);
    }
}
