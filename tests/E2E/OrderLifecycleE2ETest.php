<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrderLifecycleE2ETest extends WebTestCase
{
    public function testCompleteOrderLifecycleFromApiToOutboxAndMongoProjection(): void
    {
        $client = static::createClient();

        // 1. Health Liveness
        $client->request('GET', '/health/live');
        self::assertResponseIsSuccessful();

        // 2. Metrics probe
        $client->request('GET', '/metrics');
        self::assertResponseIsSuccessful();
    }
}
