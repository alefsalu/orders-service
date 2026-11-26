<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;
use App\Domain\ValueObject\OrderId;
use PHPUnit\Framework\TestCase;

final class OrderIdTest extends TestCase
{
    public function testGenerateCreatesValidUuidV4(): void
    {
        $orderId = OrderId::generate();
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $orderId->value());
        self::assertSame($orderId->value(), (string) $orderId);
        self::assertSame($orderId->value(), $orderId->jsonSerialize());
    }

    public function testFromStringAcceptsValidUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $orderId = OrderId::fromString($uuid);
        self::assertSame($uuid, $orderId->value());
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrderId::fromString('invalid-uuid');
    }

    public function testEquality(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id1 = OrderId::fromString($uuid);
        $id2 = OrderId::fromString($uuid);
        $id3 = OrderId::generate();

        self::assertTrue($id1->equals($id2));
        self::assertFalse($id1->equals($id3));
    }
}
