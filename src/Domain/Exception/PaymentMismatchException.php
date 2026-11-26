<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class PaymentMismatchException extends \DomainException
{
    public function __construct(string $expectedAmount, string $actualAmount)
    {
        parent::__construct(sprintf('Payment amount mismatch: expected "%s", received "%s".', $expectedAmount, $actualAmount));
    }
}
