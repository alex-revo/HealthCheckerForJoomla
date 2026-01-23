<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use HealthChecker\Tests\Utilities\MockHttpFactory;
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

    public function testRunReturnsGoodWhenServerTimeIsAccurate(): void
    {
        // Create a mock HTTP client that returns the current time in the Date header
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('accurate', $result->description);
    }

    public function testRunReturnsWarningWhenServerTimeIsDriftedSlightly(): void
    {
        // Create a mock that returns a time 60 seconds in the past (warning threshold is 30s)
        $driftedTime = new \DateTimeImmutable('-60 seconds', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('off by', $result->description);
    }

    public function testRunReturnsCriticalWhenServerTimeIsDriftedSignificantly(): void
    {
        // Create a mock that returns a time 10 minutes in the past (critical threshold is 5 min)
        $driftedTime = new \DateTimeImmutable('-10 minutes', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('off by', $result->description);
        $this->assertStringContainsString('immediately', $result->description);
    }

    public function testRunReturnsGoodWhenHttpRequestFails(): void
    {
        // When HTTP fails, it should gracefully return Good with informational message
        $httpClient = MockHttpFactory::createThatThrows('Connection refused');
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Unable to verify', $result->description);
    }

    public function testRunReturnsGoodWhenNoDateHeader(): void
    {
        // When no Date header is present, should fall back gracefully
        $httpClient = MockHttpFactory::createWithHeadResponse(200, []);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Unable to verify', $result->description);
    }

    public function testRunHandlesArrayDateHeader(): void
    {
        // Some HTTP clients return headers as arrays
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => [$dateHeader],
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testResultContainsTimezone(): void
    {
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();
        $timezone = date_default_timezone_get();

        $this->assertStringContainsString($timezone, $result->description);
    }

    public function testResultMetadata(): void
    {
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame('system.server_time', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }
}
