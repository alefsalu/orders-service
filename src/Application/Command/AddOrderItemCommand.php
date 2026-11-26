<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class AddOrderItemCommand
{
    public function __construct(
        public string $orderId,
        public string $productId,
        public string $productName,
        public string $unitPriceAmount,
        public string $unitPriceCurrency,
        public int $quantity,
        public string $correlationId,
    ) {
    }
}
