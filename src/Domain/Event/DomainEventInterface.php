<?php

declare(strict_types=1);

namespace App\Domain\Event;

interface DomainEventInterface
{
    public function eventId(): string;

    public function eventType(): string;

    public function occurredAt(): \DateTimeImmutable;

    public function aggregateType(): string;

    public function aggregateId(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
