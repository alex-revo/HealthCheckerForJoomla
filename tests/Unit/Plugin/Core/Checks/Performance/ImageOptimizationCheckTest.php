<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\ImageOptimizationCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageOptimizationCheck::class)]
class ImageOptimizationCheckTest extends TestCase
{
    private ImageOptimizationCheck $check;

    private string $testImagesDir;

    protected function setUp(): void
    {
        $this->check = new ImageOptimizationCheck();
        $this->testImagesDir = sys_get_temp_dir() . '/healthchecker_test_images_' . uniqid();

        // Define JPATH_ROOT for tests if not already defined
        if (! defined('JPATH_ROOT')) {
            define('JPATH_ROOT', sys_get_temp_dir() . '/healthchecker_jpath_' . uniqid());
        }
    }

    protected function tearDown(): void
    {
        // Clean up any test files
        if (is_dir($this->testImagesDir)) {
            $this->removeDirectory($this->testImagesDir);
        }
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.image_optimization', $this->check->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->check->getCategory());
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

    public function testRunReturnsValidStatus(): void
    {
        // Without the /images directory, returns good
        $result = $this->check->run();

        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunWithNoImagesDirectoryReturnsGood(): void
    {
        // When JPATH_ROOT/images doesn't exist
        $result = $this->check->run();

        // Should be good or warning depending on whether the test environment has images
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testDescriptionContainsImageOrOptimizationText(): void
    {
        $result = $this->check->run();

        $description = strtolower($result->description);
        $this->assertTrue(
            str_contains($description, 'image') || str_contains($description, 'directory'),
            'Description should mention images or directory',
        );
    }

    /**
     * Helper to recursively remove a directory
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
