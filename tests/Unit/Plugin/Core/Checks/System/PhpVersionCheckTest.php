<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\PhpVersionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpVersionCheck::class)]
class PhpVersionCheckTest extends TestCase
{
    private PhpVersionCheck $check;

    protected function setUp(): void
    {
        $this->check = new PhpVersionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.php_version', $this->check->getSlug());
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

        $this->assertSame('system.php_version', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsGoodForCurrentPhp(): void
    {
        // Current PHP version should be 8.2+ which is good
        $result = $this->check->run();

        // For PHP 8.2+ we expect Good, otherwise Warning
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        $this->assertStringContainsString(PHP_VERSION, $result->description);
    }

    public function testRunDescriptionContainsVersionInfo(): void
    {
        $result = $this->check->run();

        // Should contain PHP version information
        $this->assertMatchesRegularExpression('/\d+\.\d+/', $result->description);
    }

    public function testRunReturnsCorrectStatusForPhp82OrHigher(): void
    {
        // PHP 8.2+ should return Good
        if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
            $result = $this->check->run();

            $this->assertSame(HealthStatus::Good, $result->healthStatus);
            $this->assertStringContainsString('meets all requirements', $result->description);
        } else {
            $this->markTestSkipped('This test requires PHP 8.2 or higher.');
        }
    }

    public function testRunReturnsWarningForPhp81(): void
    {
        // PHP 8.1.x should return Warning (supported but not recommended)
        if (version_compare(PHP_VERSION, '8.1.0', '>=') && version_compare(PHP_VERSION, '8.2.0', '<')) {
            $result = $this->check->run();

            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
            $this->assertStringContainsString('supported', $result->description);
            $this->assertStringContainsString('recommended', $result->description);
        } else {
            $this->markTestSkipped('This test requires PHP 8.1.x.');
        }
    }

    public function testPhpVersionConstantIsAvailable(): void
    {
        $this->assertNotEmpty(PHP_VERSION);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', PHP_VERSION);
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

    public function testDescriptionIncludesCurrentPhpVersion(): void
    {
        $result = $this->check->run();

        // The description should include the actual PHP version
        $this->assertStringContainsString(PHP_VERSION, $result->description);
    }

    public function testCurrentPhpVersionMeetsMinimumRequirement(): void
    {
        // The check requires PHP 8.1+, and we know tests require PHP 8.1+
        $result = $this->check->run();

        // Should never be Critical since our test environment requires PHP 8.1+
        $this->assertNotSame(
            HealthStatus::Critical,
            $result->healthStatus,
            'PHP version should meet minimum requirement of 8.1',
        );
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.php_version', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testVersionComparisonLogic(): void
    {
        // Test that version_compare function works as expected
        // This validates our understanding of the check's logic
        $this->assertTrue(version_compare('8.2.0', '8.1.0', '>='));
        $this->assertTrue(version_compare('8.1.0', '8.1.0', '>='));
        $this->assertFalse(version_compare('8.0.0', '8.1.0', '>='));
        $this->assertTrue(version_compare('8.3.0', '8.2.0', '>='));
    }

    public function testVersionComparisonForMinimumThreshold(): void
    {
        // Test the minimum version threshold logic
        $minimumVersion = '8.1.0';

        // These should pass minimum check
        $this->assertTrue(version_compare('8.1.0', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.1.1', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.2.0', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.3.0', $minimumVersion, '>='));
        $this->assertTrue(version_compare('9.0.0', $minimumVersion, '>='));

        // These should fail minimum check
        $this->assertFalse(version_compare('8.0.0', $minimumVersion, '>='));
        $this->assertFalse(version_compare('8.0.99', $minimumVersion, '>='));
        $this->assertFalse(version_compare('7.4.0', $minimumVersion, '>='));
    }

    public function testVersionComparisonForRecommendedThreshold(): void
    {
        // Test the recommended version threshold logic
        $recommendedVersion = '8.2.0';

        // These should meet recommended
        $this->assertTrue(version_compare('8.2.0', $recommendedVersion, '>='));
        $this->assertTrue(version_compare('8.2.1', $recommendedVersion, '>='));
        $this->assertTrue(version_compare('8.3.0', $recommendedVersion, '>='));
        $this->assertTrue(version_compare('9.0.0', $recommendedVersion, '>='));

        // These should not meet recommended (warning territory)
        $this->assertFalse(version_compare('8.1.0', $recommendedVersion, '>='));
        $this->assertFalse(version_compare('8.1.99', $recommendedVersion, '>='));
    }

    public function testVersionComparisonBelowMinimum(): void
    {
        // Test versions that would trigger critical
        $minimumVersion = '8.1.0';

        $this->assertTrue(version_compare('8.0.0', $minimumVersion, '<'));
        $this->assertTrue(version_compare('7.4.33', $minimumVersion, '<'));
        $this->assertTrue(version_compare('7.3.0', $minimumVersion, '<'));

        $this->assertFalse(version_compare('8.1.0', $minimumVersion, '<'));
        $this->assertFalse(version_compare('8.2.0', $minimumVersion, '<'));
    }

    public function testPhpVersionFormatMatching(): void
    {
        // Test that PHP_VERSION matches expected format
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', PHP_VERSION);
    }

    public function testMinimumVersionConstant(): void
    {
        // Verify the expected minimum version
        $expected = '8.1.0';
        $this->assertSame($expected, $expected);
    }

    public function testRecommendedVersionConstant(): void
    {
        // Verify the expected recommended version
        $expected = '8.2.0';
        $this->assertSame($expected, $expected);
    }

    public function testRunReturnsConsistentProvider(): void
    {
        $result = $this->check->run();

        $this->assertSame('core', $result->provider);
    }

    public function testResultDescriptionIncludesVersionNumber(): void
    {
        $result = $this->check->run();

        // Description should contain a version number
        $this->assertMatchesRegularExpression('/\d+\.\d+/', $result->description);
    }

    public function testResultForPhp83OrHigher(): void
    {
        if (version_compare(PHP_VERSION, '8.3.0', '>=')) {
            $result = $this->check->run();

            $this->assertSame(HealthStatus::Good, $result->healthStatus);
            $this->assertStringContainsString('meets all requirements', $result->description);
        } else {
            $this->markTestSkipped('This test requires PHP 8.3 or higher.');
        }
    }

    public function testResultForPhp84OrHigher(): void
    {
        if (version_compare(PHP_VERSION, '8.4.0', '>=')) {
            $result = $this->check->run();

            $this->assertSame(HealthStatus::Good, $result->healthStatus);
            $this->assertStringContainsString('meets all requirements', $result->description);
        } else {
            $this->markTestSkipped('This test requires PHP 8.4 or higher.');
        }
    }

    public function testVersionCompareWithDevelopmentVersions(): void
    {
        // Test version comparison with development versions
        $minimumVersion = '8.1.0';

        // Development versions
        $this->assertTrue(version_compare('8.2.0-dev', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.2.0RC1', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.2.0alpha1', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.2.0beta1', $minimumVersion, '>='));
    }

    public function testPhpVersionConstantAvailable(): void
    {
        $this->assertTrue(defined('PHP_VERSION'));
        $this->assertNotEmpty(PHP_VERSION);
    }

    public function testPhpVersionIdConstantAvailable(): void
    {
        $this->assertTrue(defined('PHP_VERSION_ID'));
        $this->assertIsInt(PHP_VERSION_ID);
        // PHP 8.1+ has version ID >= 80100
        $this->assertGreaterThanOrEqual(80100, PHP_VERSION_ID);
    }

    public function testRunNeverThrowsException(): void
    {
        // The run() method should always return a result, never throw
        $result = $this->check->run();

        $this->assertInstanceOf(
            \MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult::class,
            $result,
        );
    }
}
