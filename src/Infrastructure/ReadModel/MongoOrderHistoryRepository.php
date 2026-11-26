<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel;

use App\Domain\ReadModel\OrderHistoryProjection;
use App\Domain\Repository\OrderHistoryRepositoryInterface;
use MongoDB\Client;
use MongoDB\Collection;

class MongoOrderHistoryRepository implements OrderHistoryRepositoryInterface
{
    private Collection $collection;

    public function __construct(
        private readonly Client $client,
        private readonly string $databaseName = 'orders_history',
        private readonly string $collectionName = 'order_history',
    ) {
        $this->collection = $this->client->selectCollection($this->databaseName, $this->collectionName);
    }

    public function findByOrderId(string $orderId): ?OrderHistoryProjection
    {
        /** @var array<string, mixed>|null $doc */
        $doc = $this->collection->findOne(['orderId' => $orderId]);
        if ($doc === null) {
            return null;
        }

        return $this->mapDocumentToProjection($doc);
    }

    public function upsertProjection(OrderHistoryProjection $projection): void
    {
        $data = $projection->toArray();

        $this->collection->updateOne(
            ['orderId' => $projection->orderId()],
            ['$set' => $data],
            ['upsert' => true]
        );
    }

    /**
     * @param array<string, mixed> $doc
     */
    private function mapDocumentToProjection(array $doc): OrderHistoryProjection
    {
        /** @var list<array<string, mixed>> $items */
        $items = isset($doc['items']) && is_array($doc['items']) ? array_values($doc['items']) : [];

        /** @var array{amount: string, currency: string} $totalAmount */
        $totalAmount = isset($doc['totalAmount']) && is_array($doc['totalAmount'])
            ? ['amount' => (string) ($doc['totalAmount']['amount'] ?? '0.00'), 'currency' => (string) ($doc['totalAmount']['currency'] ?? 'BRL')]
            : ['amount' => '0.00', 'currency' => 'BRL'];

        /** @var list<array<string, mixed>> $timeline */
        $timeline = isset($doc['timeline']) && is_array($doc['timeline']) ? array_values($doc['timeline']) : [];

        $lastUpdatedAt = isset($doc['lastUpdatedAt']) && is_string($doc['lastUpdatedAt'])
            ? new \DateTimeImmutable($doc['lastUpdatedAt'])
            : new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return new OrderHistoryProjection(
            orderId: (string) ($doc['orderId'] ?? ''),
            customerId: (string) ($doc['customerId'] ?? ''),
            currentStatus: (string) ($doc['currentStatus'] ?? ''),
            items: $items,
            totalAmount: $totalAmount,
            timeline: $timeline,
            lastUpdatedAt: $lastUpdatedAt,
        );
    }
}
