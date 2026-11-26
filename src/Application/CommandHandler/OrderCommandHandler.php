<?php

declare(strict_types=1);

namespace App\Application\CommandHandler;

use App\Application\Command\AddOrderItemCommand;
use App\Application\Command\CancelOrderCommand;
use App\Application\Command\ConfirmOrderPaymentCommand;
use App\Application\Command\CreateOrderCommand;
use App\Application\Command\PlaceOrderCommand;
use App\Application\Command\RemoveOrderItemCommand;
use App\Application\Command\ShipOrderCommand;
use App\Domain\Exception\OrderNotFoundException;
use App\Domain\Model\Order;
use App\Domain\Model\OutboxMessage;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\OutboxRepositoryInterface;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\Quantity;
use App\Infrastructure\Projection\OrderHistoryProjector;

class OrderCommandHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OutboxRepositoryInterface $outboxRepository,
        private readonly ?OrderHistoryProjector $projector = null,
    ) {
    }

    public function handleCreateOrder(CreateOrderCommand $cmd): string
    {
        $orderId = OrderId::generate();
        $customerId = CustomerId::fromString($cmd->customerId);

        $order = Order::create($orderId, $customerId);

        $this->persistAndFlushEvents($order, $cmd->correlationId);

        return $orderId->value();
    }

    public function handleAddOrderItem(AddOrderItemCommand $cmd): void
    {
        $order = $this->loadOrder($cmd->orderId);

        $currency = Currency::from($cmd->unitPriceCurrency);
        $unitPrice = Money::fromDecimal($cmd->unitPriceAmount, $currency);
        $quantity = Quantity::fromInt($cmd->quantity);

        $order->addItem($cmd->productId, $cmd->productName, $unitPrice, $quantity);

        $this->persistAndFlushEvents($order, $cmd->correlationId);
    }

    public function handleRemoveOrderItem(RemoveOrderItemCommand $cmd): void
    {
        $order = $this->loadOrder($cmd->orderId);
        $order->removeItem($cmd->orderLineId);

        $this->persistAndFlushEvents($order, $cmd->correlationId);
    }

    public function handlePlaceOrder(PlaceOrderCommand $cmd): void
    {
        $order = $this->loadOrder($cmd->orderId);
        $order->place();

        $this->persistAndFlushEvents($order, $cmd->correlationId);
    }

    public function handleConfirmPayment(ConfirmOrderPaymentCommand $cmd): void
    {
        $order = $this->loadOrder($cmd->orderId);
        $currency = Currency::from($cmd->currency);
        $amountPaid = Money::fromDecimal($cmd->amount, $currency);

        $order->confirmPayment($amountPaid, $cmd->paymentReference);

        $this->persistAndFlushEvents($order, $cmd->correlationId);
    }

    public function handleShipOrder(ShipOrderCommand $cmd): void
    {
        $order = $this->loadOrder($cmd->orderId);
        $order->ship($cmd->trackingNumber);

        $this->persistAndFlushEvents($order, $cmd->correlationId);
    }

    public function handleCancelOrder(CancelOrderCommand $cmd): void
    {
        $order = $this->loadOrder($cmd->orderId);
        $order->cancel($cmd->reason);

        $this->persistAndFlushEvents($order, $cmd->correlationId);
    }

    private function loadOrder(string $id): Order
    {
        $order = $this->orderRepository->findById(OrderId::fromString($id));
        if ($order === null) {
            throw new OrderNotFoundException($id);
        }

        return $order;
    }

    private function persistAndFlushEvents(Order $order, string $correlationId): void
    {
        $events = $order->pullDomainEvents();
        foreach ($events as $event) {
            $outboxMessage = new OutboxMessage(
                id: $event->eventId(),
                eventType: $event->eventType(),
                aggregateType: $event->aggregateType(),
                aggregateId: $event->aggregateId(),
                correlationId: $correlationId,
                payload: $event->payload(),
                occurredAt: $event->occurredAt(),
            );
            $this->outboxRepository->append($outboxMessage);

            if ($this->projector !== null) {
                $this->projector->handle($event);
            }
        }

        $this->orderRepository->save($order);
    }
}
