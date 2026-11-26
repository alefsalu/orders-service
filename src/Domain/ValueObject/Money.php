<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidArgumentException;

final class Money implements \JsonSerializable
{
    private function __construct(
        private readonly string $amount,
        private readonly Currency $currency = Currency::BRL,
    ) {
        if (!preg_match('/^\d+\.\d{2}$/', $this->amount)) {
            throw new InvalidArgumentException(sprintf('Invalid decimal amount format: "%s". Must have exactly 2 decimal places.', $this->amount));
        }

        if (self::compareAmounts($this->amount, '0.00') < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }
    }

    public static function fromDecimal(string $amount, Currency $currency = Currency::BRL): self
    {
        return new self($amount, $currency);
    }

    public static function zero(Currency $currency = Currency::BRL): self
    {
        return new self('0.00', $currency);
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add Money with different currencies.');
        }

        $newAmount = self::addAmounts($this->amount, $other->amount);

        return new self($newAmount, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative.');
        }

        $newAmount = self::multiplyAmount($this->amount, $multiplier);

        return new self($newAmount, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && self::compareAmounts($this->amount, $other->amount) === 0;
    }

    private static function compareAmounts(string $a, string $b): int
    {
        if (\function_exists('bccomp')) {
            return \bccomp($a, $b, 2);
        }
        $centsA = (int) \round(((float) $a) * 100);
        $centsB = (int) \round(((float) $b) * 100);
        return $centsA <=> $centsB;
    }

    private static function addAmounts(string $a, string $b): string
    {
        if (\function_exists('bcadd')) {
            return \bcadd($a, $b, 2);
        }
        $cents = (int) \round(((float) $a) * 100) + (int) \round(((float) $b) * 100);
        return \sprintf('%.2f', $cents / 100);
    }

    private static function multiplyAmount(string $a, int $multiplier): string
    {
        if (\function_exists('bcmul')) {
            return \bcmul($a, (string) $multiplier, 2);
        }
        $cents = (int) \round(((float) $a) * 100) * $multiplier;
        return \sprintf('%.2f', $cents / 100);
    }

    /**
     * @return array{amount: string, currency: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->value,
        ];
    }
}
