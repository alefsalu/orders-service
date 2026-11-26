<?php

declare(strict_types=1);

namespace App\Tests\Docs;

use PHPUnit\Framework\TestCase;

final class DocumentationTest extends TestCase
{
    public function testPublicDocumentationAndAdrsExist(): void
    {
        $base = dirname(__DIR__, 2);
        self::assertFileExists($base . '/README.md');
        self::assertFileExists($base . '/docs/architecture.md');
        self::assertFileExists($base . '/SECURITY.md');
        self::assertFileExists($base . '/CONTRIBUTING.md');
        self::assertFileExists($base . '/LICENSE');
        self::assertFileExists($base . '/docs/adr/0001-hexagonal-architecture-with-pure-domain.md');
        self::assertFileExists($base . '/docs/adr/0002-doctrine-xml-mapping-external-to-domain.md');
        self::assertFileExists($base . '/docs/adr/0003-transactional-outbox-with-rabbitmq-amqp.md');
        self::assertFileExists($base . '/docs/adr/0004-cqrs-light-and-mongodb-read-model-projections.md');
    }
}
