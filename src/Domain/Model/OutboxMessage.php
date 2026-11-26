<?php

declare(strict_types=1);

namespace App\Domain\Model;

class OutboxMessage
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        protected string $id,
        protected string $eventType,
        protected string $aggregateType,
        protected string $aggregateId,
        protected string $correlationId,
        protected array $payload,
        protected \DateTimeImmutable $occurredAt,
        protected ?\DateTimeImmutable $publishedAt = null,
        protected int $attempts = 0,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function aggregateType(): string
    {
        return $this->aggregateType;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function publishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function markPublished(\DateTimeImmutable $timestamp): void
    {
        $this->publishedAt = $timestamp;
    }

    public function recordFailedAttempt(): void
    {
        ++$this->attempts;
    }
}
