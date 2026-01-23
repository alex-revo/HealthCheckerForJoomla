<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Example\Checks;

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

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
    }

    public function testResultHasCorrectSlug(): void
    {
        $result = $this->check->run();

        $this->assertSame('example.thirdparty_service', $result->slug);
    }

    public function testResultHasCorrectCategory(): void
    {
        $result = $this->check->run();

        $this->assertSame('thirdparty', $result->category);
    }

    public function testResultHasCorrectProvider(): void
    {
        $result = $this->check->run();

        $this->assertSame('example', $result->provider);
    }

    public function testResultDescriptionContainsExampleCheckMarker(): void
    {
        $result = $this->check->run();

        $this->assertStringContainsString('[EXAMPLE CHECK]', $result->description);
    }

    public function testResultDescriptionContainsDisableInstructions(): void
    {
        $result = $this->check->run();

        $this->assertStringContainsString('Health Checker - Example Provider', $result->description);
        $this->assertStringContainsString('Extensions', $result->description);
        $this->assertStringContainsString('Plugins', $result->description);
    }

    public function testResultStatusIsOneOfExpectedValues(): void
    {
        $result = $this->check->run();

        $validStatuses = [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical];
        $this->assertContains($result->healthStatus, $validStatuses);
    }

    public function testResultDescriptionMentionsJoomlaApi(): void
    {
        $result = $this->check->run();

        $this->assertStringContainsString('Joomla API', $result->description);
    }
}
