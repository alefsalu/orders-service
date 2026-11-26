<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Domain\Model\OutboxMessage;
use App\Domain\Repository\OutboxRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineOutboxRepository implements OutboxRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function append(OutboxMessage $message): void
    {
        $this->entityManager->persist($message);
    }

    /**
     * @return list<OutboxMessage>
     */
    public function findUnpublished(int $limit = 100): array
    {
        /** @var list<OutboxMessage> $result */
        $result = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(OutboxMessage::class, 'o')
            ->where('o.publishedAt IS NULL')
            ->orderBy('o.occurredAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function save(OutboxMessage $message): void
    {
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }
}
