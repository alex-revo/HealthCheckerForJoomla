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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\LastLoginCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LastLoginCheck::class)]
class LastLoginCheckTest extends TestCase
{
    private LastLoginCheck $check;

    protected function setUp(): void
    {
        $this->check = new LastLoginCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.last_login', $this->check->getSlug());
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

    public function testRunWithAllUsersLoggedInReturnsGood(): void
    {
        // First query: count of never logged in = 0
        // Second query: total users = 10
        $database = MockDatabaseFactory::createWithSequentialResults([0, 10]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('All active users have logged in', $result->description);
    }

    public function testRunWithFewNeverLoggedInUsersReturnsGood(): void
    {
        // First query: count of never logged in = 10
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([10, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('10 of 100', $result->description);
        $this->assertStringContainsString('never logged in', $result->description);
    }

    public function testRunWithManyNeverLoggedInUsersReturnsWarning(): void
    {
        // First query: count of never logged in = 75
        // Second query: total users = 150
        $database = MockDatabaseFactory::createWithSequentialResults([75, 150]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('75 of 150', $result->description);
        $this->assertStringContainsString('review', $result->description);
    }

    public function testRunWithExactlyThresholdNeverLoggedInUsersReturnsWarning(): void
    {
        // First query: count of never logged in = 51 (threshold is >50)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([51, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunWithBelowThresholdReturnsGood(): void
    {
        // First query: count of never logged in = 50 (threshold is >50)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([50, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}
