<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\MaxInputTimeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MaxInputTimeCheck::class)]
class MaxInputTimeCheckTest extends TestCase
{
    private MaxInputTimeCheck $check;

    private string $originalMaxInputTime;

    protected function setUp(): void
    {
        $this->check = new MaxInputTimeCheck();
        $this->originalMaxInputTime = ini_get('max_input_time');
    }

    protected function tearDown(): void
    {
        // Restore original value
        ini_set('max_input_time', $this->originalMaxInputTime);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.max_input_time', $this->check->getSlug());
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

        $this->assertSame('system.max_input_time', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Result depends on PHP configuration - never returns Critical
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testReturnsGoodWhenUnlimitedNegativeOne(): void
    {
        // Set max_input_time to -1 (unlimited)
        if (! ini_set('max_input_time', '-1')) {
            $this->markTestSkipped('Cannot modify max_input_time in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('unlimited', $result->description);
    }

    public function testReturnsGoodWhenZero(): void
    {
        // Set max_input_time to 0 (unlimited)
        if (! ini_set('max_input_time', '0')) {
            $this->markTestSkipped('Cannot modify max_input_time in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('unlimited', $result->description);
    }

    public function testReturnsWarningWhenBelowMinimum(): void
    {
        // Set max_input_time to 30 (below recommended 60)
        if (! ini_set('max_input_time', '30')) {
            $this->markTestSkipped('Cannot modify max_input_time in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('30', $result->description);
    }

    public function testReturnsGoodWhenMeetsMinimum(): void
    {
        // Set max_input_time to exactly 60 (meets minimum)
        if (! ini_set('max_input_time', '60')) {
            $this->markTestSkipped('Cannot modify max_input_time in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('60', $result->description);
        $this->assertStringContainsString('adequate', $result->description);
    }

    public function testReturnsGoodWhenAboveMinimum(): void
    {
        // Set max_input_time to 120 (above recommended)
        if (! ini_set('max_input_time', '120')) {
            $this->markTestSkipped('Cannot modify max_input_time in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('120', $result->description);
        $this->assertStringContainsString('adequate', $result->description);
    }

    public function testWarningMessageIncludesRecommendation(): void
    {
        // Set max_input_time to a low value
        if (! ini_set('max_input_time', '15')) {
            $this->markTestSkipped('Cannot modify max_input_time in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('60', $result->description); // Recommended value
        $this->assertStringContainsString('-1', $result->description); // Unlimited option
    }

    public function testNeverReturnsCritical(): void
    {
        // This check never returns Critical status according to source code
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testDescriptionMentionsFileUploads(): void
    {
        // Set to trigger a warning
        if (! ini_set('max_input_time', '30')) {
            $this->markTestSkipped('Cannot modify max_input_time in this environment.');
        }

        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Warning) {
            $this->assertStringContainsString('file uploads', $result->description);
        }
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

    public function testDescriptionIncludesCurrentValue(): void
    {
        $currentValue = (int) ini_get('max_input_time');
        $result = $this->check->run();

        // If not unlimited, description should include the current value
        if ($currentValue !== -1 && $currentValue !== 0) {
            $this->assertStringContainsString((string) $currentValue, $result->description);
        } else {
            $this->assertStringContainsString('unlimited', $result->description);
        }
    }

    public function testBoundaryAtExactlyMinimum(): void
    {
        // Test exactly at the boundary (60 seconds)
        if (! ini_set('max_input_time', '60')) {
            $this->markTestSkipped('Cannot modify max_input_time in this environment.');
        }

        $result = $this->check->run();

        // 60 seconds is exactly at minimum, should be Good
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testBoundaryJustBelowMinimum(): void
    {
        // Test just below the boundary (59 seconds)
        if (! ini_set('max_input_time', '59')) {
            $this->markTestSkipped('Cannot modify max_input_time in this environment.');
        }

        $result = $this->check->run();

        // 59 seconds is below minimum, should be Warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }
}
