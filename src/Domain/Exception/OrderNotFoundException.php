<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class OrderNotFoundException extends \DomainException
{
    public function __construct(string $orderId)
    {
        parent::__construct(sprintf('Order with ID "%s" was not found.', $orderId));
    }
}
