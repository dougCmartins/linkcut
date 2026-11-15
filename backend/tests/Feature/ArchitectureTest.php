<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Guards the modular monolith (docs/adr/0001-monolito-modular-e-fronteiras.md):
 * - a domain never imports another domain; flows that cross domains live in Domain\Orchestrator;
 * - the shared kernel (Tenant: the tenant context; Shared: helpers without business rules) may be used by everyone;
 * - nothing outside the Orchestrator imports the Orchestrator (no cycles through it).
 * Test files are left out: fixtures may build data of other domains.
 */
final class ArchitectureTest extends TestCase
{
    private const SHARED_KERNEL = ['Tenant', 'Shared'];

    public function test_domains_only_import_themselves_and_the_shared_kernel(): void
    {
        // Arrange
        $violations = [];

        // Act
        foreach ($this->domainImports() as [$file, $domain, $imported]) {
            $isAllowed = $imported === $domain || in_array($imported, self::SHARED_KERNEL, true) || $domain === 'Orchestrator';
            if (! $isAllowed) {
                $violations[] = "{$file} ({$domain}) imports Domain\\{$imported}";
            }
        }

        // Assert
        $this->assertSame([], $violations, "A domain may not import another domain; cross domains in Domain\\Orchestrator.\n");
    }

    public function test_the_shared_kernel_depends_on_no_business_domain(): void
    {
        // Arrange
        $violations = [];

        // Act
        foreach ($this->domainImports() as [$file, $domain, $imported]) {
            if (in_array($domain, self::SHARED_KERNEL, true) && ! in_array($imported, self::SHARED_KERNEL, true)) {
                $violations[] = "{$file} ({$domain}) imports Domain\\{$imported}";
            }
        }

        // Assert
        $this->assertSame([], $violations, "The shared kernel must not know any business domain.\n");
    }

    /**
     * Every `use Domain\X\...` of the non-test PHP files under src/Domain.
     *
     * @return list<array{0: string, 1: string, 2: string}> [file, its domain, imported domain]
     */
    private function domainImports(): array
    {
        $root = dirname(__DIR__, 2).'/src/Domain';
        $imports = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = $file->getPathname();
            $relative = substr($path, strlen($root) + 1);
            if ($file->getExtension() !== 'php' || str_contains($relative, '/Tests/')) {
                continue;
            }
            $domain = explode('/', $relative)[0];
            preg_match_all('/^use Domain\\\\(\w+)\\\\/m', (string) file_get_contents($path), $matches);
            foreach ($matches[1] as $imported) {
                $imports[] = ["src/Domain/{$relative}", $domain, $imported];
            }
        }

        return $imports;
    }
}
