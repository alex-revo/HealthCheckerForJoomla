<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\ApacheModulesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApacheModulesCheck::class)]
class ApacheModulesCheckTest extends TestCase
{
    private ApacheModulesCheck $check;

    protected function setUp(): void
    {
        $this->check = new ApacheModulesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.apache_modules', $this->check->getSlug());
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

        $this->assertSame('system.apache_modules', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // When not on Apache or function unavailable, returns Good
        // On Apache, returns Good (all modules) or Warning (missing mod_rewrite)
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunWhenNotApacheReturnsGood(): void
    {
        // When apache_get_modules() doesn't exist (non-Apache env), should return Good
        if (function_exists('apache_get_modules')) {
            $this->markTestSkipped('This test is for non-Apache environments.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Not running on Apache', $result->description);
    }

    public function testRunDescriptionContainsRelevantInfo(): void
    {
        $result = $this->check->run();

        // Description should mention Apache or modules
        $this->assertTrue(
            str_contains(strtolower($result->description), 'apache') ||
            str_contains(strtolower($result->description), 'module'),
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

    public function testApacheGetModulesFunctionCheck(): void
    {
        // Test that the check correctly detects if apache_get_modules exists
        $functionExists = function_exists('apache_get_modules');

        $result = $this->check->run();

        // If function doesn't exist, must return Good with "Not running on Apache"
        if (! $functionExists) {
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
            $this->assertStringContainsString('Not running on Apache', $result->description);
        } else {
            // If function exists, we're on Apache - check should test modules
            $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        }
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.apache_modules', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testDescriptionMentionsModulesOrApache(): void
    {
        $result = $this->check->run();

        $descLower = strtolower($result->description);

        // Description should mention Apache, modules, or running status
        $this->assertTrue(
            str_contains($descLower, 'apache') ||
            str_contains($descLower, 'module') ||
            str_contains($descLower, 'running'),
            'Description should contain relevant context about Apache modules',
        );
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }
}
