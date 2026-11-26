<?php

declare(strict_types=1);

namespace App\Tests\Integration\ReadModel;

use App\Domain\Event\OrderPlacedDomainEvent;
use App\Infrastructure\Projection\OrderHistoryProjector;
use App\Infrastructure\ReadModel\MongoOrderHistoryRepository;
use MongoDB\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MongoOrderHistoryRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testProjectorInsertsAndAppendsToMongoOrderHistory(): void
    {
        $container = self::getContainer();
        if (!$container->has(Client::class)) {
            self::markTestSkipped('MongoDB client not configured');
        }

        /** @var Client $mongoClient */
        $mongoClient = $container->get(Client::class);
        $repo = new MongoOrderHistoryRepository($mongoClient, 'orders_test', 'order_history');
        $projector = new OrderHistoryProjector($repo);

        $orderId = '00000000-0000-0000-0000-000000000099';
        $event = new OrderPlacedDomainEvent(
            eventId: '00000000-0000-0000-0000-000000000088',
            aggregateId: $orderId,
            occurredAt: new \DateTimeImmutable('2026-11-25T20:30:00Z'),
            payload: [
                'customerId' => '00000000-0000-0000-0000-000000000001',
                'status' => 'PLACED',
                'totalAmount' => ['amount' => '150.00', 'currency' => 'BRL'],
            ]
        );

        $projector->handle($event);

        $history = $repo->findByOrderId($orderId);
        self::assertNotNull($history);
        self::assertSame('PLACED', $history->currentStatus());
        self::assertGreaterThanOrEqual(1, count($history->timeline()));
        self::assertSame('orders.order.placed.v1', $history->timeline()[0]['eventType']);
    }
}
