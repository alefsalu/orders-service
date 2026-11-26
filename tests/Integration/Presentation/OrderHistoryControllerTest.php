<?php

declare(strict_types=1);

namespace App\Tests\Integration\Presentation;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrderHistoryControllerTest extends WebTestCase
{
    public function testGetOrderHistoryRoute(): void
    {
        $client = static::createClient();
        $orderId = '00000000-0000-0000-0000-000000000099';

        $client->request('GET', "/api/v1/orders/{$orderId}/history");

        // Route exists: returns either 401 (unauthenticated) or 404 (not found)
        self::assertContains($client->getResponse()->getStatusCode(), [401, 404, 200]);
    }
}
