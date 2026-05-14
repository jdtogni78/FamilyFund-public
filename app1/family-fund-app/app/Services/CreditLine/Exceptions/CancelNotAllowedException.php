<?php

namespace App\Services\CreditLine\Exceptions;

use RuntimeException;

/**
 * Thrown when cancelling a credit line is not permitted because it still has outstanding shares.
 */
class CancelNotAllowedException extends RuntimeException
{
    private float $outstandingShares;

    public function __construct(float $outstandingShares)
    {
        $this->outstandingShares = $outstandingShares;
        parent::__construct(
            sprintf(
                'Cannot cancel credit line: %.4f shares still outstanding. Pay off the line before cancelling.',
                $outstandingShares
            )
        );
    }

    public function getOutstandingShares(): float
    {
        return $this->outstandingShares;
    }
}
