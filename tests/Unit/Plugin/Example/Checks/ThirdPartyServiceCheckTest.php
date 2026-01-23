<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Example\Checks;

use HealthChecker\Tests\Utilities\MockHttpFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Example\Checks\ThirdPartyServiceCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ThirdPartyServiceCheck::class)]
class ThirdPartyServiceCheckTest extends TestCase
{
    private ThirdPartyServiceCheck $check;

    protected function setUp(): void
    {
        $this->check = new ThirdPartyServiceCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('example.thirdparty_service', $this->check->getSlug());
    }

    public function testGetCategoryReturnsThirdparty(): void
    {
        $this->assertSame('thirdparty', $this->check->getCategory());
    }

    public function testGetProviderReturnsExample(): void
    {
        $this->assertSame('example', $this->check->getProvider());
    }

    public function testGetTitleReturnsNonEmptyString(): void
    {
        $title = $this->check->getTitle();

        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenServiceReachable(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('reachable', $result->description);
        $this->assertStringContainsString('normally', $result->description);
    }

    public function testRunReturnsCriticalWhenServiceUnreachable(): void
    {
        $httpClient = MockHttpFactory::createThatThrows('Connection refused');
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('Cannot reach', $result->description);
    }

    public function testRunReturnsCriticalWhenHttpError(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(500);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('Cannot reach', $result->description);
    }

    public function testRunReturnsCriticalWhenResponseCodeZero(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(0);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testResultHasCorrectSlug(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame('example.thirdparty_service', $result->slug);
    }

    public function testResultHasCorrectCategory(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame('thirdparty', $result->category);
    }

    public function testResultHasCorrectProvider(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame('example', $result->provider);
    }

    public function testResultDescriptionContainsExampleCheckMarker(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertStringContainsString('[EXAMPLE CHECK]', $result->description);
    }

    public function testResultDescriptionContainsDisableInstructions(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertStringContainsString('Health Checker - Example Provider', $result->description);
        $this->assertStringContainsString('Extensions', $result->description);
        $this->assertStringContainsString('Plugins', $result->description);
    }

    public function testResultDescriptionMentionsJoomlaApi(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(200);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertStringContainsString('Joomla API', $result->description);
    }

    public function testRunReturnsCorrectStatusForClientError(): void
    {
        $httpClient = MockHttpFactory::createWithHeadResponse(404);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunReturnsGoodFor3xxRedirect(): void
    {
        // 3xx responses are still successful - server responded
        $httpClient = MockHttpFactory::createWithHeadResponse(301);
        $this->check->setHttpClient($httpClient);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}
