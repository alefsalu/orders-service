<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Domain\Model\OutboxMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RabbitMqAmqpEventPublisher implements AmqpEventPublisherInterface
{
    private ?\AMQPExchange $exchange = null;
    private ?\AMQPConnection $connection = null;

    public function __construct(
        private readonly string $host = 'rabbitmq',
        private readonly int $port = 5672,
        private readonly string $user = 'guest',
        private readonly string $password = 'guest',
        private readonly string $exchangeName = 'orders.events',
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function publish(OutboxMessage $message): void
    {
        $envelope = [
            'eventId' => $message->id(),
            'eventType' => $message->eventType(),
            'occurredAt' => $message->occurredAt()->format(\DateTimeInterface::RFC3339),
            'aggregateType' => $message->aggregateType(),
            'aggregateId' => $message->aggregateId(),
            'correlationId' => $message->correlationId(),
            'payload' => $message->payload(),
        ];

        $body = json_encode($envelope, JSON_THROW_ON_ERROR);
        $routingKey = $message->eventType();

        if (class_exists(\AMQPConnection::class)) {
            $this->publishViaExtension($body, $routingKey, $message->correlationId());
        } else {
            // Emulate or log when amqp extension is not loaded in host CLI
            $this->logger->info(sprintf('[AMQP Publish] Exchange: %s, Key: %s, Body: %s', $this->exchangeName, $routingKey, $body), [
                'correlationId' => $message->correlationId(),
            ]);
        }
    }

    private function publishViaExtension(string $body, string $routingKey, string $correlationId): void
    {
        try {
            if ($this->exchange === null || $this->connection === null || !$this->connection->isConnected()) {
                $this->connection = new \AMQPConnection([
                    'host' => $this->host,
                    'port' => $this->port,
                    'login' => $this->user,
                    'password' => $this->password,
                    'vhost' => '/',
                ]);
                $this->connection->connect();

                $channel = new \AMQPChannel($this->connection);
                $channel->confirmSelect();

                $this->exchange = new \AMQPExchange($channel);
                $this->exchange->setName($this->exchangeName);
                $this->exchange->setType(AMQP_EX_TYPE_TOPIC);
                $this->exchange->setFlags(AMQP_DURABLE);
                $this->exchange->declareExchange();
            }

            $this->exchange->publish(
                $body,
                $routingKey,
                AMQP_NOPARAM,
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => 2, // persistent
                    'headers' => [
                        'X-Correlation-Id' => $correlationId,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish message to RabbitMQ: ' . $e->getMessage(), [
                'correlationId' => $correlationId,
                'exception' => $e,
            ]);
            throw new \RuntimeException('RabbitMQ publishing failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
