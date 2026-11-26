<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\ReadModel\OrderHistoryProjection;

interface OrderHistoryRepositoryInterface
{
    public function findByOrderId(string $orderId): ?OrderHistoryProjection;

    public function upsertProjection(OrderHistoryProjection $projection): void;
}
