<?php

declare(strict_types=1);

namespace App\Presentation\Http\Request;

final readonly class CreateOrderRequest
{
    public function __construct(
        public ?string $customerId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customerId: isset($data['customerId']) && is_string($data['customerId']) ? $data['customerId'] : null,
        );
    }
}
