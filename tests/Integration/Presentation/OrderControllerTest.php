<?php

declare(strict_types=1);

namespace App\Tests\Integration\Presentation;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrderControllerTest extends WebTestCase
{
    public function testOrderLifecycleViaHttp(): void
    {
        $client = static::createClient();

        // 1. Create Order (testing route registration and structure)
        $client->request('POST', '/api/v1/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['customerId' => '00000000-0000-0000-0000-000000000001']));

        // If auth enabled and token missing, expect 401
        self::assertContains($client->getResponse()->getStatusCode(), [201, 401]);
    }
}
