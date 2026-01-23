<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\OutputBufferingCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OutputBufferingCheck::class)]
class OutputBufferingCheckTest extends TestCase
{
    private OutputBufferingCheck $check;

    private string $originalOutputBuffering;

    protected function setUp(): void
    {
        $this->check = new OutputBufferingCheck();
        $this->originalOutputBuffering = ini_get('output_buffering');
    }

    protected function tearDown(): void
    {
        // Restore original value
        ini_set('output_buffering', $this->originalOutputBuffering);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.output_buffering', $this->check->getSlug());
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

        $this->assertSame('system.output_buffering', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunAlwaysReturnsGood(): void
    {
        // This check is informational and always returns Good
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunDescriptionContainsOutputBufferingInfo(): void
    {
        $result = $this->check->run();

        // Description should mention output buffering
        $this->assertStringContainsString('Output buffering', $result->description);
    }

    public function testCurrentOutputBufferingIsDetectable(): void
    {
        $outputBuffering = ini_get('output_buffering');

        // output_buffering should return a value (even if empty/false)
        $this->assertIsString($outputBuffering);
    }

    public function testDescriptionReflectsCurrentSetting(): void
    {
        $outputBuffering = ini_get('output_buffering');
        $result = $this->check->run();

        // Check that description reflects the actual setting
        if (in_array($outputBuffering, ['', '0', 'Off'], true)) {
            $this->assertStringContainsString('disabled', $result->description);
        } elseif ($outputBuffering === '1' || $outputBuffering === 'On') {
            $this->assertStringContainsString('enabled', $result->description);
        } else {
            // Numeric buffer size
            $this->assertStringContainsString('bytes', $result->description);
        }
    }

    public function testCheckIsInformationalOnly(): void
    {
        // This check should never return Warning or Critical
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testDisabledWhenEmpty(): void
    {
        // Set output_buffering to empty string
        if (! ini_set('output_buffering', '')) {
            $this->markTestSkipped('Cannot modify output_buffering in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
        $this->assertStringContainsString('recommended', $result->description);
    }

    public function testDisabledWhenZero(): void
    {
        // Set output_buffering to 0
        if (! ini_set('output_buffering', '0')) {
            $this->markTestSkipped('Cannot modify output_buffering in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testDisabledWhenOff(): void
    {
        // Set output_buffering to Off
        if (! ini_set('output_buffering', 'Off')) {
            $this->markTestSkipped('Cannot modify output_buffering in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testEnabledWhenOne(): void
    {
        // Set output_buffering to 1
        if (! ini_set('output_buffering', '1')) {
            $this->markTestSkipped('Cannot modify output_buffering in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('enabled', $result->description);
    }

    public function testEnabledWhenOn(): void
    {
        // Set output_buffering to On
        if (! ini_set('output_buffering', 'On')) {
            $this->markTestSkipped('Cannot modify output_buffering in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('enabled', $result->description);
    }

    public function testNumericBufferSize(): void
    {
        // Set output_buffering to a specific size
        if (! ini_set('output_buffering', '4096')) {
            $this->markTestSkipped('Cannot modify output_buffering in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('4096', $result->description);
        $this->assertStringContainsString('bytes', $result->description);
    }

    public function testLargeNumericBufferSize(): void
    {
        // Set output_buffering to a large size
        if (! ini_set('output_buffering', '65536')) {
            $this->markTestSkipped('Cannot modify output_buffering in this environment.');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('65536', $result->description);
        $this->assertStringContainsString('bytes', $result->description);
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

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.output_buffering', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testAllPossibleValuesReturnGood(): void
    {
        $possibleValues = ['', '0', 'Off', '1', 'On', '4096', '8192', '16384'];
        $testedCount = 0;

        foreach ($possibleValues as $value) {
            if (! ini_set('output_buffering', $value)) {
                continue; // Skip values we can't set
            }

            $result = $this->check->run();

            $this->assertSame(
                HealthStatus::Good,
                $result->healthStatus,
                sprintf('Expected Good status for output_buffering=%s', $value),
            );
            $testedCount++;
        }

        if ($testedCount === 0) {
            $this->markTestSkipped('Cannot modify output_buffering in this environment.');
        }
    }
}
