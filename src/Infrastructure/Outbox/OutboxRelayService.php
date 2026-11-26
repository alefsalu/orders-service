<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Domain\Repository\OutboxRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class OutboxRelayService
{
    public function __construct(
        private readonly OutboxRepositoryInterface $outboxRepository,
        private readonly AmqpEventPublisherInterface $publisher,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function publishPendingMessages(int $batchSize = 100): int
    {
        $messages = $this->outboxRepository->findUnpublished($batchSize);
        $publishedCount = 0;

        foreach ($messages as $message) {
            try {
                $this->publisher->publish($message);
                $message->markPublished(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
                $this->outboxRepository->save($message);
                ++$publishedCount;

                $this->logger->info(sprintf('Outbox message "%s" published successfully.', $message->id()), [
                    'correlationId' => $message->correlationId(),
                    'eventType' => $message->eventType(),
                ]);
            } catch (\Throwable $e) {
                $message->recordFailedAttempt();
                $this->outboxRepository->save($message);

                $this->logger->error(sprintf('Failed to publish outbox message "%s": %s', $message->id(), $e->getMessage()), [
                    'correlationId' => $message->correlationId(),
                    'eventType' => $message->eventType(),
                    'attempts' => $message->attempts(),
                    'exception' => $e,
                ]);
            }
        }

        return $publishedCount;
    }
}
