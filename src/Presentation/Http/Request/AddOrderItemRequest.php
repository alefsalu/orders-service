<?php

declare(strict_types=1);

namespace App\Presentation\Http\Request;

use App\Domain\Exception\InvalidArgumentException;

final readonly class AddOrderItemRequest
{
    /**
     * @param array{amount: string, currency: string} $unitPrice
     */
    public function __construct(
        public string $productId,
        public string $productName,
        public array $unitPrice,
        public int $quantity,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['productId']) || !is_string($data['productId'])) {
            throw new InvalidArgumentException('Missing or invalid "productId".');
        }

        if (empty($data['productName']) || !is_string($data['productName'])) {
            throw new InvalidArgumentException('Missing or invalid "productName".');
        }

        if (!isset($data['unitPrice']) || !is_array($data['unitPrice']) || !isset($data['unitPrice']['amount'])) {
            throw new InvalidArgumentException('Missing or invalid "unitPrice".');
        }

        if (!isset($data['quantity']) || !is_numeric($data['quantity']) || (int) $data['quantity'] <= 0) {
            throw new InvalidArgumentException('Quantity must be an integer greater than zero.');
        }

        return new self(
            productId: $data['productId'],
            productName: $data['productName'],
            unitPrice: [
                'amount' => (string) $data['unitPrice']['amount'],
                'currency' => (string) ($data['unitPrice']['currency'] ?? 'BRL'),
            ],
            quantity: (int) $data['quantity'],
        );
    }
}
