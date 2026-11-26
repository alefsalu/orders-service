<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class PlaceOrderCommand
{
    public function __construct(
        public string $orderId,
        public string $correlationId,
    ) {
    }
}
