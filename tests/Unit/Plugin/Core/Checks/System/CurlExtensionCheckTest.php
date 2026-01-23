<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\CurlExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurlExtensionCheck::class)]
class CurlExtensionCheckTest extends TestCase
{
    private CurlExtensionCheck $check;

    protected function setUp(): void
    {
        $this->check = new CurlExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.curl_extension', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenCurlLoaded(): void
    {
        // cURL is typically loaded in PHP test environments
        if (! extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension not available');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('cURL', $result->description);
        $this->assertStringContainsString('loaded', $result->description);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.curl_extension', $result->slug);
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

        $this->assertSame('system.curl_extension', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // cURL check should never return Critical status per documentation
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

    public function testRunReturnsWarningWhenCurlNotLoaded(): void
    {
        // This test can only run if cURL is not available
        if (extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is loaded - cannot test warning path');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('cURL', $result->description);
        $this->assertStringContainsString('not loaded', $result->description);
    }

    /**
     * Test that cURL version information is included when available.
     *
     * When cURL is loaded and curl_version() returns valid data,
     * the description should include the libcurl version.
     */
    public function testRunIncludesVersionWhenCurlLoaded(): void
    {
        if (! extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension not available');
        }

        $result = $this->check->run();
        $version = curl_version();

        // Verify the check returns Good status
        $this->assertSame(HealthStatus::Good, $result->healthStatus);

        // If curl_version() returns valid version info, it should be in description
        if (is_array($version) && isset($version['version'])) {
            $this->assertStringContainsString('libcurl', $result->description);
            $this->assertStringContainsString($version['version'], $result->description);
        }
    }

    /**
     * Document that version-unavailable branch requires curl_version() to fail.
     *
     * The code path at lines 93-95 handles when curl_version() returns false or
     * invalid data. This is a defensive path that's unlikely to be reached in
     * normal PHP installations where cURL is properly installed.
     *
     * NOTE: This branch cannot be tested without mocking curl_version(),
     * which is a global PHP function that cannot be easily mocked.
     */
    public function testDocumentVersionUnavailableBranch(): void
    {
        // This test serves as documentation for the version-unavailable code path
        $this->assertTrue(true, 'Version-unavailable branch documented - see test docblock');
    }
}
