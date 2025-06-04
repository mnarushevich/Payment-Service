<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

final class StripeCustomerException extends Exception
{
    public function __construct(
        string $message = 'Stripe customer exception',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
