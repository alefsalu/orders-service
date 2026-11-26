<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Exception\EmptyOrderPlacementException;
use App\Domain\Exception\InvalidStateTransitionException;
use App\Domain\Exception\OrderNotModifiableException;
use App\Domain\Exception\PaymentMismatchException;
use App\Domain\Model\Order;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testCreateDraftOrderAndAddItemRecalculatesTotal(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        self::assertSame(OrderStatus::DRAFT, $order->status());
        self::assertSame('0.00', $order->totalAmount()->amount());

        $line = $order->addItem('prod-1', 'Product 1', Money::fromDecimal('25.50', Currency::BRL), Quantity::fromInt(2));
        self::assertCount(1, $order->items());
        self::assertSame('51.00', $order->totalAmount()->amount());
        self::assertSame('Product 1', $line->productName());
        self::assertSame('prod-1', $line->productId());
        self::assertSame(2, $line->quantity()->value());
    }

    public function testRemoveItemRecalculatesTotal(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $line1 = $order->addItem('prod-1', 'Product 1', Money::fromDecimal('10.00', Currency::BRL), Quantity::fromInt(1));
        $line2 = $order->addItem('prod-2', 'Product 2', Money::fromDecimal('20.00', Currency::BRL), Quantity::fromInt(1));
        self::assertSame('30.00', $order->totalAmount()->amount());

        $order->removeItem($line1->id()->value());
        self::assertCount(1, $order->items());
        self::assertSame('20.00', $order->totalAmount()->amount());
    }

    public function testCannotPlaceEmptyOrder(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $this->expectException(EmptyOrderPlacementException::class);
        $order->place();
    }

    // Review Focus 1: Invalid State Transition
    public function testInvalidStateTransitionThrowsException(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $this->expectException(InvalidStateTransitionException::class);
        $order->ship('TRACK123'); // Cannot ship DRAFT order
    }

    // Review Focus 2: Mutating items on non-draft order
    public function testCannotAddItemAfterOrderIsPlaced(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order->addItem('prod-1', 'Product 1', Money::fromDecimal('10.00', Currency::BRL), Quantity::fromInt(1));
        $order->place();

        $this->expectException(OrderNotModifiableException::class);
        $order->addItem('prod-2', 'Product 2', Money::fromDecimal('5.00', Currency::BRL), Quantity::fromInt(1));
    }

    public function testCannotRemoveItemAfterOrderIsPlaced(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $line = $order->addItem('prod-1', 'Product 1', Money::fromDecimal('10.00', Currency::BRL), Quantity::fromInt(1));
        $order->place();

        $this->expectException(OrderNotModifiableException::class);
        $order->removeItem($line->id()->value());
    }

    public function testPaymentConfirmationRequiresMatchingAmount(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order->addItem('prod-1', 'Product 1', Money::fromDecimal('100.00', Currency::BRL), Quantity::fromInt(1));
        $order->place();

        $this->expectException(PaymentMismatchException::class);
        $order->confirmPayment(Money::fromDecimal('99.00', Currency::BRL), 'pay-ref-123');
    }

    public function testFullSuccessfulOrderLifecycle(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order->addItem('prod-1', 'Product 1', Money::fromDecimal('100.00', Currency::BRL), Quantity::fromInt(1));
        $order->place();
        self::assertSame(OrderStatus::PLACED, $order->status());

        $order->confirmPayment(Money::fromDecimal('100.00', Currency::BRL), 'pay-ref-123');
        self::assertSame(OrderStatus::PAID, $order->status());

        $order->ship('TRACK-BR-123');
        self::assertSame(OrderStatus::SHIPPED, $order->status());
    }

    public function testCancelDraftOrder(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order->cancel('Customer changed mind');
        self::assertSame(OrderStatus::CANCELLED, $order->status());
    }

    public function testCannotCancelShippedOrder(): void
    {
        $order = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order->addItem('prod-1', 'Product 1', Money::fromDecimal('10.00', Currency::BRL), Quantity::fromInt(1));
        $order->place();
        $order->confirmPayment(Money::fromDecimal('10.00', Currency::BRL), 'ref-1');
        $order->ship('TRK-1');

        $this->expectException(InvalidStateTransitionException::class);
        $order->cancel('Too late');
    }
}
