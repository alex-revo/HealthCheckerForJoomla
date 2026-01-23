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

    private string $originalMaxInputVars;

    protected function setUp(): void
    {
        $this->check = new MaxInputVarsCheck();
        $this->originalMaxInputVars = ini_get('max_input_vars');
    }

    protected function tearDown(): void
    {
        // Restore original value
        ini_set('max_input_vars', $this->originalMaxInputVars);
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

    public function testReturnsCriticalWhenBelowMinimum(): void
    {
        // Set max_input_vars to 500 (below 1000 minimum)
        if (! ini_set('max_input_vars', '500')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('500', $result->description);
        $this->assertStringContainsString('1000', $result->description);
        $this->assertStringContainsString('lose data', $result->description);
    }

    public function testReturnsWarningWhenBelowRecommended(): void
    {
        // Set max_input_vars to 1500 (above 1000 minimum but below 3000 recommended)
        if (! ini_set('max_input_vars', '1500')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('1500', $result->description);
        $this->assertStringContainsString('3000', $result->description);
    }

    public function testReturnsGoodWhenMeetsRequirements(): void
    {
        // Set max_input_vars to 5000 (above recommended)
        if (! ini_set('max_input_vars', '5000')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('5000', $result->description);
        $this->assertStringContainsString('meets requirements', $result->description);
    }

    public function testReturnsGoodAtExactlyRecommended(): void
    {
        // Set max_input_vars to exactly 3000 (recommended)
        if (! ini_set('max_input_vars', '3000')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('meets requirements', $result->description);
    }

    public function testReturnsWarningAtExactlyMinimum(): void
    {
        // Set max_input_vars to exactly 1000 (minimum)
        if (! ini_set('max_input_vars', '1000')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
        }

        $result = $this->check->run();

        // 1000 is at minimum but below recommended, so Warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testBoundaryJustBelowMinimum(): void
    {
        // 999 is just below 1000 minimum
        if (! ini_set('max_input_vars', '999')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testBoundaryJustBelowRecommended(): void
    {
        // 2999 is just below 3000 recommended
        if (! ini_set('max_input_vars', '2999')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testVeryLowValue(): void
    {
        // 100 is very low
        if (! ini_set('max_input_vars', '100')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testVeryHighValue(): void
    {
        // 10000 is very high
        if (! ini_set('max_input_vars', '10000')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
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

    public function testCriticalMessageMentionsDataLoss(): void
    {
        if (! ini_set('max_input_vars', '500')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('lose data', $result->description);
    }

    public function testWarningMessageMentionsIssues(): void
    {
        if (! ini_set('max_input_vars', '1500')) {
            $this->markTestSkipped('Cannot modify max_input_vars in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('issues', $result->description);
    }
}
