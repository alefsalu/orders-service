<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class CreateOrderCommand
{
    public function __construct(
        public string $customerId,
        public string $correlationId,
    ) {
    }
}
