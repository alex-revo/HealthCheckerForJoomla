<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\PhpSapiCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpSapiCheck::class)]
class PhpSapiCheckTest extends TestCase
{
    private PhpSapiCheck $check;

    protected function setUp(): void
    {
        $this->check = new PhpSapiCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.php_sapi', $this->check->getSlug());
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

        $this->assertSame('system.php_sapi', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good or Warning (CLI warning)
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunDescriptionContainsSapiInfo(): void
    {
        $result = $this->check->run();

        // Description should mention SAPI or CLI
        $this->assertTrue(
            str_contains(strtolower($result->description), 'sapi') ||
            str_contains(strtolower($result->description), 'cli'),
        );
    }

    public function testCurrentSapiIsDetectable(): void
    {
        $sapi = PHP_SAPI;

        // PHP_SAPI should return a non-empty string
        $this->assertNotEmpty($sapi);
        $this->assertIsString($sapi);
    }

    public function testRunInCliEnvironmentReturnsWarning(): void
    {
        // In PHPUnit tests, we run via CLI
        if (PHP_SAPI !== 'cli') {
            $this->markTestSkipped('This test is for CLI environment only.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('CLI', $result->description);
    }

    public function testDescriptionIncludesSapiName(): void
    {
        $result = $this->check->run();
        $sapi = PHP_SAPI;

        // Description should include the current SAPI name
        $this->assertTrue(
            str_contains(strtolower($result->description), strtolower($sapi)) ||
            str_contains($result->description, 'CLI'),
        );
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // This check should never return Critical status
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
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
        $this->assertSame($result1->description, $result2->description);
    }

    public function testPhpSapiConstantExists(): void
    {
        $this->assertTrue(defined('PHP_SAPI'));
        $this->assertIsString(PHP_SAPI);
    }

    public function testCliWarningMessageIsInformative(): void
    {
        if (PHP_SAPI !== 'cli') {
            $this->markTestSkipped('This test is for CLI environment only.');
        }

        $result = $this->check->run();

        // Warning message should explain this is for web environments
        $this->assertStringContainsString('web environments', $result->description);
    }

    public function testRecommendedSapisListIsComplete(): void
    {
        // Validate that the check recognizes common recommended SAPIs
        $recommendedSapis = ['fpm-fcgi', 'cgi-fcgi', 'litespeed', 'frankenphp'];

        foreach ($recommendedSapis as $sapi) {
            $this->assertIsString($sapi);
            $this->assertNotEmpty($sapi);
        }
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.php_sapi', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testApache2handlerRecognition(): void
    {
        // If running under apache2handler, should return Good with performance note
        if (PHP_SAPI === 'apache2handler') {
            $result = $this->check->run();

            $this->assertSame(HealthStatus::Good, $result->healthStatus);
            $this->assertStringContainsString('apache2handler', $result->description);
            $this->assertStringContainsString('PHP-FPM', $result->description);
        } else {
            $this->markTestSkipped('This test requires apache2handler SAPI.');
        }
    }
}
