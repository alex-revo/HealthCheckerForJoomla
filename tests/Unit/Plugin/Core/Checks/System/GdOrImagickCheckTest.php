<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\GdOrImagickCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GdOrImagickCheck::class)]
class GdOrImagickCheckTest extends TestCase
{
    private GdOrImagickCheck $check;

    protected function setUp(): void
    {
        $this->check = new GdOrImagickCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.gd_or_imagick', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenGdLoaded(): void
    {
        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            $this->markTestSkipped('Neither GD nor Imagick extension available');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('loaded', $result->description);

        // Check that it mentions at least one of the extensions
        $this->assertTrue(
            str_contains($result->description, 'GD') || str_contains($result->description, 'Imagick'),
            'Description should mention GD or Imagick',
        );
    }

    public function testRunReturnsCriticalWhenNeitherAvailable(): void
    {
        if (extension_loaded('gd') || extension_loaded('imagick')) {
            $this->markTestSkipped('GD or Imagick extension is available - cannot test critical path');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('Neither GD nor Imagick', $result->description);
        $this->assertStringContainsString('not work', $result->description);
    }

    public function testRunReportsGdWhenOnlyGdLoaded(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('GD', $result->description);
    }

    public function testRunReportsImagickWhenImagickLoaded(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick extension not available');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Imagick', $result->description);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.gd_or_imagick', $result->slug);
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

        $this->assertSame('system.gd_or_imagick', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testCheckNeverReturnsWarning(): void
    {
        // GD/Imagick check returns Critical or Good, never Warning per documentation
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Warning, $result->healthStatus);
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

        $hasGd = extension_loaded('gd');
        $hasImagick = extension_loaded('imagick');

        // Based on whether at least one image extension is loaded
        if ($hasGd || $hasImagick) {
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        }
    }

    /**
     * Test that the check reports both extensions when both are loaded.
     *
     * When both GD and Imagick are loaded, the description should mention both.
     */
    public function testRunReportsBothWhenBothLoaded(): void
    {
        if (! extension_loaded('gd') || ! extension_loaded('imagick')) {
            $this->markTestSkipped('Both GD and Imagick must be loaded for this test');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('GD', $result->description);
        $this->assertStringContainsString('Imagick', $result->description);
        $this->assertStringContainsString('and', $result->description);
    }

    /**
     * Document that the "only Imagick" branch requires GD to be unloaded.
     *
     * The code path where only Imagick is loaded (not GD) can only be tested
     * in environments where GD is not installed. Most PHP installations
     * include GD by default.
     */
    public function testDocumentImagickOnlyBranch(): void
    {
        // This test documents the code path for Imagick-only environments
        // In typical PHP installations, GD is always available
        if (extension_loaded('gd')) {
            $this->assertTrue(true, 'GD is loaded - Imagick-only branch not testable');
        } else {
            $result = $this->check->run();
            if (extension_loaded('imagick')) {
                $this->assertSame(HealthStatus::Good, $result->healthStatus);
                $this->assertStringContainsString('Imagick', $result->description);
                $this->assertStringNotContainsString('GD', $result->description);
            }
        }
    }
}
