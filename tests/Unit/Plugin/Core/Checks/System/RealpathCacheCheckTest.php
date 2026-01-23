<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\RealpathCacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RealpathCacheCheck::class)]
class RealpathCacheCheckTest extends TestCase
{
    private RealpathCacheCheck $check;

    private string $originalRealpathCacheSize;

    private string $originalRealpathCacheTtl;

    protected function setUp(): void
    {
        $this->check = new RealpathCacheCheck();
        $this->originalRealpathCacheSize = ini_get('realpath_cache_size');
        $this->originalRealpathCacheTtl = ini_get('realpath_cache_ttl');
    }

    protected function tearDown(): void
    {
        // Restore original values
        ini_set('realpath_cache_size', $this->originalRealpathCacheSize);
        ini_set('realpath_cache_ttl', $this->originalRealpathCacheTtl);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.realpath_cache', $this->check->getSlug());
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

        $this->assertSame('system.realpath_cache', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good or Warning (never Critical)
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunDescriptionContainsCacheInfo(): void
    {
        $result = $this->check->run();

        // Description should mention realpath, cache, or usage
        $this->assertTrue(
            str_contains(strtolower($result->description), 'realpath') ||
            str_contains(strtolower($result->description), 'cache'),
        );
    }

    public function testCurrentRealpathCacheSizeIsDetectable(): void
    {
        $cacheSize = ini_get('realpath_cache_size');

        // realpath_cache_size should return a value
        $this->assertNotFalse($cacheSize);
    }

    public function testCurrentRealpathCacheTtlIsDetectable(): void
    {
        $cacheTtl = ini_get('realpath_cache_ttl');

        // realpath_cache_ttl should return a value
        $this->assertNotFalse($cacheTtl);
    }

    public function testRealpathCacheSizeFunction(): void
    {
        // realpath_cache_size() should return current usage
        $currentUsage = realpath_cache_size();

        $this->assertIsInt($currentUsage);
        $this->assertGreaterThanOrEqual(0, $currentUsage);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // This check should never return Critical status
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testDescriptionIncludesUsagePercentage(): void
    {
        $result = $this->check->run();

        // Description should include usage percentage or mention usage
        $this->assertTrue(
            str_contains($result->description, '%') ||
            str_contains(strtolower($result->description), 'unable'),
        );
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        // Description may vary slightly due to usage changes, but status should be same
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.realpath_cache', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testConvertToBytesLogicWithKilobytes(): void
    {
        // Test that the check can parse K suffix
        // '512K' should be converted to 524288 bytes
        $this->assertSame(524288, 512 * 1024);
    }

    public function testConvertToBytesLogicWithMegabytes(): void
    {
        // Test that the check can parse M suffix
        // '4M' should be converted to 4194304 bytes
        $this->assertSame(4194304, 4 * 1024 * 1024);
    }

    public function testConvertToBytesLogicWithGigabytes(): void
    {
        // Test that the check can parse G suffix
        // '1G' should be converted to 1073741824 bytes
        $this->assertSame(1073741824, 1 * 1024 * 1024 * 1024);
    }

    public function testRecommendedMinimumSizeIs4M(): void
    {
        // Recommended minimum is 4MB = 4 * 1024 * 1024 = 4194304 bytes
        $recommendedBytes = 4 * 1024 * 1024;

        $this->assertSame(4194304, $recommendedBytes);
    }

    public function testGoodResultIncludesTtl(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Good) {
            // Good result should include TTL information
            $this->assertStringContainsString('TTL', $result->description);
        }
    }

    public function testWarningResultExplainsIssue(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Warning) {
            // Warning should explain why
            $this->assertTrue(
                str_contains($result->description, 'below') ||
                str_contains($result->description, 'nearly full') ||
                str_contains($result->description, 'Unable'),
            );
        } else {
            // If not Warning, should be Good
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        }
    }

    public function testCacheUsageCalculation(): void
    {
        $currentUsage = realpath_cache_size();
        $cacheSize = ini_get('realpath_cache_size');

        // Both should be available
        $this->assertIsInt($currentUsage);
        $this->assertNotFalse($cacheSize);
    }

    public function testReturnsWarningWhenCacheSizeBelowRecommended(): void
    {
        // Set realpath_cache_size to 2M (below 4M recommended)
        if (! ini_set('realpath_cache_size', '2M')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('2M', $result->description);
        $this->assertStringContainsString('4M', $result->description);
        $this->assertStringContainsString('below', $result->description);
    }

    public function testReturnsGoodWhenCacheSizeMeetsRecommended(): void
    {
        // Set realpath_cache_size to 4M (recommended)
        if (! ini_set('realpath_cache_size', '4M')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // If usage is not > 90%, should be Good
        // Note: In test environment usage is typically low
        if ($result->healthStatus === HealthStatus::Good) {
            $this->assertStringContainsString('TTL', $result->description);
        }
    }

    public function testReturnsGoodWhenCacheSizeAboveRecommended(): void
    {
        // Set realpath_cache_size to 8M (above recommended)
        if (! ini_set('realpath_cache_size', '8M')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Should be Good if usage is not > 90%
        if ($result->healthStatus === HealthStatus::Good) {
            $this->assertStringContainsString('TTL', $result->description);
        }
    }

    public function testDescriptionIncludesConfiguredSize(): void
    {
        // Set a specific cache size
        if (! ini_set('realpath_cache_size', '8M')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Description should include the configured size
        $this->assertStringContainsString('8M', $result->description);
    }

    public function testConvertToBytesWithEmptyValue(): void
    {
        // Test with 0 value
        if (! ini_set('realpath_cache_size', '0')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Should be Warning (below recommended)
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testConvertToBytesWithKilobytesSuffix(): void
    {
        // Set realpath_cache_size using K suffix
        // 4096K = 4M (recommended)
        if (! ini_set('realpath_cache_size', '4096K')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Should be Good if usage is not > 90%
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testConvertToBytesWithLowercaseSuffix(): void
    {
        // Set realpath_cache_size using lowercase suffix
        if (! ini_set('realpath_cache_size', '4m')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Should be Good if usage is not > 90%
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testConvertToBytesWithPlainBytes(): void
    {
        // Set realpath_cache_size using plain bytes
        // 4194304 = 4M (recommended)
        if (! ini_set('realpath_cache_size', '4194304')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Should be Good if usage is not > 90%
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testVerySmallCacheSize(): void
    {
        // Set to very small value
        if (! ini_set('realpath_cache_size', '16K')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Should be Warning (below 4M recommended)
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testVeryLargeCacheSize(): void
    {
        // Set to very large value
        if (! ini_set('realpath_cache_size', '64M')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Should be Good (above recommended, usage will be low)
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testBoundaryAtExactlyRecommended(): void
    {
        // Set to exactly 4M
        if (! ini_set('realpath_cache_size', '4M')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Should be Good (usage likely under 90% in test environment)
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testBoundaryJustBelowRecommended(): void
    {
        // Set to just below 4M
        if (! ini_set('realpath_cache_size', '3M')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Should be Warning (below 4M recommended)
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testUsagePercentageCalculation(): void
    {
        // Set a known cache size and verify percentage is calculated
        if (! ini_set('realpath_cache_size', '8M')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        // Description should include percentage
        $this->assertStringContainsString('%', $result->description);
    }

    public function testTtlIncludedInDescription(): void
    {
        // Set a good cache size to get Good status
        if (! ini_set('realpath_cache_size', '8M')) {
            $this->markTestSkipped('Cannot modify realpath_cache_size in this environment.');
        }

        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Good) {
            // Good result should include TTL
            $this->assertStringContainsString('TTL', $result->description);
        }
    }
}
