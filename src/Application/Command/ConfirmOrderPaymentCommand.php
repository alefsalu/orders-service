<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class ConfirmOrderPaymentCommand
{
    public function __construct(
        public string $orderId,
        public string $paymentReference,
        public string $amount,
        public string $currency,
        public string $correlationId,
    ) {
    }
}
