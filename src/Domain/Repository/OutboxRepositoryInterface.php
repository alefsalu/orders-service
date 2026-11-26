<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\OutboxMessage;

interface OutboxRepositoryInterface
{
    public function append(OutboxMessage $message): void;

    /**
     * @return list<OutboxMessage>
     */
    public function findUnpublished(int $limit = 100): array;

    public function save(OutboxMessage $message): void;
}
