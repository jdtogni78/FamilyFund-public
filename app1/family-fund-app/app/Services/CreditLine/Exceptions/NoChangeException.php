<?php

namespace App\Services\CreditLine\Exceptions;

use RuntimeException;

/**
 * Thrown by ReadjustService when neither term_months nor payment_frequency
 * changed, so no adjustment row should be written.
 */
class NoChangeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No changes to term_months or payment_frequency — readjust aborted.');
    }
}
