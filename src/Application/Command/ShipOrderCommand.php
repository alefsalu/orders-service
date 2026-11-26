<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class ShipOrderCommand
{
    public function __construct(
        public string $orderId,
        public string $trackingNumber,
        public string $correlationId,
    ) {
    }
}
