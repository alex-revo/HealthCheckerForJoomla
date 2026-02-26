<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\CoreDirectoriesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CoreDirectoriesCheck::class)]
class CoreDirectoriesCheckTest extends TestCase
{
    private CoreDirectoriesCheck $coreDirectoriesCheck;

    protected function setUp(): void
    {
        $this->coreDirectoriesCheck = new CoreDirectoriesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.core_directories', $this->coreDirectoriesCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->coreDirectoriesCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->coreDirectoriesCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->coreDirectoriesCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->coreDirectoriesCheck->run();

        $this->assertSame('system.core_directories', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->coreDirectoriesCheck->run();

        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionIsNotEmpty(): void
    {
        $healthCheckResult = $this->coreDirectoriesCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->coreDirectoriesCheck->run();

        $this->assertSame('system.core_directories', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->coreDirectoriesCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->coreDirectoriesCheck->run();
        $result2 = $this->coreDirectoriesCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testSlugFormat(): void
    {
        $slug = $this->coreDirectoriesCheck->getSlug();

        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->coreDirectoriesCheck->getCategory();

        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }

    public function testJpathRootConstantIsDefined(): void
    {
        $this->assertTrue(defined('JPATH_ROOT'));
    }

    public function testCriticalWhenDirectoryNotExist(): void
    {
        $this->assertFalse(is_dir('/this/path/does/not/exist/at/all/' . uniqid()));
    }

    public function testGoodResultConfirmsAllDirectoriesExist(): void
    {
        $healthCheckResult = $this->coreDirectoriesCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Good) {
            $this->assertStringContainsString('CORE_DIRECTORIES_GOOD', $healthCheckResult->description);
        } else {
            $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Warning, HealthStatus::Critical]);
        }
    }

    public function testCriticalResultListsMissingDirectories(): void
    {
        $healthCheckResult = $this->coreDirectoriesCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Critical) {
            $this->assertStringContainsString('CORE_DIRECTORIES_CRITICAL', $healthCheckResult->description);
        }
    }

    public function testGetDocsUrlReturnsValidUrl(): void
    {
        $url = $this->coreDirectoriesCheck->getDocsUrl();

        $this->assertStringStartsWith('https://', $url);
        $this->assertStringContainsString('CoreDirectoriesCheck', $url);
    }
}
