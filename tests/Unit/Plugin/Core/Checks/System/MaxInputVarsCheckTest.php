<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\MaxInputVarsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MaxInputVarsCheck::class)]
class MaxInputVarsCheckTest extends TestCase
{
    private MaxInputVarsCheck $check;

    protected function setUp(): void
    {
        $this->check = new MaxInputVarsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.max_input_vars', $this->check->getSlug());
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

        $this->assertSame('system.max_input_vars', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good, Warning, or Critical depending on max_input_vars value
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsMaxInputVarsInfo(): void
    {
        $result = $this->check->run();

        // Description should mention max_input_vars
        $this->assertStringContainsString('max_input_vars', $result->description);
    }

    public function testCurrentMaxInputVarsIsDetectable(): void
    {
        $maxInputVars = (int) ini_get('max_input_vars');

        // max_input_vars should be a positive integer
        $this->assertGreaterThan(0, $maxInputVars);
    }

    public function testCheckThresholds(): void
    {
        // Test environment thresholds:
        // >= 3000: Good
        // >= 1000 and < 3000: Warning
        // < 1000: Critical
        $maxInputVars = (int) ini_get('max_input_vars');
        $result = $this->check->run();

        if ($maxInputVars >= 3000) {
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
            $this->assertStringContainsString('meets requirements', $result->description);
        } elseif ($maxInputVars >= 1000) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
            $this->assertStringContainsString('below the recommended', $result->description);
        } else {
            $this->assertSame(HealthStatus::Critical, $result->healthStatus);
            $this->assertStringContainsString('below the minimum', $result->description);
        }
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $result = $this->check->run();
        $maxInputVars = (int) ini_get('max_input_vars');

        // Description should include the current value
        $this->assertStringContainsString((string) $maxInputVars, $result->description);
    }
}
