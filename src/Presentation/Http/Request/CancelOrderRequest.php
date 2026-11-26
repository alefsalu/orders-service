<?php

declare(strict_types=1);

namespace App\Presentation\Http\Request;

use App\Domain\Exception\InvalidArgumentException;

final readonly class CancelOrderRequest
{
    public function __construct(public string $reason)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['reason']) || !is_string($data['reason'])) {
            throw new InvalidArgumentException('Missing or invalid "reason".');
        }

        return new self(reason: $data['reason']);
    }
}
