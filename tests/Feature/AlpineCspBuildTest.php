<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ensures the JavaScript entry point uses @alpinejs/csp
 * instead of the standard alpinejs build, which requires unsafe-eval.
 */
class AlpineCspBuildTest extends TestCase
{
    private const APP_JS_PATH = __DIR__ . '/../../resources/js/app.js';

    #[Test]
    public function app_js_imports_alpinejs_csp_build(): void
    {
        $this->assertFileExists(self::APP_JS_PATH, 'resources/js/app.js must exist');

        $contents = file_get_contents(self::APP_JS_PATH);

        $this->assertStringContainsString(
            "from '@alpinejs/csp'",
            $contents,
            'app.js must import Alpine from @alpinejs/csp to avoid unsafe-eval CSP requirement'
        );
    }

    #[Test]
    public function app_js_does_not_import_standard_alpinejs(): void
    {
        $contents = file_get_contents(self::APP_JS_PATH);

        $this->assertStringNotContainsString(
            "from 'alpinejs'",
            $contents,
            'app.js must not import from the standard alpinejs package (which requires unsafe-eval)'
        );
    }

    #[Test]
    public function csp_build_package_is_in_dependencies(): void
    {
        $packageJsonPath = __DIR__ . '/../../package.json';
        $this->assertFileExists($packageJsonPath);

        $package = json_decode(file_get_contents($packageJsonPath), true);

        $this->assertArrayHasKey(
            '@alpinejs/csp',
            $package['dependencies'] ?? [],
            '@alpinejs/csp must be listed in package.json dependencies (not devDependencies)'
        );
    }
}
