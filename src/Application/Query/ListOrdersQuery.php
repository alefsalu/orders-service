<?php

declare(strict_types=1);

namespace App\Application\Query;

final readonly class ListOrdersQuery
{
    public function __construct(
        public ?string $customerId = null,
        public int $page = 0,
        public int $size = 20,
    ) {
    }
}
