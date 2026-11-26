<?php

declare(strict_types=1);

namespace App\Tests\Integration\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CrossCuttingHttpTest extends WebTestCase
{
    public function testSecurityHeadersAndCorrelationIdPresentOnResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health/live', [], [], [
            'HTTP_X_CORRELATION_ID' => '11111111-2222-3333-4444-555555555555',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('11111111-2222-3333-4444-555555555555', $client->getResponse()->headers->get('X-Correlation-Id'));
        self::assertSame('nosniff', $client->getResponse()->headers->get('X-Content-Type-Options'));
        self::assertSame('DENY', $client->getResponse()->headers->get('X-Frame-Options'));
        self::assertSame('no-referrer', $client->getResponse()->headers->get('Referrer-Policy'));
        self::assertSame("default-src 'none'", $client->getResponse()->headers->get('Content-Security-Policy'));
    }

    public function testGeneratedCorrelationIdWhenHeaderOmitted(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health/live');

        self::assertResponseIsSuccessful();
        $correlationId = $client->getResponse()->headers->get('X-Correlation-Id');
        self::assertNotNull($correlationId);
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $correlationId);
    }
}
