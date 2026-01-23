<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\ExifExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExifExtensionCheck::class)]
class ExifExtensionCheckTest extends TestCase
{
    private ExifExtensionCheck $check;

    protected function setUp(): void
    {
        $this->check = new ExifExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.exif_extension', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenExifFunctionExists(): void
    {
        // EXIF extension check uses function_exists('exif_read_data')
        if (! \function_exists('exif_read_data')) {
            $this->markTestSkipped('EXIF extension not available');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('EXIF', $result->description);
        $this->assertStringContainsString('installed', $result->description);
    }

    public function testRunReturnsWarningWhenExifNotAvailable(): void
    {
        // EXIF extension check uses function_exists('exif_read_data')
        if (\function_exists('exif_read_data')) {
            $this->markTestSkipped('EXIF extension is available - cannot test warning path');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('EXIF', $result->description);
        $this->assertStringContainsString('not installed', $result->description);
    }
}
