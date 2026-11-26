<?php

declare(strict_types=1);

namespace App\Tests\Integration\Monitoring;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthAndMetricsTest extends WebTestCase
{
    public function testLivenessProbeReturnsUp(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health/live');

        self::assertResponseIsSuccessful();
        $body = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('UP', $body['status']);
    }

    public function testMetricsEndpointExposesPrometheusFormat(): void
    {
        $client = static::createClient();
        $client->request('GET', '/metrics');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('php_orders_total', (string) $client->getResponse()->getContent());
        self::assertStringContainsString('http_requests_total', (string) $client->getResponse()->getContent());
    }
}
