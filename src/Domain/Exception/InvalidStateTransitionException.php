<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class InvalidStateTransitionException extends \DomainException
{
    public function __construct(string $fromStatus, string $toStatus)
    {
        parent::__construct(sprintf('Invalid state transition from "%s" to "%s".', $fromStatus, $toStatus));
    }
}
