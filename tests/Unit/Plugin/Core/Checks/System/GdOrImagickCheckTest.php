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
}
