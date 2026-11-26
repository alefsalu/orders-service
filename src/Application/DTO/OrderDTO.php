<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class OrderDTO implements \JsonSerializable
{
    /**
     * @param list<OrderLineDTO> $items
     * @param array{amount: string, currency: string} $totalAmount
     */
    public function __construct(
        public string $id,
        public string $customerId,
        public string $status,
        public array $items,
        public array $totalAmount,
        public int $version,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'customerId' => $this->customerId,
            'status' => $this->status,
            'items' => array_map(static fn (OrderLineDTO $item): array => $item->jsonSerialize(), $this->items),
            'totalAmount' => $this->totalAmount,
            'version' => $this->version,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
