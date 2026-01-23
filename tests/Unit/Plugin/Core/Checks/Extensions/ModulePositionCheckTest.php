<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\ModulePositionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ModulePositionCheck::class)]
class ModulePositionCheckTest extends TestCase
{
    private ModulePositionCheck $check;

    private string $templatesPath;

    protected function setUp(): void
    {
        $this->check = new ModulePositionCheck();
        $this->templatesPath = JPATH_SITE . '/templates';

        // Clean up and recreate templates directory
        $this->removeDirectory($this->templatesPath);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->templatesPath);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.module_positions', $this->check->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->check->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->check->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('database', strtolower($result->description));
    }

    public function testRunWithNoActiveTemplateReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithObject(null);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Could not determine active template', $result->description);
    }

    public function testRunWithMissingTemplateManifestReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadObject',
                'return' => (object) [
                    'template' => 'cassiopeia',
                    'params' => '{}',
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        // Don't create the template manifest file
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Template manifest not found', $result->description);
        $this->assertStringContainsString('cassiopeia', $result->description);
    }

    public function testRunWithTemplateWithoutPositionsReturnsGood(): void
    {
        // Create template directory and manifest without positions
        $templateDir = $this->templatesPath . '/simple_template';
        mkdir($templateDir, 0777, true);

        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<extension version="4.0" type="template" client="site">
    <name>Simple Template</name>
    <version>1.0.0</version>
</extension>
XML;
        file_put_contents($templateDir . '/templateDetails.xml', $xmlContent);

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadObject',
                'return' => (object) [
                    'template' => 'simple_template',
                    'params' => '{}',
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('does not define positions', $result->description);
    }

    public function testRunWithAllModulesInValidPositionsReturnsGood(): void
    {
        // Create template with positions
        $templateDir = $this->templatesPath . '/test_template';
        mkdir($templateDir, 0777, true);

        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<extension version="4.0" type="template" client="site">
    <name>Test Template</name>
    <positions>
        <position>sidebar-left</position>
        <position>sidebar-right</position>
        <position>footer</position>
    </positions>
</extension>
XML;
        file_put_contents($templateDir . '/templateDetails.xml', $xmlContent);

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadObject',
                'return' => (object) [
                    'template' => 'test_template',
                    'params' => '{}',
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'id' => 1,
                        'title' => 'Menu Module',
                        'position' => 'sidebar-left',
                    ],
                    (object) [
                        'id' => 2,
                        'title' => 'Search Module',
                        'position' => 'footer',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('All 2 published modules', $result->description);
        $this->assertStringContainsString('valid positions', $result->description);
    }

    public function testRunWithOrphanedModulesReturnsWarning(): void
    {
        // Create template with limited positions
        $templateDir = $this->templatesPath . '/limited_template';
        mkdir($templateDir, 0777, true);

        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<extension version="4.0" type="template" client="site">
    <name>Limited Template</name>
    <positions>
        <position>header</position>
        <position>footer</position>
    </positions>
</extension>
XML;
        file_put_contents($templateDir . '/templateDetails.xml', $xmlContent);

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadObject',
                'return' => (object) [
                    'template' => 'limited_template',
                    'params' => '{}',
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'id' => 1,
                        'title' => 'Header Module',
                        'position' => 'header',
                    ],
                    (object) [
                        'id' => 2,
                        'title' => 'Sidebar Module',
                        'position' => 'sidebar-left',
                    ], // Not in template!
                    (object) [
                        'id' => 3,
                        'title' => 'Banner Module',
                        'position' => 'banner',
                    ], // Not in template!
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('2 published module(s)', $result->description);
        $this->assertStringContainsString('not defined in template', $result->description);
    }

    public function testRunWithNoPublishedModulesReturnsGood(): void
    {
        // Create template with positions
        $templateDir = $this->templatesPath . '/empty_template';
        mkdir($templateDir, 0777, true);

        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<extension version="4.0" type="template" client="site">
    <name>Empty Template</name>
    <positions>
        <position>main</position>
    </positions>
</extension>
XML;
        file_put_contents($templateDir . '/templateDetails.xml', $xmlContent);

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadObject',
                'return' => (object) [
                    'template' => 'empty_template',
                    'params' => '{}',
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ], // No published modules
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('All 0 published modules', $result->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    /**
     * Recursively remove a directory
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
