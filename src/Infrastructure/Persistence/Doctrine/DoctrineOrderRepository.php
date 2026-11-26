<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Model\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineOrderRepository implements OrderRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    public function findById(OrderId $id): ?Order
    {
        return $this->entityManager->find(Order::class, $id->value());
    }

    /**
     * @return list<Order>
     */
    public function findByCustomerId(CustomerId $customerId, int $page = 0, int $size = 20): array
    {
        /** @var list<Order> $result */
        $result = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.customerId = :customerId')
            ->setParameter('customerId', $customerId->value())
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult($page * $size)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function countByCustomerId(CustomerId $customerId): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Order::class, 'o')
            ->where('o.customerId = :customerId')
            ->setParameter('customerId', $customerId->value())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Order>
     */
    public function findAllPaginated(int $page = 0, int $size = 20): array
    {
        /** @var list<Order> $result */
        $result = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult($page * $size)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function countAll(): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Order::class, 'o')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
