<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\SessionSavePathCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SessionSavePathCheck::class)]
class SessionSavePathCheckTest extends TestCase
{
    private SessionSavePathCheck $check;

    protected function setUp(): void
    {
        $this->check = new SessionSavePathCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.session_save_path', $this->check->getSlug());
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

        $this->assertSame('system.session_save_path', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good or Critical (never Warning according to source)
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Critical]);
    }

    public function testRunDescriptionContainsSessionInfo(): void
    {
        $result = $this->check->run();

        // Description should mention session or path
        $this->assertTrue(
            str_contains(strtolower($result->description), 'session') ||
            str_contains(strtolower($result->description), 'path'),
        );
    }

    public function testCurrentSessionSavePathIsDetectable(): void
    {
        $savePath = session_save_path();

        // session_save_path() should return a string (may be empty)
        $this->assertIsString($savePath);
    }

    public function testSystemTempDirectoryExists(): void
    {
        $tempDir = sys_get_temp_dir();

        // System temp directory should exist and be a string
        $this->assertIsString($tempDir);
        $this->assertNotEmpty($tempDir);
    }

    public function testCheckWithValidSessionPath(): void
    {
        // In most test environments, the session save path should be valid
        $result = $this->check->run();

        // If path is valid, should return Good
        // If path is invalid, should return Critical
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Critical]);
    }

    public function testGoodResultIncludesPathInfo(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Good) {
            // Good result should mention the path is writable
            $this->assertStringContainsString('writable', $result->description);
        }
    }

    public function testCriticalResultExplainsIssue(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Critical) {
            // Critical result should explain the issue
            $this->assertTrue(
                str_contains($result->description, 'does not exist') ||
                str_contains($result->description, 'not writable'),
            );
        } else {
            // If not critical, should be Good (path is valid)
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        }
    }

    public function testCheckNeverReturnsWarning(): void
    {
        // According to the source, this check does not produce Warning
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Warning, $result->healthStatus);
    }
}
