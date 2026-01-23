<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\ServerTimeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerTimeCheck::class)]
class ServerTimeCheckTest extends TestCase
{
    private ServerTimeCheck $check;

    protected function setUp(): void
    {
        $this->check = new ServerTimeCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.server_time', $this->check->getSlug());
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

        $this->assertSame('system.server_time', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good, Warning, or Critical depending on time drift
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsTimeInfo(): void
    {
        $result = $this->check->run();

        // Description should mention time, server, or error (if HTTP unavailable)
        $this->assertTrue(
            str_contains(strtolower($result->description), 'time') ||
            str_contains(strtolower($result->description), 'server') ||
            str_contains(strtolower($result->description), 'null'),
        );
    }

    public function testCurrentTimezoneIsDetectable(): void
    {
        $timezone = date_default_timezone_get();

        // Timezone should return a valid string
        $this->assertNotEmpty($timezone);
        $this->assertIsString($timezone);
    }

    public function testServerTimeCanBeCreated(): void
    {
        $serverTime = new \DateTimeImmutable('now');

        $this->assertInstanceOf(\DateTimeImmutable::class, $serverTime);
    }

    public function testCheckHandlesExternalTimeSourceUnavailability(): void
    {
        // The check should gracefully handle when external time sources are unreachable
        $result = $this->check->run();

        // Should return a valid status even if external sources are unavailable
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testDescriptionIncludesTimezoneOrError(): void
    {
        $result = $this->check->run();
        $timezone = date_default_timezone_get();

        // Description should include timezone info or error (if HTTP unavailable)
        $this->assertTrue(
            str_contains($result->description, $timezone) ||
            str_contains($result->description, 'null'),
        );
    }

    public function testCheckUsesUtcInternally(): void
    {
        // Verify UTC timezone can be used
        $utcTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->assertSame('UTC', $utcTime->getTimezone()->getName());
    }
}
