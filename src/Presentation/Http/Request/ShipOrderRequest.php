<?php

declare(strict_types=1);

namespace App\Presentation\Http\Request;

use App\Domain\Exception\InvalidArgumentException;

final readonly class ShipOrderRequest
{
    public function __construct(public string $trackingNumber)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['trackingNumber']) || !is_string($data['trackingNumber'])) {
            throw new InvalidArgumentException('Missing or invalid "trackingNumber".');
        }

        return new self(trackingNumber: $data['trackingNumber']);
    }
}
