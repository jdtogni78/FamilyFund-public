<?php

namespace App\Services\CreditLine\Matching;

use App\Models\TransactionExt;

/**
 * Value object returned by CreditLineMatcher::match().
 *
 * @property-read string   $status          One of TransactionExt::MATCH_STATUS_*
 * @property-read int|null $creditLineId    Set on auto_matched / manual; NULL otherwise.
 * @property-read int[]    $candidateLineIds All candidate line IDs found (useful for ambiguous).
 * @property-read string   $reason          Human-readable explanation (for debugging / logging).
 */
class MatchResult
{
    public readonly string $status;
    public readonly ?int   $creditLineId;
    /** @var int[] */
    public readonly array  $candidateLineIds;
    public readonly string $reason;
    public readonly bool   $isNoOp;

    public function __construct(
        string $status,
        ?int   $creditLineId,
        array  $candidateLineIds,
        string $reason,
        bool   $isNoOp = false,
    ) {
        $this->status           = $status;
        $this->creditLineId     = $creditLineId;
        $this->candidateLineIds = $candidateLineIds;
        $this->reason           = $reason;
        $this->isNoOp           = $isNoOp;
    }

    // ── Factories ──────────────────────────────────────────────────────────

    public static function autoMatched(int $lineId, string $reason = ''): self
    {
        return new self(TransactionExt::MATCH_STATUS_AUTO_MATCHED, $lineId, [$lineId], $reason);
    }

    public static function ambiguous(array $candidateLineIds, string $reason = ''): self
    {
        return new self(TransactionExt::MATCH_STATUS_AMBIGUOUS, null, $candidateLineIds, $reason);
    }

    public static function unmatched(string $reason = ''): self
    {
        return new self(TransactionExt::MATCH_STATUS_UNMATCHED, null, [], $reason);
    }

    /**
     * Used when the matcher should not touch the transaction — FK already set
     * (DrawService / manual resolution) or wrong type. The classifier must
     * treat this as "preserve the row's existing status"; persisting the
     * placeholder status here would clobber a MANUAL/UNMATCHED row that the
     * observer just re-traversed.
     */
    public static function noOp(int $lineId, string $reason = 'already assigned'): self
    {
        return new self(TransactionExt::MATCH_STATUS_AUTO_MATCHED, $lineId, [$lineId], $reason, isNoOp: true);
    }
}
