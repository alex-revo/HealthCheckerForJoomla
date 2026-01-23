<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Users;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\UserGroupsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserGroupsCheck::class)]
class UserGroupsCheckTest extends TestCase
{
    private UserGroupsCheck $check;

    protected function setUp(): void
    {
        $this->check = new UserGroupsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.user_groups', $this->check->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->check->getCategory());
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

    public function testRunWithFewGroupsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(8);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('8 user groups', $result->description);
    }

    public function testRunWithTwentyGroupsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(20);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('20 user groups', $result->description);
    }

    public function testRunWithManyGroupsReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(25);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('25 user groups', $result->description);
        $this->assertStringContainsString('consolidating', $result->description);
    }

    public function testRunWithExactlyThresholdPlusOneReturnsWarning(): void
    {
        // Threshold is >20, so 21 should trigger warning
        $database = MockDatabaseFactory::createWithResult(21);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunWithDefaultJoomlaGroupsReturnsGood(): void
    {
        // Joomla ships with 9 default groups
        $database = MockDatabaseFactory::createWithResult(9);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('9 user groups', $result->description);
    }
}
