<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum OrderStatus: string
{
    case DRAFT = 'DRAFT';
    case PLACED = 'PLACED';
    case PAID = 'PAID';
    case SHIPPED = 'SHIPPED';
    case CANCELLED = 'CANCELLED';
}
