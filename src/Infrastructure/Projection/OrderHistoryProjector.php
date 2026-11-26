<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Event\DomainEventInterface;
use App\Domain\ReadModel\OrderHistoryProjection;
use App\Domain\Repository\OrderHistoryRepositoryInterface;

class OrderHistoryProjector
{
    public function __construct(private readonly OrderHistoryRepositoryInterface $historyRepository)
    {
    }

    public function handle(DomainEventInterface $event): void
    {
        $orderId = $event->aggregateId();
        $projection = $this->historyRepository->findByOrderId($orderId);

        $payload = $event->payload();
        $occurredAt = $event->occurredAt();

        $timelineEntry = [
            'eventId' => $event->eventId(),
            'eventType' => $event->eventType(),
            'occurredAt' => $occurredAt->format(\DateTimeInterface::RFC3339),
            'payload' => $payload,
        ];

        $status = (string) ($payload['status'] ?? ($projection !== null ? $projection->currentStatus() : 'UNKNOWN'));

        if ($projection === null) {
            $customerId = (string) ($payload['customerId'] ?? '');
            /** @var array{amount: string, currency: string} $totalAmount */
            $totalAmount = isset($payload['totalAmount']) && is_array($payload['totalAmount'])
                ? ['amount' => (string) ($payload['totalAmount']['amount'] ?? '0.00'), 'currency' => (string) ($payload['totalAmount']['currency'] ?? 'BRL')]
                : ['amount' => '0.00', 'currency' => 'BRL'];

            $projection = new OrderHistoryProjection(
                orderId: $orderId,
                customerId: $customerId,
                currentStatus: $status,
                items: [],
                totalAmount: $totalAmount,
                timeline: [$timelineEntry],
                lastUpdatedAt: $occurredAt,
            );
        } else {
            $projection->appendTimelineEntry($status, $timelineEntry, $occurredAt);

            if (isset($payload['totalAmount']) && is_array($payload['totalAmount'])) {
                /** @var array{amount: string, currency: string} $newTotal */
                $newTotal = [
                    'amount' => (string) ($payload['totalAmount']['amount'] ?? $projection->totalAmount()['amount']),
                    'currency' => (string) ($payload['totalAmount']['currency'] ?? $projection->totalAmount()['currency']),
                ];
                $projection->updateItemsAndTotal($projection->items(), $newTotal);
            }
        }

        $this->historyRepository->upsertProjection($projection);
    }
}
