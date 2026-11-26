<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class OrderNotModifiableException extends \DomainException
{
    public function __construct(string $status)
    {
        parent::__construct(sprintf('Order in status "%s" cannot be modified.', $status));
    }
}
