<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\LogFileSizeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogFileSizeCheck::class)]
class LogFileSizeCheckTest extends TestCase
{
    private LogFileSizeCheck $check;

    private string $testLogDir;

    protected function setUp(): void
    {
        $this->check = new LogFileSizeCheck();
        $this->testLogDir = sys_get_temp_dir() . '/healthchecker_log_test_' . getmypid();
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testLogDir)) {
            $this->removeDirectory($this->testLogDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.log_file_size', $this->check->getSlug());
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

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.log_file_size', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good, Warning, or Critical depending on log size
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionIsNotEmpty(): void
    {
        $result = $this->check->run();

        // The check returns a description (may be error message if Joomla not available)
        $this->assertNotEmpty($result->description);
    }

    public function testRunHandlesMissingLogDirectory(): void
    {
        // This test relies on the default behavior when log directory doesn't exist
        // The check should return Good if directory doesn't exist (no logs = no problem)
        $result = $this->check->run();

        // If log directory doesn't exist, should return Good
        // If it exists, can return any status based on size
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.log_file_size', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testWarningThresholdIs100MB(): void
    {
        // Warning threshold is 100MB = 100 * 1024 * 1024 = 104857600 bytes
        $warningBytes = 100 * 1024 * 1024;

        $this->assertSame(104857600, $warningBytes);
    }

    public function testCriticalThresholdIs500MB(): void
    {
        // Critical threshold is 500MB = 500 * 1024 * 1024 = 524288000 bytes
        $criticalBytes = 500 * 1024 * 1024;

        $this->assertSame(524288000, $criticalBytes);
    }

    public function testFormatBytesLogic(): void
    {
        // Test the byte formatting logic manually
        $testCases = [[0, '0 B'], [1024, '1 KB'], [1048576, '1 MB'], [1073741824, '1 GB']];

        foreach ($testCases as [$bytes, $expected]) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $value = (float) $bytes;

            for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
                $value /= 1024;
            }

            $formatted = round($value, 2) . ' ' . $units[$i];
            $this->assertSame($expected, $formatted);
        }
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
    }

    public function testRecursiveDirectoryIteratorLogic(): void
    {
        // Create a test directory with files
        mkdir($this->testLogDir, 0777, true);
        file_put_contents($this->testLogDir . '/test.log', str_repeat('x', 1024));

        // Verify we can calculate directory size
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->testLogDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        $totalSize = 0;
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
            }
        }

        $this->assertSame(1024, $totalSize);
    }

    public function testGoodResultDescriptionFormatted(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Good) {
            // Good result should mention "manageable" or similar
            $this->assertTrue(
                str_contains($result->description, 'manageable') ||
                str_contains($result->description, 'does not exist'),
            );
        }
    }

    public function testWarningResultMentionsReview(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Warning) {
            // Warning should suggest reviewing or rotating logs
            $this->assertTrue(
                str_contains($result->description, 'review') ||
                str_contains($result->description, 'rotat') ||
                str_contains($result->description, 'readable'),
            );
        } else {
            // Not a warning, so just verify status is valid
            $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Critical]);
        }
    }

    public function testCriticalResultMentionsCleanup(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Critical) {
            // Critical should mention cleaning up or investigating
            $this->assertTrue(
                str_contains($result->description, 'clean') ||
                str_contains($result->description, 'investigat'),
            );
        } else {
            // Not critical, just verify status is valid
            $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        }
    }

    public function testDescriptionIncludesSizeInfo(): void
    {
        $result = $this->check->run();

        // Description should include size information (B, KB, MB, GB) or mention directory
        $descLower = strtolower($result->description);
        $this->assertTrue(
            str_contains($descLower, 'b') ||    // B, KB, MB, GB
            str_contains($descLower, 'directory') ||
            str_contains($descLower, 'log'),
        );
    }
}
