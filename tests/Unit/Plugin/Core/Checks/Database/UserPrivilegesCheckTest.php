<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Database;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\UserPrivilegesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserPrivilegesCheck::class)]
class UserPrivilegesCheckTest extends TestCase
{
    private UserPrivilegesCheck $check;

    protected function setUp(): void
    {
        $this->check = new UserPrivilegesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.user_privileges', $this->check->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->check->getCategory());
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

    public function testRunReturnsGoodWhenAllPrivilegesGranted(): void
    {
        $database = MockDatabaseFactory::createWithColumn([
            "GRANT ALL PRIVILEGES ON `test_db`.* TO 'user'@'localhost'",
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('all required privileges', $result->description);
    }

    public function testRunReturnsGoodWhenAllIndividualPrivilegesGranted(): void
    {
        // All 8 required privileges granted individually: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX
        $database = MockDatabaseFactory::createWithColumn([
            "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX ON `test_db`.* TO 'user'@'localhost'",
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('all required privileges', $result->description);
    }

    public function testRunReturnsGoodWhenPrivilegesSpreadAcrossMultipleGrants(): void
    {
        // Privileges split across multiple GRANT statements
        $database = MockDatabaseFactory::createWithColumn([
            "GRANT SELECT, INSERT, UPDATE, DELETE ON `test_db`.* TO 'user'@'localhost'",
            "GRANT CREATE, DROP ON `test_db`.* TO 'user'@'localhost'",
            "GRANT ALTER, INDEX ON `test_db`.* TO 'user'@'localhost'",
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('all required privileges', $result->description);
    }

    public function testRunReturnsWarningWhenSomePrivilegesMissing(): void
    {
        // Missing CREATE, DROP, ALTER, INDEX privileges
        $database = MockDatabaseFactory::createWithColumn([
            "GRANT SELECT, INSERT, UPDATE, DELETE ON `test_db`.* TO 'user'@'localhost'",
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('may be missing privileges', $result->description);
        $this->assertStringContainsString('CREATE', $result->description);
        $this->assertStringContainsString('DROP', $result->description);
        $this->assertStringContainsString('ALTER', $result->description);
        $this->assertStringContainsString('INDEX', $result->description);
    }

    public function testRunReturnsWarningWhenOnlySelectGranted(): void
    {
        // Only SELECT privilege granted
        $database = MockDatabaseFactory::createWithColumn(["GRANT SELECT ON `test_db`.* TO 'user'@'localhost'"]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('may be missing privileges', $result->description);
    }

    public function testRunReturnsWarningWhenExceptionThrown(): void
    {
        $database = MockDatabaseFactory::createWithException(new \RuntimeException('Access denied'));
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Unable to check', $result->description);
        $this->assertStringContainsString('Access denied', $result->description);
    }

    public function testRunReturnsGoodWithCaseInsensitiveAllPrivileges(): void
    {
        // ALL PRIVILEGES in different cases
        $database = MockDatabaseFactory::createWithColumn([
            "GRANT all privileges ON `test_db`.* TO 'user'@'localhost'",
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsGoodWithCaseInsensitivePrivileges(): void
    {
        // Individual privileges in lowercase
        $database = MockDatabaseFactory::createWithColumn([
            "GRANT select, insert, update, delete, create, drop, alter, index ON `test_db`.* TO 'user'@'localhost'",
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenOnlyIndexMissing(): void
    {
        // Only INDEX privilege is missing
        $database = MockDatabaseFactory::createWithColumn([
            "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER ON `test_db`.* TO 'user'@'localhost'",
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('INDEX', $result->description);
    }

    public function testRunReturnsGoodWhenPrivilegesFromGlobalAndDatabase(): void
    {
        // Privileges from global (*.*) and database grants
        $database = MockDatabaseFactory::createWithColumn([
            "GRANT SELECT, INSERT ON *.* TO 'user'@'localhost'",
            "GRANT UPDATE, DELETE, CREATE, DROP, ALTER, INDEX ON `test_db`.* TO 'user'@'localhost'",
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunHandlesEmptyGrantsList(): void
    {
        // Empty grants list (somehow no grants returned)
        $database = MockDatabaseFactory::createWithColumn([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('may be missing privileges', $result->description);
    }
}
