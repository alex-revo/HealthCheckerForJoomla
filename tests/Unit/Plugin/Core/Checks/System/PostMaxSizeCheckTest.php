<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\PostMaxSizeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PostMaxSizeCheck::class)]
class PostMaxSizeCheckTest extends TestCase
{
    private PostMaxSizeCheck $check;

    private string $originalPostMaxSize;

    protected function setUp(): void
    {
        $this->check = new PostMaxSizeCheck();
        $this->originalPostMaxSize = ini_get('post_max_size');
    }

    protected function tearDown(): void
    {
        // Restore original value
        ini_set('post_max_size', $this->originalPostMaxSize);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.post_max_size', $this->check->getSlug());
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

        $this->assertSame('system.post_max_size', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good, Warning, or Critical depending on post_max_size value
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsPostMaxSizeInfo(): void
    {
        $result = $this->check->run();

        // Description should mention post_max_size
        $this->assertStringContainsString('post_max_size', $result->description);
    }

    public function testCurrentPostMaxSizeIsDetectable(): void
    {
        $postMaxSize = ini_get('post_max_size');

        // post_max_size should return a value
        $this->assertNotFalse($postMaxSize);
    }

    public function testCheckThresholds(): void
    {
        // Test environment thresholds:
        // >= 32M: Good
        // >= 8M and < 32M: Warning
        // < 8M: Critical
        $postMaxSize = ini_get('post_max_size');
        $bytes = $this->convertToBytes($postMaxSize);
        $result = $this->check->run();

        if ($bytes >= 32 * 1024 * 1024) {
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } elseif ($bytes >= 8 * 1024 * 1024) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        }
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $result = $this->check->run();
        $postMaxSize = ini_get('post_max_size');

        // Description should include the current value
        $this->assertStringContainsString($postMaxSize, $result->description);
    }

    public function testReturnsCriticalWhenBelowMinimum(): void
    {
        // Set post_max_size to 4M (below 8M minimum)
        if (! ini_set('post_max_size', '4M')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('4M', $result->description);
        $this->assertStringContainsString('8M', $result->description);
    }

    public function testReturnsWarningWhenBelowRecommended(): void
    {
        // Set post_max_size to 16M (above 8M minimum but below 32M recommended)
        if (! ini_set('post_max_size', '16M')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('16M', $result->description);
        $this->assertStringContainsString('32M', $result->description);
    }

    public function testReturnsGoodWhenMeetsRequirements(): void
    {
        // Set post_max_size to 64M (above recommended)
        if (! ini_set('post_max_size', '64M')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('64M', $result->description);
        $this->assertStringContainsString('meets requirements', $result->description);
    }

    public function testReturnsGoodAtExactlyRecommended(): void
    {
        // Set post_max_size to exactly 32M (recommended)
        if (! ini_set('post_max_size', '32M')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('meets requirements', $result->description);
    }

    public function testReturnsWarningAtExactlyMinimum(): void
    {
        // Set post_max_size to exactly 8M (minimum)
        if (! ini_set('post_max_size', '8M')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        // 8M is at minimum but below recommended, so Warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testBoundaryJustBelowMinimum(): void
    {
        // 7M is just below 8M minimum
        if (! ini_set('post_max_size', '7M')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testBoundaryJustBelowRecommended(): void
    {
        // 31M is just below 32M recommended
        if (! ini_set('post_max_size', '31M')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testConvertToBytesWithKilobytes(): void
    {
        // Test that K suffix is handled correctly
        // 8192K = 8M (minimum)
        if (! ini_set('post_max_size', '8192K')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        // Should be Warning (at minimum, below recommended)
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testConvertToBytesWithGigabytes(): void
    {
        // Test that G suffix is handled correctly
        // 1G = 1024M (well above recommended)
        if (! ini_set('post_max_size', '1G')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testConvertToBytesWithPlainBytes(): void
    {
        // Test with plain bytes (no suffix)
        // 33554432 = 32M (recommended)
        if (! ini_set('post_max_size', '33554432')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testConvertToBytesWithZeroValue(): void
    {
        // Test with 0 - should be treated as 0 bytes (critical)
        if (! ini_set('post_max_size', '0')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        // 0 bytes is way below minimum, Critical
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testConvertToBytesLowercaseSuffix(): void
    {
        // Test lowercase suffix
        if (! ini_set('post_max_size', '32m')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testVeryLowValue(): void
    {
        // 1M is very low
        if (! ini_set('post_max_size', '1M')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testVeryHighValue(): void
    {
        // 128M is high
        if (! ini_set('post_max_size', '128M')) {
            $this->markTestSkipped('Cannot modify post_max_size in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
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

    /**
     * Helper method to convert PHP shorthand notation to bytes.
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '0') {
            return 0;
        }

        $last = strtolower($value[strlen($value) - 1]);
        $numericValue = (int) $value;

        return match ($last) {
            'g' => $numericValue * 1024 * 1024 * 1024,
            'm' => $numericValue * 1024 * 1024,
            'k' => $numericValue * 1024,
            default => $numericValue,
        };
    }
}
