<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;

final class Quantity implements \JsonSerializable
{
    private function __construct(private readonly int $value)
    {
        if ($this->value <= 0) {
            throw new InvalidArgumentException(sprintf('Quantity must be positive, got %d.', $this->value));
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
