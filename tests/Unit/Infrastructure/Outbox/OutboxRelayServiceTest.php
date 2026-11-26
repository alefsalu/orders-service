<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Outbox;

use App\Domain\Repository\OutboxRepositoryInterface;
use App\Infrastructure\Outbox\AmqpEventPublisherInterface;
use App\Infrastructure\Outbox\OutboxMessage;
use App\Infrastructure\Outbox\OutboxRelayService;
use PHPUnit\Framework\TestCase;

final class OutboxRelayServiceTest extends TestCase
{
    public function testRelayPublishesUnpublishedMessagesAndMarksPublishedAt(): void
    {
        $message = new OutboxMessage(
            id: '00000000-0000-0000-0000-000000000001',
            eventType: 'orders.order.placed.v1',
            aggregateType: 'Order',
            aggregateId: '00000000-0000-0000-0000-000000000002',
            correlationId: '00000000-0000-0000-0000-000000000003',
            payload: ['status' => 'PLACED'],
            occurredAt: new \DateTimeImmutable('2026-11-25T20:00:00Z')
        );

        $outboxRepo = $this->createMock(OutboxRepositoryInterface::class);
        $outboxRepo->expects(self::once())->method('findUnpublished')->with(100)->willReturn([$message]);
        $outboxRepo->expects(self::once())->method('save')->with($message);

        $publisher = $this->createMock(AmqpEventPublisherInterface::class);
        $publisher->expects(self::once())->method('publish')->with(self::equalTo($message));

        $relay = new OutboxRelayService($outboxRepo, $publisher);
        $publishedCount = $relay->publishPendingMessages(100);

        self::assertSame(1, $publishedCount);
        self::assertNotNull($message->publishedAt());
    }

    // Review Focus 3: RabbitMQ broker failure increments attempts without marking published
    public function testRelayHandlesPublisherFailureGracefully(): void
    {
        $message = new OutboxMessage(
            id: '00000000-0000-0000-0000-000000000001',
            eventType: 'orders.order.placed.v1',
            aggregateType: 'Order',
            aggregateId: '00000000-0000-0000-0000-000000000002',
            correlationId: '00000000-0000-0000-0000-000000000003',
            payload: [],
            occurredAt: new \DateTimeImmutable('2026-11-25T20:00:00Z')
        );

        $outboxRepo = $this->createMock(OutboxRepositoryInterface::class);
        $outboxRepo->method('findUnpublished')->willReturn([$message]);
        $outboxRepo->expects(self::once())->method('save')->with($message);

        $publisher = $this->createMock(AmqpEventPublisherInterface::class);
        $publisher->method('publish')->willThrowException(new \RuntimeException('RabbitMQ connection lost'));

        $relay = new OutboxRelayService($outboxRepo, $publisher);
        $publishedCount = $relay->publishPendingMessages(100);

        self::assertSame(0, $publishedCount);
        self::assertNull($message->publishedAt());
        self::assertSame(1, $message->attempts());
    }
}
