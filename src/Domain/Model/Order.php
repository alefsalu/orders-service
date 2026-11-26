<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Event\DomainEventInterface;
use App\Domain\Event\OrderCancelledDomainEvent;
use App\Domain\Event\OrderCreatedDomainEvent;
use App\Domain\Event\OrderItemAddedDomainEvent;
use App\Domain\Event\OrderPaidDomainEvent;
use App\Domain\Event\OrderPlacedDomainEvent;
use App\Domain\Event\OrderShippedDomainEvent;
use App\Domain\Exception\EmptyOrderPlacementException;
use App\Domain\Exception\InvalidStateTransitionException;
use App\Domain\Exception\OrderNotModifiableException;
use App\Domain\Exception\PaymentMismatchException;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\Quantity;

class Order
{
    private string $id;
    private string $customerId;
    private string $status;
    private string $totalAmountValue;
    private string $totalAmountCurrency;
    private int $version = 1;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    /**
     * @var array<string, OrderLine>|iterable<OrderLine>
     */
    private iterable $items = [];

    /**
     * @var list<DomainEventInterface>
     */
    private array $recordedEvents = [];

    private function __construct(OrderId $id, CustomerId $customerId)
    {
        $this->id = $id->value();
        $this->customerId = $customerId->value();
        $this->status = OrderStatus::DRAFT->value;
        $this->totalAmountValue = '0.00';
        $this->totalAmountCurrency = Currency::BRL->value;
        $this->version = 1;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->updatedAt = $this->createdAt;
        $this->items = [];

        $this->recordEvent(new OrderCreatedDomainEvent(
            eventId: null,
            aggregateId: $this->id,
            occurredAt: $this->createdAt,
            payload: [
                'orderId' => $this->id,
                'customerId' => $this->customerId,
                'status' => $this->status,
                'totalAmount' => [
                    'amount' => $this->totalAmountValue,
                    'currency' => $this->totalAmountCurrency,
                ],
            ]
        ));
    }

    public static function create(OrderId $id, CustomerId $customerId): self
    {
        return new self($id, $customerId);
    }

    public function id(): OrderId
    {
        return OrderId::fromString($this->id);
    }

    public function customerId(): CustomerId
    {
        return CustomerId::fromString($this->customerId);
    }

    public function status(): OrderStatus
    {
        return OrderStatus::from($this->status);
    }

    public function totalAmount(): Money
    {
        return Money::fromDecimal($this->totalAmountValue, Currency::from($this->totalAmountCurrency));
    }

    public function version(): int
    {
        return $this->version;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return list<OrderLine>
     */
    public function items(): array
    {
        if (is_array($this->items)) {
            return array_values($this->items);
        }

        if (is_iterable($this->items)) {
            $list = [];
            foreach ($this->items as $item) {
                $list[] = $item;
            }

            return $list;
        }

        return [];
    }

    public function addItem(string $productId, string $productName, Money $unitPrice, Quantity $quantity): OrderLine
    {
        if ($this->status !== OrderStatus::DRAFT->value) {
            throw new OrderNotModifiableException($this->status);
        }

        $lineId = OrderLineId::generate();
        $line = new OrderLine($lineId, $productId, $productName, $unitPrice, $quantity);
        $line->setOrder($this);

        if (is_array($this->items)) {
            $this->items[$lineId->value()] = $line;
        } elseif (method_exists($this->items, 'add')) {
            $this->items->add($line);
        }

        $this->recalculateTotal();
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->recordEvent(new OrderItemAddedDomainEvent(
            eventId: null,
            aggregateId: $this->id,
            occurredAt: $this->updatedAt,
            payload: [
                'orderLineId' => $lineId->value(),
                'productId' => $productId,
                'productName' => $productName,
                'unitPrice' => $unitPrice->jsonSerialize(),
                'quantity' => $quantity->value(),
                'totalAmount' => $this->totalAmount()->jsonSerialize(),
            ]
        ));

        return $line;
    }

    public function removeItem(string $orderLineId): void
    {
        if ($this->status !== OrderStatus::DRAFT->value) {
            throw new OrderNotModifiableException($this->status);
        }

        if (is_array($this->items)) {
            unset($this->items[$orderLineId]);
        } elseif (method_exists($this->items, 'removeElement')) {
            foreach ($this->items() as $item) {
                if ($item->id()->value() === $orderLineId) {
                    $this->items->removeElement($item);
                    break;
                }
            }
        }

        $this->recalculateTotal();
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function place(): void
    {
        if ($this->status !== OrderStatus::DRAFT->value) {
            throw new InvalidStateTransitionException($this->status, OrderStatus::PLACED->value);
        }

        if (count($this->items()) === 0) {
            throw new EmptyOrderPlacementException();
        }

        $this->status = OrderStatus::PLACED->value;
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->recordEvent(new OrderPlacedDomainEvent(
            eventId: null,
            aggregateId: $this->id,
            occurredAt: $this->updatedAt,
            payload: [
                'orderId' => $this->id,
                'status' => $this->status,
                'totalAmount' => $this->totalAmount()->jsonSerialize(),
            ]
        ));
    }

    public function confirmPayment(Money $amountPaid, string $paymentReference): void
    {
        if ($this->status !== OrderStatus::PLACED->value) {
            throw new InvalidStateTransitionException($this->status, OrderStatus::PAID->value);
        }

        if (!$this->totalAmount()->equals($amountPaid)) {
            throw new PaymentMismatchException($this->totalAmount()->amount(), $amountPaid->amount());
        }

        $this->status = OrderStatus::PAID->value;
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->recordEvent(new OrderPaidDomainEvent(
            eventId: null,
            aggregateId: $this->id,
            occurredAt: $this->updatedAt,
            payload: [
                'orderId' => $this->id,
                'status' => $this->status,
                'paymentReference' => $paymentReference,
                'amountPaid' => $amountPaid->jsonSerialize(),
            ]
        ));
    }

    public function ship(string $trackingNumber): void
    {
        if ($this->status !== OrderStatus::PAID->value) {
            throw new InvalidStateTransitionException($this->status, OrderStatus::SHIPPED->value);
        }

        $this->status = OrderStatus::SHIPPED->value;
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->recordEvent(new OrderShippedDomainEvent(
            eventId: null,
            aggregateId: $this->id,
            occurredAt: $this->updatedAt,
            payload: [
                'orderId' => $this->id,
                'status' => $this->status,
                'trackingNumber' => $trackingNumber,
            ]
        ));
    }

    public function cancel(string $reason): void
    {
        if ($this->status !== OrderStatus::DRAFT->value && $this->status !== OrderStatus::PLACED->value) {
            throw new InvalidStateTransitionException($this->status, OrderStatus::CANCELLED->value);
        }

        $this->status = OrderStatus::CANCELLED->value;
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->recordEvent(new OrderCancelledDomainEvent(
            eventId: null,
            aggregateId: $this->id,
            occurredAt: $this->updatedAt,
            payload: [
                'orderId' => $this->id,
                'status' => $this->status,
                'reason' => $reason,
            ]
        ));
    }

    /**
     * @return list<DomainEventInterface>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function recordEvent(DomainEventInterface $event): void
    {
        $this->recordedEvents[] = $event;
    }

    private function recalculateTotal(): void
    {
        $total = Money::zero(Currency::from($this->totalAmountCurrency));
        foreach ($this->items() as $item) {
            $total = $total->add($item->totalPrice());
        }

        $this->totalAmountValue = $total->amount();
        $this->totalAmountCurrency = $total->currency()->value;
    }
}
