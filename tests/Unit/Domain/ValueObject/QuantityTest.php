<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;
use App\Domain\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    public function testQuantityMustBePositive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantity::fromInt(0);
    }

    public function testQuantityRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantity::fromInt(-5);
    }

    public function testValidQuantity(): void
    {
        $q = Quantity::fromInt(10);
        self::assertSame(10, $q->value());
        self::assertSame(10, $q->jsonSerialize());
        self::assertTrue($q->equals(Quantity::fromInt(10)));
        self::assertFalse($q->equals(Quantity::fromInt(5)));
    }
}
