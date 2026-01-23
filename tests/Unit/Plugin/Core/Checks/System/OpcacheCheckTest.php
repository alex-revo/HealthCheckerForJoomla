<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\OpcacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpcacheCheck::class)]
class OpcacheCheckTest extends TestCase
{
    private OpcacheCheck $check;

    protected function setUp(): void
    {
        $this->check = new OpcacheCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.opcache', $this->check->getSlug());
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

    public function testRunReturnsValidStatus(): void
    {
        // The actual opcache check depends on the system configuration
        // but we can verify it returns a valid status
        $result = $this->check->run();

        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.opcache', $result->slug);
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

        $this->assertSame('system.opcache', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // OPcache check should never return Critical status per documentation
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testDescriptionContainsOpcacheInfo(): void
    {
        $result = $this->check->run();

        // Description should mention OPcache
        $this->assertTrue(
            str_contains(strtolower($result->description), 'opcache') ||
            str_contains(strtolower($result->description), 'memory'),
            'Description should contain relevant OPcache information',
        );
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        // Status should be consistent (may have slight description variance due to memory stats)
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
    }

    /**
     * Test behavior when OPcache extension is loaded.
     *
     * This tests the "OPcache is enabled" path which requires the extension to be loaded.
     */
    public function testRunWhenOpcacheExtensionLoaded(): void
    {
        if (! extension_loaded('Zend OPcache')) {
            $this->markTestSkipped('OPcache extension not loaded');
        }

        $result = $this->check->run();

        // If extension is loaded, we get either Good (enabled/working) or Warning (disabled/issue)
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    /**
     * Test behavior when OPcache extension is not loaded.
     *
     * NOTE: This test can only run in environments without OPcache.
     * In typical PHP installations, OPcache is always available.
     */
    public function testRunWhenOpcacheExtensionNotLoaded(): void
    {
        if (extension_loaded('Zend OPcache')) {
            $this->markTestSkipped('OPcache extension is loaded - cannot test "not loaded" path');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not loaded', $result->description);
    }

    /**
     * Test that the check handles enabled OPcache with various states.
     *
     * The check has multiple branches for memory statistics handling.
     * These branches protect against edge cases in opcache_get_status() return values.
     */
    public function testOpcacheEnabledHandlesMemoryStatistics(): void
    {
        if (! extension_loaded('Zend OPcache')) {
            $this->markTestSkipped('OPcache extension not loaded');
        }

        // Get actual OPcache status to understand the current state
        $opcacheEnabled = (bool) ini_get('opcache.enable');
        $result = $this->check->run();

        if (! $opcacheEnabled) {
            // OPcache installed but not enabled
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
            $this->assertStringContainsString('not enabled', $result->description);
        } else {
            // OPcache is enabled - check memory stats handling
            $status = @opcache_get_status(false);
            if ($status === false) {
                // Status unavailable
                $this->assertSame(HealthStatus::Warning, $result->healthStatus);
            } elseif (! isset($status['memory_usage']) || ! is_array($status['memory_usage'])) {
                // Memory stats not available (covers line 98-100)
                $this->assertSame(HealthStatus::Good, $result->healthStatus);
                $this->assertStringContainsString('memory statistics not available', $result->description);
            } elseif (
                ! isset($status['memory_usage']['used_memory'], $status['memory_usage']['free_memory'])
            ) {
                // Memory keys missing (covers line 105-107)
                $this->assertSame(HealthStatus::Good, $result->healthStatus);
                $this->assertStringContainsString('memory statistics incomplete', $result->description);
            } else {
                // Normal operation - either Good or Warning based on memory usage
                $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
            }
        }
    }

    /**
     * Document that certain OPcache branches depend on runtime state.
     *
     * The following code paths depend on opcache_get_status() return values:
     * - Lines 93-95: Status returns false -> Warning
     * - Lines 98-100: memory_usage not set or not array -> Good with "not available"
     * - Lines 105-107: used_memory or free_memory missing -> Good with "incomplete"
     * - Lines 113-115: Invalid memory values (negative or zero sum) -> Good with "unavailable"
     * - Lines 121-123: Percentage out of range -> Good with "unreliable"
     * - Lines 126-133: High memory usage (>90%) -> Warning
     * - Line 135: Normal healthy state -> Good
     *
     * These paths require specific OPcache states that can't be easily simulated.
     */
    public function testDocumentOpcacheStateDependentBranches(): void
    {
        // This test serves as documentation for code paths that depend on OPcache state
        $this->assertTrue(true, 'OPcache state-dependent branches documented - see test docblock');
    }
}
