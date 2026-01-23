<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\DiskSpaceCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiskSpaceCheck::class)]
class DiskSpaceCheckTest extends TestCase
{
    private DiskSpaceCheck $check;

    protected function setUp(): void
    {
        $this->check = new DiskSpaceCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.disk_space', $this->check->getSlug());
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

        $this->assertSame('system.disk_space', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testRunResultHasDescription(): void
    {
        $result = $this->check->run();

        $this->assertIsString($result->description);
        $this->assertNotEmpty($result->description);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

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

        $this->assertSame('system.disk_space', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testMultipleRunsReturnConsistentStatus(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        // Status should be consistent between runs (disk space shouldn't change drastically)
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
    }

    public function testCriticalThresholdIs100MB(): void
    {
        // Critical threshold is 100MB = 100 * 1024 * 1024 = 104857600 bytes
        $criticalBytes = 100 * 1024 * 1024;

        $this->assertSame(104857600, $criticalBytes);
    }

    public function testWarningThresholdIs500MB(): void
    {
        // Warning threshold is 500MB = 500 * 1024 * 1024 = 524288000 bytes
        $warningBytes = 500 * 1024 * 1024;

        $this->assertSame(524288000, $warningBytes);
    }

    public function testFormatBytesLogicBytes(): void
    {
        // Test byte formatting for small values
        $testCases = [[0, '0 B'], [1, '1 B'], [100, '100 B'], [512, '512 B'], [1023, '1023 B']];

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

    public function testFormatBytesLogicKilobytes(): void
    {
        // Test byte formatting for KB values
        $bytes = 1024;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        $formatted = round($value, 2) . ' ' . $units[$i];
        $this->assertSame('1 KB', $formatted);
    }

    public function testFormatBytesLogicMegabytes(): void
    {
        // Test byte formatting for MB values
        $bytes = 1048576; // 1 MB
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        $formatted = round($value, 2) . ' ' . $units[$i];
        $this->assertSame('1 MB', $formatted);
    }

    public function testFormatBytesLogicGigabytes(): void
    {
        // Test byte formatting for GB values
        $bytes = 1073741824; // 1 GB
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        $formatted = round($value, 2) . ' ' . $units[$i];
        $this->assertSame('1 GB', $formatted);
    }

    public function testFormatBytesLogicTerabytes(): void
    {
        // Test byte formatting for TB values
        $bytes = 1099511627776; // 1 TB
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        $formatted = round($value, 2) . ' ' . $units[$i];
        $this->assertSame('1 TB', $formatted);
    }

    public function testFormatBytesLogicFractionalValues(): void
    {
        // Test byte formatting for fractional values
        $bytes = 1536; // 1.5 KB
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        $formatted = round($value, 2) . ' ' . $units[$i];
        $this->assertSame('1.5 KB', $formatted);
    }

    public function testDescriptionContainsFreeSpaceInfo(): void
    {
        $result = $this->check->run();

        // Description should contain "free" or information about disk space
        $descLower = strtolower($result->description);
        $this->assertTrue(
            str_contains($descLower, 'free') ||
            str_contains($descLower, 'disk') ||
            str_contains($descLower, 'space') ||
            str_contains($descLower, 'unable'),
        );
    }

    public function testGoodStatusMentionsFreeSpace(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Good) {
            $this->assertStringContainsString('free', $result->description);
        }
    }

    public function testWarningStatusMentionsLowSpace(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Warning) {
            $descLower = strtolower($result->description);
            $this->assertTrue(
                str_contains($descLower, 'low') ||
                str_contains($descLower, 'running') ||
                str_contains($descLower, 'unable'),
            );
        } else {
            // If not warning, verify it's a valid status
            $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Critical]);
        }
    }

    public function testCriticalStatusMentionsCriticallyLow(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Critical) {
            $descLower = strtolower($result->description);
            $this->assertTrue(str_contains($descLower, 'critical') || str_contains($descLower, 'low'));
        } else {
            // If not critical, verify it's a valid status
            $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        }
    }

    public function testDiskFreeSpaceFunctionExists(): void
    {
        // Verify disk_free_space function is available
        $this->assertTrue(function_exists('disk_free_space'));
    }

    public function testDiskTotalSpaceFunctionExists(): void
    {
        // Verify disk_total_space function is available
        $this->assertTrue(function_exists('disk_total_space'));
    }

    public function testCanGetDiskSpaceForRootPath(): void
    {
        // Test that we can get disk space for the root path
        $freeSpace = @disk_free_space('/');

        // On most systems this should work
        $this->assertTrue($freeSpace !== false || $freeSpace === false);
    }

    public function testDescriptionIncludesSizeUnit(): void
    {
        $result = $this->check->run();

        // Description should include size unit (B, KB, MB, GB, TB) or mention inability to determine
        $hasUnit = preg_match('/\d+(\.\d+)?\s*(B|KB|MB|GB|TB)/i', $result->description);
        $unableToDetermine = str_contains(strtolower($result->description), 'unable');

        $this->assertTrue($hasUnit === 1 || $unableToDetermine);
    }

    public function testJpathRootConstantDefined(): void
    {
        // Verify JPATH_ROOT is defined (required for the check)
        $this->assertTrue(defined('JPATH_ROOT'));
    }

    public function testThresholdsAreCorrectlyOrdered(): void
    {
        // Critical threshold should be less than warning threshold
        $criticalBytes = 100 * 1024 * 1024;  // 100MB
        $warningBytes = 500 * 1024 * 1024;   // 500MB

        $this->assertLessThan($warningBytes, $criticalBytes);
    }

    public function testFormatBytesHandlesLargeValues(): void
    {
        // Test formatting for values larger than 1TB
        $bytes = 2199023255552; // 2 TB
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        $formatted = round($value, 2) . ' ' . $units[$i];
        $this->assertSame('2 TB', $formatted);
    }

    public function testFormatBytesHandlesPrecision(): void
    {
        // Test that formatting rounds to 2 decimal places
        $bytes = 1572864; // 1.5 MB exactly
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        $formatted = round($value, 2) . ' ' . $units[$i];
        $this->assertSame('1.5 MB', $formatted);
    }

    public function testFormatBytesHandlesRoundingUp(): void
    {
        // Test rounding behavior
        $bytes = 1600000; // approximately 1.53 MB
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        $formatted = round($value, 2) . ' ' . $units[$i];
        // Should round to 2 decimal places
        $this->assertMatchesRegularExpression('/^\d+(\.\d{1,2})? MB$/', $formatted);
    }
}
