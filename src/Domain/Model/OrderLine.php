<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;

class OrderLine
{
    private string $id;
    private ?Order $order = null;
    private string $productId;
    private string $productName;
    private string $unitPriceAmount;
    private string $unitPriceCurrency;
    private int $quantity;
    private string $totalPriceAmount;
    private string $totalPriceCurrency;

    public function __construct(
        OrderLineId $id,
        string $productId,
        string $productName,
        Money $unitPrice,
        Quantity $quantity,
    ) {
        $this->id = $id->value();
        $this->productId = $productId;
        $this->productName = $productName;
        $this->unitPriceAmount = $unitPrice->amount();
        $this->unitPriceCurrency = $unitPrice->currency()->value;
        $this->quantity = $quantity->value();

        $total = $unitPrice->multiply($quantity->value());
        $this->totalPriceAmount = $total->amount();
        $this->totalPriceCurrency = $total->currency()->value;
    }

    public function id(): OrderLineId
    {
        return OrderLineId::fromString($this->id);
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function productName(): string
    {
        return $this->productName;
    }

    public function unitPrice(): Money
    {
        return Money::fromDecimal($this->unitPriceAmount, Currency::from($this->unitPriceCurrency));
    }

    public function quantity(): Quantity
    {
        return Quantity::fromInt($this->quantity);
    }

    public function totalPrice(): Money
    {
        return Money::fromDecimal($this->totalPriceAmount, Currency::from($this->totalPriceCurrency));
    }

    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }
}
