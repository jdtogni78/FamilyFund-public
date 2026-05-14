<?php

namespace App\Services\CreditLine\Adjust;

use App\Models\AccountCreditLine;
use App\Models\CreditLineAdjustment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds a structured timeline of adjustments for a credit line (UC-40).
 *
 * Returns entries newest-first. The first entry in the list is always the
 * synthetic "origination" entry derived from the line itself; it has no
 * corresponding CreditLineAdjustment row. Subsequent entries (if any) are
 * one entry per CreditLineAdjustment row, ordered newest-first.
 *
 * Entry shape:
 *   [
 *     'date' => Carbon,
 *     'kind' => 'origination' | 'adjustment',
 *     'data' => array,       // see buildOriginationData() / buildAdjustmentData()
 *   ]
 *
 * The 'data' array for adjustments contains:
 *   - old_X/new_X field pairs for term_months, payment_frequency, maturity_date,
 *     planned_payoff_date, and outstanding_shares_at_adjustment
 *   - 'diff': list of field names that actually changed
 *   - 'adjusted_by': the adjustedBy user relation (or null for system)
 *   - 'reason': free-text if provided
 *
 * The 'data' array for the origination entry contains:
 *   - 'term_months', 'payment_frequency', 'maturity_date', 'principal_shares',
 *     'origination_date'
 */
class AdjustmentHistoryBuilder
{
    /**
     * Build the full timeline for a credit line, newest-first.
     *
     * @param  AccountCreditLine $line
     * @return array<int, array{date: Carbon, kind: string, data: array}>
     */
    public function build(AccountCreditLine $line): array
    {
        $adjustments = CreditLineAdjustment::where('account_credit_line_id', $line->id)
            ->orderByDesc('adjusted_at')
            ->get();

        $entries = [];

        // Adjustment entries, newest-first.
        foreach ($adjustments as $adj) {
            $entries[] = [
                'date' => Carbon::parse($adj->adjusted_at),
                'kind' => 'adjustment',
                'data' => $this->buildAdjustmentData($adj),
            ];
        }

        // Origination entry appended last (it is oldest, so it ends up at the end
        // of the newest-first list).
        $entries[] = [
            'date' => Carbon::parse($line->origination_date),
            'kind' => 'origination',
            'data' => $this->buildOriginationData($line),
        ];

        return $entries;
    }

    /**
     * Build the data payload for a CreditLineAdjustment entry.
     */
    private function buildAdjustmentData(CreditLineAdjustment $adj): array
    {
        $diff = [];

        if ($adj->old_term_months !== $adj->new_term_months) {
            $diff[] = 'term_months';
        }
        if ($adj->old_payment_frequency !== $adj->new_payment_frequency) {
            $diff[] = 'payment_frequency';
        }
        if ($adj->old_maturity_date != $adj->new_maturity_date) {
            $diff[] = 'maturity_date';
        }
        if ($adj->old_planned_payoff_date != $adj->new_planned_payoff_date) {
            $diff[] = 'planned_payoff_date';
        }

        return [
            'id'                               => $adj->id,
            'adjusted_at'                      => Carbon::parse($adj->adjusted_at),
            'old_term_months'                  => $adj->old_term_months,
            'new_term_months'                  => $adj->new_term_months,
            'old_payment_frequency'            => $adj->old_payment_frequency,
            'new_payment_frequency'            => $adj->new_payment_frequency,
            'old_maturity_date'                => Carbon::parse($adj->old_maturity_date),
            'new_maturity_date'                => Carbon::parse($adj->new_maturity_date),
            'old_planned_payoff_date'          => Carbon::parse($adj->old_planned_payoff_date),
            'new_planned_payoff_date'          => Carbon::parse($adj->new_planned_payoff_date),
            'outstanding_shares_at_adjustment' => $adj->outstanding_shares_at_adjustment,
            'diff'                             => $diff,
            'adjusted_by'                      => $adj->adjustedBy,
            'reason'                           => $adj->reason,
        ];
    }

    /**
     * Build the data payload for the synthetic origination entry.
     */
    private function buildOriginationData(AccountCreditLine $line): array
    {
        return [
            'term_months'       => $line->term_months,
            'payment_frequency' => $line->payment_frequency,
            'maturity_date'     => Carbon::parse($line->maturity_date),
            'principal_shares'  => $line->principal_shares,
            'origination_date'  => Carbon::parse($line->origination_date),
        ];
    }
}
