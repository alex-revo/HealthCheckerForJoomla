<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\UploadMaxFilesizeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UploadMaxFilesizeCheck::class)]
class UploadMaxFilesizeCheckTest extends TestCase
{
    private UploadMaxFilesizeCheck $check;

    private string $originalUploadMaxFilesize;

    private string $originalPostMaxSize;

    protected function setUp(): void
    {
        $this->check = new UploadMaxFilesizeCheck();
        $this->originalUploadMaxFilesize = ini_get('upload_max_filesize');
        $this->originalPostMaxSize = ini_get('post_max_size');
    }

    protected function tearDown(): void
    {
        // Restore original values
        ini_set('upload_max_filesize', $this->originalUploadMaxFilesize);
        ini_set('post_max_size', $this->originalPostMaxSize);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.upload_max_filesize', $this->check->getSlug());
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

        $this->assertSame('system.upload_max_filesize', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good, Warning, or Critical
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsUploadInfo(): void
    {
        $result = $this->check->run();

        // Description should mention upload_max_filesize
        $this->assertStringContainsString('upload_max_filesize', $result->description);
    }

    public function testCurrentUploadMaxFilesizeIsDetectable(): void
    {
        $uploadMaxFilesize = ini_get('upload_max_filesize');

        // upload_max_filesize should return a value
        $this->assertNotFalse($uploadMaxFilesize);
    }

    public function testCurrentPostMaxSizeIsDetectable(): void
    {
        $postMaxSize = ini_get('post_max_size');

        // post_max_size should return a value (used for comparison)
        $this->assertNotFalse($postMaxSize);
    }

    public function testCheckThresholds(): void
    {
        // Test environment thresholds:
        // >= 10M: Good (if <= post_max_size)
        // >= 2M and < 10M: Warning
        // < 2M: Critical
        // Also Warning if > post_max_size
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $uploadBytes = $this->convertToBytes($uploadMaxFilesize);
        $postMaxSize = ini_get('post_max_size');
        $postBytes = $this->convertToBytes($postMaxSize);

        $result = $this->check->run();

        if ($uploadBytes < 2 * 1024 * 1024) {
            $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        } elseif ($uploadBytes > $postBytes) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        } elseif ($uploadBytes < 10 * 1024 * 1024) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        }
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $result = $this->check->run();
        $uploadMaxFilesize = ini_get('upload_max_filesize');

        // Description should include the current value
        $this->assertStringContainsString($uploadMaxFilesize, $result->description);
    }

    public function testWarningWhenExceedsPostMaxSize(): void
    {
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $uploadBytes = $this->convertToBytes($uploadMaxFilesize);
        $postBytes = $this->convertToBytes($postMaxSize);

        $result = $this->check->run();

        if ($uploadBytes > $postBytes) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
            $this->assertStringContainsString('exceeds post_max_size', $result->description);
        } else {
            // If upload_max_filesize <= post_max_size, no warning about exceeding
            $this->assertStringNotContainsString('exceeds post_max_size', $result->description);
        }
    }

    public function testReturnsCriticalWhenBelowMinimum(): void
    {
        // Set upload_max_filesize to 1M (below 2M minimum)
        if (! ini_set('upload_max_filesize', '1M')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        // Ensure post_max_size is higher
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('1M', $result->description);
        $this->assertStringContainsString('2M', $result->description);
    }

    public function testReturnsWarningWhenBelowRecommended(): void
    {
        // Set upload_max_filesize to 5M (above 2M minimum but below 10M recommended)
        if (! ini_set('upload_max_filesize', '5M')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        // Ensure post_max_size is higher
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('5M', $result->description);
        $this->assertStringContainsString('10M', $result->description);
    }

    public function testReturnsGoodWhenMeetsRequirements(): void
    {
        // Set upload_max_filesize to 20M (above recommended)
        if (! ini_set('upload_max_filesize', '20M')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        // Ensure post_max_size is higher
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('20M', $result->description);
        $this->assertStringContainsString('meets requirements', $result->description);
    }

    public function testReturnsGoodAtExactlyRecommended(): void
    {
        // Set upload_max_filesize to exactly 10M (recommended)
        if (! ini_set('upload_max_filesize', '10M')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        // Ensure post_max_size is higher
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('meets requirements', $result->description);
    }

    public function testReturnsWarningAtExactlyMinimum(): void
    {
        // Set upload_max_filesize to exactly 2M (minimum)
        if (! ini_set('upload_max_filesize', '2M')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        // Ensure post_max_size is higher
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        // 2M is at minimum but below recommended, so Warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testReturnsWarningWhenExceedsPostMaxSize(): void
    {
        // Set upload_max_filesize to 64M (higher than post_max_size)
        if (! ini_set('upload_max_filesize', '64M')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        // Set post_max_size lower
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('exceeds post_max_size', $result->description);
        $this->assertStringContainsString('32M', $result->description);
    }

    public function testBoundaryJustBelowMinimum(): void
    {
        // 1.9M is just below 2M minimum
        if (! ini_set('upload_max_filesize', '1945K')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testBoundaryJustBelowRecommended(): void
    {
        // 9M is just below 10M recommended
        if (! ini_set('upload_max_filesize', '9M')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testConvertToBytesWithKilobytes(): void
    {
        // Test that K suffix is handled correctly
        // 2048K = 2M (minimum)
        if (! ini_set('upload_max_filesize', '2048K')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        // Should be Warning (at minimum, below recommended)
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testConvertToBytesWithGigabytes(): void
    {
        // Test that G suffix is handled correctly
        // 1G = 1024M (well above recommended)
        if (! ini_set('upload_max_filesize', '1G')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        // Make sure post_max_size is also high
        ini_set('post_max_size', '2G');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testConvertToBytesWithPlainBytes(): void
    {
        // Test with plain bytes (no suffix)
        // 10485760 = 10M (recommended)
        if (! ini_set('upload_max_filesize', '10485760')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testConvertToBytesWithZeroValue(): void
    {
        // Test with 0 - should be treated as 0 bytes (critical)
        if (! ini_set('upload_max_filesize', '0')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        // 0 bytes is way below minimum, Critical
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testConvertToBytesLowercaseSuffix(): void
    {
        // Test lowercase suffix
        if (! ini_set('upload_max_filesize', '10m')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        ini_set('post_max_size', '32m');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testVeryLowValue(): void
    {
        // 512K is very low
        if (! ini_set('upload_max_filesize', '512K')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        ini_set('post_max_size', '32M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testVeryHighValue(): void
    {
        // 100M is high
        if (! ini_set('upload_max_filesize', '100M')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        ini_set('post_max_size', '128M');

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

    public function testExceedsPostMaxSizeTakesPriorityOverBelowRecommended(): void
    {
        // Test that exceeding post_max_size is checked before below recommended
        // 5M upload with 4M post_max_size - should warn about exceeding, not about being below 10M
        if (! ini_set('upload_max_filesize', '5M')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        ini_set('post_max_size', '4M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('exceeds post_max_size', $result->description);
    }

    public function testCriticalTakesPriorityOverExceedsPostMaxSize(): void
    {
        // Test that critical (below minimum) takes priority
        // 1M upload with 500K post_max_size - should be Critical for being below 2M
        if (! ini_set('upload_max_filesize', '1M')) {
            $this->markTestSkipped('Cannot modify upload_max_filesize in this environment.');
        }
        ini_set('post_max_size', '512K');

        $result = $this->check->run();

        // Below 2M minimum should be Critical, regardless of post_max_size comparison
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
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
