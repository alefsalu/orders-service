<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class LayersTest extends TestCase
{
    public function testDomainHasNoDependenciesOnInfrastructureOrFramework(): void
    {
        // Static file scanner to ensure src/Domain does not import Symfony, Doctrine, or Infrastructure
        $domainDir = dirname(__DIR__, 2) . '/src/Domain';
        if (!is_dir($domainDir)) {
            self::markTestSkipped('Domain directory does not exist yet');
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($domainDir));
        $violations = [];

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = (string) file_get_contents($file->getPathname());
            $forbiddenPatterns = [
                'use Symfony\\',
                'use Doctrine\\',
                'use App\\Infrastructure\\',
                'use App\\Presentation\\',
                'use App\\Application\\',
                '#[ORM\\',
                '#[Route',
            ];

            foreach ($forbiddenPatterns as $pattern) {
                if (str_contains($content, $pattern)) {
                    $violations[] = sprintf('%s contains forbidden import or attribute "%s"', $file->getFilename(), $pattern);
                }
            }
        }

        self::assertEmpty($violations, "Domain purity violations found:\n" . implode("\n", $violations));
    }
}
