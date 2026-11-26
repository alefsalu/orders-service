<?php

declare(strict_types=1);

namespace App\Domain\Event;

final class OrderPlacedDomainEvent implements DomainEventInterface
{
    private string $eventId;
    private string $eventType = 'orders.order.placed.v1';
    private string $aggregateType = 'Order';

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        ?string $eventId,
        private readonly string $aggregateId,
        private readonly \DateTimeImmutable $occurredAt,
        private readonly array $payload,
    ) {
        $this->eventId = $eventId ?? (function (): string {
            $data = random_bytes(16);
            $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
            $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        })();
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function aggregateType(): string
    {
        return $this->aggregateType;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
