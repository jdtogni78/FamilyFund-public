<?php

namespace App\Services\CreditLine\Exceptions;

use RuntimeException;

/**
 * Thrown when a draw request would exceed the account's available-to-borrow balance.
 */
class OverBorrowException extends RuntimeException
{
    private float $requested;
    private float $available;

    public function __construct(float $requested, float $available)
    {
        $this->requested  = $requested;
        $this->available  = $available;
        parent::__construct(
            sprintf(
                'Cannot borrow %.4f shares: only %.4f shares available (OWN minus outstanding on active lines).',
                $requested,
                $available
            )
        );
    }

    public function getRequested(): float
    {
        return $this->requested;
    }

    public function getAvailable(): float
    {
        return $this->available;
    }
}
