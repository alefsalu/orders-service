<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Event;

use App\Domain\Event\OrderCancelledDomainEvent;
use App\Domain\Event\OrderCreatedDomainEvent;
use App\Domain\Event\OrderItemAddedDomainEvent;
use App\Domain\Event\OrderPaidDomainEvent;
use App\Domain\Event\OrderPlacedDomainEvent;
use App\Domain\Event\OrderShippedDomainEvent;
use App\Domain\Model\Order;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

final class OrderEventsTest extends TestCase
{
    public function testOrderRecordsDomainEventsOnLifecycleActions(): void
    {
        $orderId = OrderId::generate();
        $order = Order::create($orderId, CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order->addItem('p-1', 'Item 1', Money::fromDecimal('50.00', Currency::BRL), Quantity::fromInt(1));
        $order->place();

        $events = $order->pullDomainEvents();
        self::assertCount(3, $events); // Created, ItemAdded, Placed

        self::assertInstanceOf(OrderCreatedDomainEvent::class, $events[0]);
        self::assertSame('orders.order.created.v1', $events[0]->eventType());
        self::assertSame($orderId->value(), $events[0]->aggregateId());

        self::assertInstanceOf(OrderItemAddedDomainEvent::class, $events[1]);
        self::assertSame('orders.order.item_added.v1', $events[1]->eventType());

        self::assertInstanceOf(OrderPlacedDomainEvent::class, $events[2]);
        self::assertSame('orders.order.placed.v1', $events[2]->eventType());
        self::assertSame($orderId->value(), $events[2]->aggregateId());
        self::assertSame('Order', $events[2]->aggregateType());
        self::assertSame('50.00', $events[2]->payload()['totalAmount']['amount']);

        // Subsequent pull returns empty array
        self::assertEmpty($order->pullDomainEvents());
    }

    public function testPaymentShippingAndCancellationEvents(): void
    {
        $orderId = OrderId::generate();
        $order = Order::create($orderId, CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order->addItem('p-1', 'Item 1', Money::fromDecimal('100.00', Currency::BRL), Quantity::fromInt(1));
        $order->place();
        $order->pullDomainEvents(); // Clear initial events

        $order->confirmPayment(Money::fromDecimal('100.00', Currency::BRL), 'pix-123');
        $events = $order->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderPaidDomainEvent::class, $events[0]);
        self::assertSame('orders.order.paid.v1', $events[0]->eventType());
        self::assertSame('pix-123', $events[0]->payload()['paymentReference']);

        $order->ship('TRACK-BR');
        $events = $order->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderShippedDomainEvent::class, $events[0]);
        self::assertSame('orders.order.shipped.v1', $events[0]->eventType());
        self::assertSame('TRACK-BR', $events[0]->payload()['trackingNumber']);

        // Test cancel on new order
        $order2 = Order::create(OrderId::generate(), CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order2->pullDomainEvents();
        $order2->cancel('Changed mind');
        $cancelEvents = $order2->pullDomainEvents();
        self::assertCount(1, $cancelEvents);
        self::assertInstanceOf(OrderCancelledDomainEvent::class, $cancelEvents[0]);
        self::assertSame('orders.order.cancelled.v1', $cancelEvents[0]->eventType());
    }
}
