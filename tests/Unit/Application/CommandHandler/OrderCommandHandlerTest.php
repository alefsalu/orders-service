<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\CommandHandler;

use App\Application\Command\AddOrderItemCommand;
use App\Application\Command\CreateOrderCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\CommandHandler\OrderCommandHandler;
use App\Domain\Model\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\OutboxRepositoryInterface;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

final class OrderCommandHandlerTest extends TestCase
{
    public function testHandlePlaceOrderSavesAggregateAndAppendsOutbox(): void
    {
        $orderId = OrderId::generate();
        $order = Order::create($orderId, CustomerId::fromString('00000000-0000-0000-0000-000000000001'));
        $order->addItem('p-1', 'Widget', Money::fromDecimal('20.00', Currency::BRL), Quantity::fromInt(1));

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->method('findById')->with($orderId)->willReturn($order);
        $orderRepo->expects(self::once())->method('save')->with($order);

        $outboxRepo = $this->createMock(OutboxRepositoryInterface::class);
        $outboxRepo->expects(self::atLeastOnce())->method('append');

        $handler = new OrderCommandHandler($orderRepo, $outboxRepo);
        $handler->handlePlaceOrder(new PlaceOrderCommand($orderId->value(), 'corr-123'));

        self::assertSame(OrderStatus::PLACED, $order->status());
    }

    public function testHandleCreateOrderPersistsOrderAndOutbox(): void
    {
        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->expects(self::once())->method('save');

        $outboxRepo = $this->createMock(OutboxRepositoryInterface::class);
        $outboxRepo->expects(self::once())->method('append');

        $handler = new OrderCommandHandler($orderRepo, $outboxRepo);
        $orderId = $handler->handleCreateOrder(new CreateOrderCommand('00000000-0000-0000-0000-000000000001', 'corr-create'));

        self::assertNotEmpty($orderId);
    }

    public function testHandleAddOrderItemAddsLineAndSaves(): void
    {
        $orderId = OrderId::generate();
        $order = Order::create($orderId, CustomerId::fromString('00000000-0000-0000-0000-000000000001'));

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->method('findById')->with($orderId)->willReturn($order);
        $orderRepo->expects(self::once())->method('save')->with($order);

        $outboxRepo = $this->createMock(OutboxRepositoryInterface::class);
        $outboxRepo->expects(self::atLeastOnce())->method('append');

        $handler = new OrderCommandHandler($orderRepo, $outboxRepo);
        $handler->handleAddOrderItem(new AddOrderItemCommand(
            orderId: $orderId->value(),
            productId: 'prod-item',
            productName: 'Sample Product',
            unitPriceAmount: '35.00',
            unitPriceCurrency: 'BRL',
            quantity: 2,
            correlationId: 'corr-item'
        ));

        self::assertCount(1, $order->items());
        self::assertSame('70.00', $order->totalAmount()->amount());
    }
}
