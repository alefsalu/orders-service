<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

use Doctrine\DBAL\Connection;
use MongoDB\Client;

class HealthCheckService
{
    public function __construct(
        private readonly ?Connection $dbConnection = null,
        private readonly ?Client $mongoClient = null,
        private readonly string $rabbitHost = 'rabbitmq',
        private readonly int $rabbitPort = 5672,
    ) {
    }

    /**
     * @return array{status: string, checks: array<string, string>}
     */
    public function checkReadiness(): array
    {
        $checks = [
            'mysql' => $this->checkMysql(),
            'mongodb' => $this->checkMongo(),
            'rabbitmq' => $this->checkRabbitMq(),
        ];

        $isHealthy = !in_array('DOWN', $checks, true);

        return [
            'status' => $isHealthy ? 'UP' : 'DOWN',
            'checks' => $checks,
        ];
    }

    private function checkMysql(): string
    {
        if ($this->dbConnection === null) {
            return 'UP';
        }

        try {
            $this->dbConnection->executeQuery('SELECT 1');

            return 'UP';
        } catch (\Throwable) {
            return 'DOWN';
        }
    }

    private function checkMongo(): string
    {
        if ($this->mongoClient === null) {
            return 'UP';
        }

        try {
            $this->mongoClient->listDatabases();

            return 'UP';
        } catch (\Throwable) {
            return 'DOWN';
        }
    }

    private function checkRabbitMq(): string
    {
        try {
            $fp = @fsockopen($this->rabbitHost, $this->rabbitPort, $errno, $errstr, 1.0);
            if ($fp !== false) {
                fclose($fp);

                return 'UP';
            }

            return 'DOWN';
        } catch (\Throwable) {
            return 'DOWN';
        }
    }
}
