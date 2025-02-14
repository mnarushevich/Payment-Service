<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class StripeCustomerException extends Exception
{
    public function __construct($message = 'Stripe customer exception', $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
