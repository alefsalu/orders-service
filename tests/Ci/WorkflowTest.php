<?php

declare(strict_types=1);

namespace App\Tests\Ci;

use PHPUnit\Framework\TestCase;

final class WorkflowTest extends TestCase
{
    public function testCiWorkflowContainsRequiredJobs(): void
    {
        $yaml = (string) file_get_contents(dirname(__DIR__, 2) . '/.github/workflows/ci.yml');
        self::assertNotEmpty($yaml);
        self::assertStringContainsString('guard:', $yaml);
        self::assertStringContainsString('lint:', $yaml);
        self::assertStringContainsString('test:', $yaml);
        self::assertStringContainsString('security:', $yaml);
        self::assertStringContainsString('build-image:', $yaml);
    }
}
