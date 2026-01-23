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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\AdminEmailCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdminEmailCheck::class)]
class AdminEmailCheckTest extends TestCase
{
    private AdminEmailCheck $check;

    protected function setUp(): void
    {
        $this->check = new AdminEmailCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.admin_email', $this->check->getSlug());
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

    public function testRunWithNoSuperAdminsReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('No active Super Admin', $result->description);
    }

    public function testRunWithValidEmailsReturnsGood(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@company.com',
            ],
            (object) [
                'id' => 2,
                'username' => 'manager',
                'email' => 'manager@company.com',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('2 Super Admin', $result->description);
        $this->assertStringContainsString('valid email', $result->description);
    }

    public function testRunWithEmptyEmailReturnsCritical(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin',
                'email' => '',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('admin', $result->description);
        $this->assertStringContainsString('no email', $result->description);
    }

    public function testRunWithInvalidEmailFormatReturnsCritical(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin',
                'email' => 'not-an-email',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('admin', $result->description);
        $this->assertStringContainsString('invalid format', $result->description);
    }

    public function testRunWithExampleDomainReturnsCritical(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('admin', $result->description);
        $this->assertStringContainsString('example.com', $result->description);
    }

    public function testRunWithMailinatorDomainReturnsCritical(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@mailinator.com',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('mailinator.com', $result->description);
    }

    public function testRunWithMultipleInvalidEmailsReturnsCritical(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin1',
                'email' => 'admin@example.com',
            ],
            (object) [
                'id' => 2,
                'username' => 'admin2',
                'email' => '',
            ],
            (object) [
                'id' => 3,
                'username' => 'admin3',
                'email' => 'valid@company.com',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('admin1', $result->description);
        $this->assertStringContainsString('admin2', $result->description);
    }
}
