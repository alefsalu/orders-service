<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Order;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\OrderId;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;

    public function findById(OrderId $id): ?Order;

    /**
     * @return list<Order>
     */
    public function findByCustomerId(CustomerId $customerId, int $page = 0, int $size = 20): array;

    public function countByCustomerId(CustomerId $customerId): int;

    /**
     * @return list<Order>
     */
    public function findAllPaginated(int $page = 0, int $size = 20): array;

    public function countAll(): int;
}
