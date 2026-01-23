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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\UserActionsLogCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserActionsLogCheck::class)]
class UserActionsLogCheckTest extends TestCase
{
    private UserActionsLogCheck $check;

    protected function setUp(): void
    {
        $this->check = new UserActionsLogCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.user_actions_log', $this->check->getSlug());
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

    public function testRunReturnsWarningWhenUserActionsLogPluginDisabled(): void
    {
        // First query: User Actions Log plugin enabled status (returns null/false)
        $database = MockDatabaseFactory::createWithSequentialResults([0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('User - User Actions Log plugin is disabled', $result->description);
    }

    public function testRunReturnsWarningWhenActionLogPluginsDisabled(): void
    {
        // First query: User Actions Log plugin enabled (returns 1)
        // Second query: Action Log plugins count (returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Action Log plugins are disabled', $result->description);
    }

    public function testRunReturnsGoodWhenBothPluginsEnabled(): void
    {
        // First query: User Actions Log plugin enabled (returns 1)
        // Second query: Action Log plugins count (returns 3)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 3]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('User Actions Log plugin is enabled', $result->description);
    }

    public function testRunReturnsGoodWithSingleActionLogPlugin(): void
    {
        // First query: User Actions Log plugin enabled (returns 1)
        // Second query: Action Log plugins count (returns 1)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 1]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenUserActionsLogPluginReturnsNull(): void
    {
        // First query: User Actions Log plugin not found (returns null)
        $database = MockDatabaseFactory::createWithSequentialResults([null]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }
}
