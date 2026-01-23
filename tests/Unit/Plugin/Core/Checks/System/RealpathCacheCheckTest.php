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

    protected function setUp(): void
    {
        $this->check = new RealpathCacheCheck();
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
}
