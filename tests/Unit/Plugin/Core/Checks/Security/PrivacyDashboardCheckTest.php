<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\PrivacyDashboardCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrivacyDashboardCheck::class)]
class PrivacyDashboardCheckTest extends TestCase
{
    private PrivacyDashboardCheck $check;

    protected function setUp(): void
    {
        $this->check = new PrivacyDashboardCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.privacy_dashboard', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->check->getCategory());
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

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenPrivacyComponentDisabled(): void
    {
        // First query: check if privacy component is enabled (returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Privacy component is disabled', $result->description);
    }

    public function testRunReturnsWarningWhenPendingRequestsExist(): void
    {
        // First query: privacy component enabled (returns 1)
        // Second query: pending privacy requests count (returns 3)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 3]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('3 pending privacy request', $result->description);
    }

    public function testRunReturnsGoodWhenEnabledAndNoPendingRequests(): void
    {
        // First query: privacy component enabled (returns 1)
        // Second query: no pending privacy requests (returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('enabled with no pending requests', $result->description);
    }

    public function testRunReturnsWarningWithSinglePendingRequest(): void
    {
        // First query: privacy component enabled (returns 1)
        // Second query: 1 pending privacy request
        $database = MockDatabaseFactory::createWithSequentialResults([1, 1]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('1 pending privacy request', $result->description);
    }
}
