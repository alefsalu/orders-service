<?php

declare(strict_types=1);

namespace App\Domain\ReadModel;

final class OrderHistoryProjection implements \JsonSerializable
{
    /**
     * @param list<array<string, mixed>> $items
     * @param array{amount: string, currency: string} $totalAmount
     * @param list<array<string, mixed>> $timeline
     */
    public function __construct(
        private readonly string $orderId,
        private readonly string $customerId,
        private string $currentStatus,
        private array $items,
        private array $totalAmount,
        private array $timeline,
        private \DateTimeImmutable $lastUpdatedAt,
    ) {
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function currentStatus(): string
    {
        return $this->currentStatus;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return array{amount: string, currency: string}
     */
    public function totalAmount(): array
    {
        return $this->totalAmount;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timeline(): array
    {
        return $this->timeline;
    }

    public function lastUpdatedAt(): \DateTimeImmutable
    {
        return $this->lastUpdatedAt;
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function appendTimelineEntry(string $newStatus, array $entry, \DateTimeImmutable $occurredAt): void
    {
        $this->currentStatus = $newStatus;
        $this->timeline[] = $entry;
        $this->lastUpdatedAt = $occurredAt;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array{amount: string, currency: string} $totalAmount
     */
    public function updateItemsAndTotal(array $items, array $totalAmount): void
    {
        $this->items = $items;
        $this->totalAmount = $totalAmount;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'orderId' => $this->orderId,
            'customerId' => $this->customerId,
            'currentStatus' => $this->currentStatus,
            'items' => $this->items,
            'totalAmount' => $this->totalAmount,
            'timeline' => $this->timeline,
            'lastUpdatedAt' => $this->lastUpdatedAt->format(\DateTimeInterface::RFC3339),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
