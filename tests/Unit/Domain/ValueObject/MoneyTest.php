<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testMoneyMaintainsTwoDecimalPlacesAndEnforcesBrl(): void
    {
        $money = Money::fromDecimal('100.00', Currency::BRL);
        self::assertSame('100.00', $money->amount());
        self::assertSame(Currency::BRL, $money->currency());
        self::assertSame(['amount' => '100.00', 'currency' => 'BRL'], $money->jsonSerialize());
    }

    public function testMoneyAdditionAndMultiplication(): void
    {
        $a = Money::fromDecimal('49.99', Currency::BRL);
        $b = Money::fromDecimal('10.01', Currency::BRL);
        self::assertSame('60.00', $a->add($b)->amount());
        self::assertSame('149.97', $a->multiply(3)->amount());
    }

    public function testMoneyZeroFactory(): void
    {
        $zero = Money::zero();
        self::assertSame('0.00', $zero->amount());
        self::assertSame(Currency::BRL, $zero->currency());
    }

    public function testMoneyEquality(): void
    {
        $a = Money::fromDecimal('10.00', Currency::BRL);
        $b = Money::fromDecimal('10.00', Currency::BRL);
        $c = Money::fromDecimal('10.50', Currency::BRL);

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testMoneyRejectsNegativeAmountOrInvalidDecimalFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromDecimal('10', Currency::BRL);
    }

    public function testMoneyRejectsNegativeMultiplier(): void
    {
        $money = Money::fromDecimal('10.00', Currency::BRL);
        $this->expectException(InvalidArgumentException::class);
        $money->multiply(-1);
    }
}
