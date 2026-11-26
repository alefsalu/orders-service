<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class OrderLineDTO implements \JsonSerializable
{
    /**
     * @param array{amount: string, currency: string} $unitPrice
     * @param array{amount: string, currency: string} $totalPrice
     */
    public function __construct(
        public string $id,
        public string $productId,
        public string $productName,
        public array $unitPrice,
        public int $quantity,
        public array $totalPrice,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'productId' => $this->productId,
            'productName' => $this->productName,
            'unitPrice' => $this->unitPrice,
            'quantity' => $this->quantity,
            'totalPrice' => $this->totalPrice,
        ];
    }
}
