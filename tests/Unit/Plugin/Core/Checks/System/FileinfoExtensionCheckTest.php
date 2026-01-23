<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\FileinfoExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileinfoExtensionCheck::class)]
class FileinfoExtensionCheckTest extends TestCase
{
    private FileinfoExtensionCheck $check;

    protected function setUp(): void
    {
        $this->check = new FileinfoExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.fileinfo_extension', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->check->getCategory());
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

    public function testRunReturnsGoodWhenFileinfoLoaded(): void
    {
        // Fileinfo is typically loaded in PHP environments
        if (! extension_loaded('fileinfo')) {
            $this->markTestSkipped('Fileinfo extension not available');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Fileinfo', $result->description);
        $this->assertStringContainsString('loaded', $result->description);
    }

    public function testRunReturnsWarningWhenFileinfoNotAvailable(): void
    {
        if (extension_loaded('fileinfo')) {
            $this->markTestSkipped('Fileinfo extension is available - cannot test warning path');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Fileinfo', $result->description);
        $this->assertStringContainsString('not loaded', $result->description);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.fileinfo_extension', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.fileinfo_extension', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // Fileinfo check should never return Critical status per documentation
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testRunReturnsValidStatusBasedOnExtensionAvailability(): void
    {
        $result = $this->check->run();

        // Based on whether fileinfo extension is loaded
        if (extension_loaded('fileinfo')) {
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        }
    }
}
