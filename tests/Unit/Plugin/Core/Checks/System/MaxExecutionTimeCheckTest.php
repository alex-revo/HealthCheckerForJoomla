<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\MaxExecutionTimeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MaxExecutionTimeCheck::class)]
class MaxExecutionTimeCheckTest extends TestCase
{
    private MaxExecutionTimeCheck $check;

    private string $originalMaxExecutionTime;

    protected function setUp(): void
    {
        $this->check = new MaxExecutionTimeCheck();
        $this->originalMaxExecutionTime = ini_get('max_execution_time');
    }

    protected function tearDown(): void
    {
        // Restore original value
        ini_set('max_execution_time', $this->originalMaxExecutionTime);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.max_execution_time', $this->check->getSlug());
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

        $this->assertSame('system.max_execution_time', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunResultHasDescription(): void
    {
        $result = $this->check->run();

        $this->assertIsString($result->description);
        $this->assertNotEmpty($result->description);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsTimeInfo(): void
    {
        $result = $this->check->run();

        // Description should mention execution time or unlimited
        $this->assertTrue(
            str_contains($result->description, 'execution time') ||
            str_contains($result->description, 'unlimited'),
        );
    }

    public function testReturnsGoodWhenUnlimited(): void
    {
        // Set max_execution_time to 0 (unlimited) - only possible in CLI
        ini_set('max_execution_time', '0');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('unlimited', $result->description);
    }

    public function testReturnsCriticalWhenBelowMinimum(): void
    {
        // Skip if we can't change max_execution_time (non-CLI environment)
        if (! ini_set('max_execution_time', '15')) {
            $this->markTestSkipped('Cannot modify max_execution_time in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('15', $result->description);
        $this->assertStringContainsString('below', $result->description);
    }

    public function testReturnsWarningWhenBelowRecommended(): void
    {
        // Skip if we can't change max_execution_time
        if (! ini_set('max_execution_time', '45')) {
            $this->markTestSkipped('Cannot modify max_execution_time in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('45', $result->description);
        $this->assertStringContainsString('recommended', $result->description);
    }

    public function testReturnsGoodWhenMeetsRequirements(): void
    {
        // Skip if we can't change max_execution_time
        if (! ini_set('max_execution_time', '120')) {
            $this->markTestSkipped('Cannot modify max_execution_time in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('120', $result->description);
        $this->assertStringContainsString('meets requirements', $result->description);
    }

    public function testReturnsGoodAtExactlyMinimum(): void
    {
        // 30 seconds is exactly at minimum, so it should be Warning (below recommended)
        if (! ini_set('max_execution_time', '30')) {
            $this->markTestSkipped('Cannot modify max_execution_time in this environment.');
        }

        $result = $this->check->run();

        // 30s is at minimum but below recommended 60s, so Warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testReturnsGoodAtExactlyRecommended(): void
    {
        // 60 seconds is exactly at recommended
        if (! ini_set('max_execution_time', '60')) {
            $this->markTestSkipped('Cannot modify max_execution_time in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('meets requirements', $result->description);
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
        $currentValue = (int) ini_get('max_execution_time');
        $result = $this->check->run();

        // If not unlimited (0), description should include the current value
        if ($currentValue !== 0) {
            $this->assertStringContainsString((string) $currentValue, $result->description);
        } else {
            $this->assertStringContainsString('unlimited', $result->description);
        }
    }
}
