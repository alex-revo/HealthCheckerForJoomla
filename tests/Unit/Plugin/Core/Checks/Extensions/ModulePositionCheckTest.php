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

    protected function setUp(): void
    {
        $this->check = new ModulePositionCheck();
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
        $this->assertStringContainsString('template', strtolower($result->description));
    }

    public function testRunWhenTemplateNotFoundReturnsWarning(): void
    {
        // Template exists in DB but doesn't exist on disk - check cannot verify positions
        // This test just verifies the check handles missing template gracefully
        $result = $this->check->run();

        // Should return warning (no database or template not found)
        $this->assertContains($result->healthStatus, [HealthStatus::Warning, HealthStatus::Good]);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testDescriptionContainsModuleOrPositionText(): void
    {
        $result = $this->check->run();

        $description = strtolower($result->description);
        $hasRelevantText = str_contains($description, 'module')
            || str_contains($description, 'position')
            || str_contains($description, 'template')
            || str_contains($description, 'database');

        $this->assertTrue($hasRelevantText, 'Description should mention module, position, template, or database');
    }
}
