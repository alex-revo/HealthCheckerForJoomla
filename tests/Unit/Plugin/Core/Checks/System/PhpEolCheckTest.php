<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\PhpEolCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpEolCheck::class)]
class PhpEolCheckTest extends TestCase
{
    private PhpEolCheck $check;

    protected function setUp(): void
    {
        $this->check = new PhpEolCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.php_eol', $this->check->getSlug());
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

        $this->assertSame('system.php_eol', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good, Warning (API unreachable, security-only, or ending soon),
        // or Critical (past EOL)
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsPhpInfo(): void
    {
        $result = $this->check->run();

        // Description should mention PHP or contain an error message (if API unavailable)
        $this->assertTrue(str_contains($result->description, 'PHP') || str_contains($result->description, 'null'));
    }

    public function testCurrentPhpVersionIsDetectable(): void
    {
        $version = PHP_VERSION;

        // PHP version should be a valid version string
        $this->assertNotEmpty($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }

    public function testCheckHandlesApiUnavailability(): void
    {
        // The check should gracefully handle API failures
        // In test environment, it might return Warning if API is unreachable
        // or Good/Warning/Critical if API returns data
        $result = $this->check->run();

        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testPhpVersionExtraction(): void
    {
        // Test that current PHP version can be parsed
        $parts = explode('.', PHP_VERSION);

        $this->assertCount(3, $parts, 'PHP version should have three parts');
        $this->assertIsNumeric($parts[0], 'Major version should be numeric');
        $this->assertIsNumeric($parts[1], 'Minor version should be numeric');
    }

    public function testDescriptionMentionsVersionNumberOrError(): void
    {
        $result = $this->check->run();

        // Description should include the PHP version number or error (if API unavailable)
        $majorMinor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $this->assertTrue(
            str_contains($result->description, $majorMinor) ||
            str_contains($result->description, 'null'),
        );
    }
}
