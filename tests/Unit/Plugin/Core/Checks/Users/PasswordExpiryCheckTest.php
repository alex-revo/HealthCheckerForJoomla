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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\PasswordExpiryCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordExpiryCheck::class)]
class PasswordExpiryCheckTest extends TestCase
{
    private PasswordExpiryCheck $check;

    protected function setUp(): void
    {
        $this->check = new PasswordExpiryCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.password_expiry', $this->check->getSlug());
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

    public function testRunWithAllPasswordsRecentReturnsGood(): void
    {
        // First query: expired count = 0
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([0, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('recently updated', $result->description);
    }

    public function testRunWithFewExpiredPasswordsReturnsGood(): void
    {
        // First query: expired count = 20 (20% of total)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([20, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('20 of 100', $result->description);
        $this->assertStringContainsString('acceptable', $result->description);
    }

    public function testRunWithMediumExpiredPasswordsReturnsWarning(): void
    {
        // First query: expired count = 30 (30% of total, above 25% threshold)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([30, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('30 of 100', $result->description);
    }

    public function testRunWithHighExpiredPasswordsReturnsWarning(): void
    {
        // First query: expired count = 80 (80% of total, above 75% threshold)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([80, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('80%', $result->description);
        $this->assertStringContainsString('password policy', $result->description);
    }

    public function testRunWithExactly25PercentReturnsGood(): void
    {
        // First query: expired count = 25 (exactly 25%)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([25, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithAbove25PercentReturnsWarning(): void
    {
        // First query: expired count = 26 (26%, above 25% threshold)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([26, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunWithExactly75PercentReturnsWarningWithPolicyMessage(): void
    {
        // First query: expired count = 75 (exactly 75%)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([75, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        // 75% is at threshold, message mentions reviewing policies
        $this->assertStringContainsString('password policies', $result->description);
    }

    public function testRunWithAbove75PercentMentionsImplementingPolicy(): void
    {
        // First query: expired count = 76 (76%, above 75% threshold)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([76, 100]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        // Above 75% mentions implementing policy
        $this->assertStringContainsString('implementing', $result->description);
    }
}
