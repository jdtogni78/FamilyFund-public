<?php

namespace App\Services\CreditLine\Matching\Exceptions;

use RuntimeException;

/**
 * Thrown by MatchResolutionService when a manual resolution is not permitted.
 *
 * Reasons:
 *  - The transaction's credit_line_match_status is neither 'ambiguous' nor 'unmatched'.
 *  - The target credit line belongs to a different account than the transaction.
 */
class InvalidMatchResolutionException extends RuntimeException
{
    public static function notFlagged(int $tranId, string $status): self
    {
        return new self(sprintf(
            'Transaction %d cannot be resolved: current status is "%s". '
            . 'Only transactions with status "ambiguous" or "unmatched" may be manually resolved.',
            $tranId,
            $status,
        ));
    }

    public static function accountMismatch(int $tranId, int $tranAccountId, int $lineAccountId): self
    {
        return new self(sprintf(
            'Transaction %d (account %d) cannot be resolved against a credit line '
            . 'that belongs to account %d. Accounts must match.',
            $tranId,
            $tranAccountId,
            $lineAccountId,
        ));
    }
}
