<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Domain\Model\OutboxMessage;

interface AmqpEventPublisherInterface
{
    public function publish(OutboxMessage $message): void;
}
