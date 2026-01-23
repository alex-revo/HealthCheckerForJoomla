<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Users;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\DefaultUserGroupCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultUserGroupCheck::class)]
class DefaultUserGroupCheckTest extends TestCase
{
    private DefaultUserGroupCheck $check;

    protected function setUp(): void
    {
        $this->check = new DefaultUserGroupCheck();
    }

    protected function tearDown(): void
    {
        ComponentHelper::resetParams();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.default_user_group', $this->check->getSlug());
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

    public function testRunWithDangerousAdministratorGroupReturnsCritical(): void
    {
        $params = new Registry([
            'new_usertype' => 7,
        ]); // Administrator group
        ComponentHelper::setParams('com_users', $params);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('Administrator or Super Users', $result->description);
        $this->assertStringContainsString('critical security risk', $result->description);
    }

    public function testRunWithDangerousSuperUsersGroupReturnsCritical(): void
    {
        $params = new Registry([
            'new_usertype' => 8,
        ]); // Super Users group
        ComponentHelper::setParams('com_users', $params);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('Administrator or Super Users', $result->description);
    }

    public function testRunWithSafeRegisteredGroupReturnsGood(): void
    {
        $params = new Registry([
            'new_usertype' => 2,
        ]); // Registered group
        ComponentHelper::setParams('com_users', $params);

        $database = MockDatabaseFactory::createWithResult('Registered');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Default user group: Registered', $result->description);
    }

    public function testRunWithSafeGroupReturnsGroupName(): void
    {
        $params = new Registry([
            'new_usertype' => 3,
        ]); // Author group
        ComponentHelper::setParams('com_users', $params);

        $database = MockDatabaseFactory::createWithResult('Author');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Author', $result->description);
    }

    public function testRunWithSafeGroupNoNameShowsGroupId(): void
    {
        $params = new Registry([
            'new_usertype' => 5,
        ]); // Custom group
        ComponentHelper::setParams('com_users', $params);

        $database = MockDatabaseFactory::createWithResult(null); // Group name not found
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('ID 5', $result->description);
    }

    public function testRunWithDefaultGroupValueReturnsGood(): void
    {
        // No params set, should use default value of 2 (Registered)
        $database = MockDatabaseFactory::createWithResult('Registered');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $params = new Registry([
            'new_usertype' => 2,
        ]);
        ComponentHelper::setParams('com_users', $params);

        // No database set - should fail with warning for safe group
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }
}
