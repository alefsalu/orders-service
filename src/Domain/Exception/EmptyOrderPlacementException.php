<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class EmptyOrderPlacementException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Cannot place an order with no items.');
    }
}
