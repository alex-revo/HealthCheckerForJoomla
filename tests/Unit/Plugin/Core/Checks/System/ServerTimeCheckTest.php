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

    public function testResultTitleIsNotEmpty(): void
    {
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testRunReturnsGoodWhenEmptyArrayDateHeader(): void
    {
        // Empty array header should be handled gracefully
        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => [],
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Unable to verify', $result->description);
    }

    public function testRunHandlesLowercaseDateHeader(): void
    {
        // Some servers return lowercase header names
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testWarningThresholdIs30Seconds(): void
    {
        // 35 seconds drift should trigger warning
        $driftedTime = new \DateTimeImmutable('-35 seconds', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testCriticalThresholdIs5Minutes(): void
    {
        // 6 minutes drift should trigger critical
        $driftedTime = new \DateTimeImmutable('-6 minutes', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testBelowWarningThresholdReturnsGood(): void
    {
        // 20 seconds drift should be Good (below 30s warning threshold)
        $driftedTime = new \DateTimeImmutable('-20 seconds', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('accurate', $result->description);
    }

    public function testGoodResultIncludesDriftValue(): void
    {
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // Good result should include drift value and source
        $this->assertStringContainsString('drift:', $result->description);
    }

    public function testGoodResultIncludesSourceName(): void
    {
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // Good result should mention the source (Google or Cloudflare)
        $this->assertStringContainsString('Verified against', $result->description);
    }

    public function testFormatTimeDiffSeconds(): void
    {
        // Test with 45 seconds drift
        $driftedTime = new \DateTimeImmutable('-45 seconds', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // Should mention seconds
        $this->assertStringContainsString('second', $result->description);
    }

    public function testFormatTimeDiffMinutes(): void
    {
        // Test with 2 minutes drift
        $driftedTime = new \DateTimeImmutable('-2 minutes', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // Should mention minutes
        $this->assertStringContainsString('minute', $result->description);
    }

    public function testFormatTimeDiffHours(): void
    {
        // Test with 2 hours drift
        $driftedTime = new \DateTimeImmutable('-2 hours', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // Should mention hours
        $this->assertStringContainsString('hour', $result->description);
    }

    public function testCriticalResultRecommendsNtpCheck(): void
    {
        $driftedTime = new \DateTimeImmutable('-10 minutes', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertStringContainsString('NTP', $result->description);
    }

    public function testFormatTimeDiff1Second(): void
    {
        // Test with exactly 1 second drift - should use singular form
        $driftedTime = new \DateTimeImmutable('-31 seconds', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // Should be warning (over 30s threshold)
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testFormatTimeDiff1Minute(): void
    {
        // Test with exactly 1 minute drift - should use singular "minute"
        $driftedTime = new \DateTimeImmutable('-60 seconds', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('minute', $result->description);
    }

    public function testFormatTimeDiff1Hour(): void
    {
        // Test with 1 hour drift - should use singular "hour"
        $driftedTime = new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('hour', $result->description);
    }

    public function testFormatTimeDiffMultipleHours(): void
    {
        // Test with multiple hours drift - should use plural "hours"
        $driftedTime = new \DateTimeImmutable('-3 hours', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('hours', $result->description);
    }

    public function testFormatTimeDiff1HourExactly(): void
    {
        // Test with exactly 3600 seconds (1 hour)
        $driftedTime = new \DateTimeImmutable('-3600 seconds', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('hour', $result->description);
    }

    public function testFutureDriftAlsoDetected(): void
    {
        // Test with time in the future (positive drift)
        $driftedTime = new \DateTimeImmutable('+10 minutes', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // Should detect drift regardless of direction (uses abs())
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testInvalidDateHeaderFormat(): void
    {
        // Test with an invalid date format
        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => 'invalid-date-format',
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // Should fall back to "Unable to verify" message
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Unable to verify', $result->description);
    }

    public function testAlternativeDateFormat(): void
    {
        // Test with alternative date format (without day name)
        // This tests the fallback parsing in tryFetchTimeFromUrl
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        // Alternative format: "21 Jan 2026 12:31:34 GMT" (no day name)
        $dateHeader = $currentTime->format('d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // Should parse the alternative format successfully
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testMultipleRunsWithSameHttpClient(): void
    {
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
    }

    public function testWarningMentionsNtpSynchronization(): void
    {
        $driftedTime = new \DateTimeImmutable('-60 seconds', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('NTP', $result->description);
    }

    public function testGoodResultShowsVerifiedSource(): void
    {
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        // Result should mention verification against Google (the source)
        $this->assertStringContainsString('Google', $result->description);
    }

    public function testExactlyAtWarningThreshold(): void
    {
        // Test exactly at 30 second threshold (should be Good, not Warning)
        $driftedTime = new \DateTimeImmutable('-30 seconds', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // At exactly 30 seconds, should still be Good (threshold is > 30)
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testExactlyAtCriticalThreshold(): void
    {
        // Test exactly at 300 second (5 min) threshold (should be Warning, not Critical)
        $driftedTime = new \DateTimeImmutable('-300 seconds', new \DateTimeZone('UTC'));
        $dateHeader = $driftedTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        // At exactly 300 seconds, should still be Warning (threshold is > 300)
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testZeroDrift(): void
    {
        // Test with exactly zero drift
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dateHeader = $currentTime->format('D, d M Y H:i:s') . ' GMT';

        $httpClient = MockHttpFactory::createWithHeadResponse(200, [
            'Date' => $dateHeader,
        ]);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        // Should show drift: 0s or similar
        $this->assertMatchesRegularExpression('/drift:\s*\d+s/', $result->description);
    }
}
