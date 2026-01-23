<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Content;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\OrphanedArticlesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrphanedArticlesCheck::class)]
class OrphanedArticlesCheckTest extends TestCase
{
    private OrphanedArticlesCheck $check;

    protected function setUp(): void
    {
        $this->check = new OrphanedArticlesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.orphaned_articles', $this->check->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->check->getCategory());
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

    public function testRunWithNoOrphanedArticlesReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('All published articles', $result->description);
    }

    public function testRunWithFewOrphanedArticlesReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(5);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('5 published articles', $result->description);
        $this->assertStringContainsString('intentional', $result->description);
    }

    public function testRunWithManyOrphanedArticlesReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(15);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('15 published articles', $result->description);
    }

    public function testRunWithExactlyTenOrphanedArticlesReturnsGood(): void
    {
        // Boundary test: exactly 10 orphaned articles should return good (>10 triggers warning)
        $database = MockDatabaseFactory::createWithResult(10);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('10 published articles', $result->description);
        $this->assertStringContainsString('intentional', $result->description);
    }

    public function testRunWithElevenOrphanedArticlesReturnsWarning(): void
    {
        // Boundary test: 11 orphaned articles should return warning (>10)
        $database = MockDatabaseFactory::createWithResult(11);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('11 published articles', $result->description);
    }

    public function testRunReturnsWarningOnDatabaseException(): void
    {
        $database = MockDatabaseFactory::createWithException(new \RuntimeException('Database connection failed'));
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Unable to check', $result->description);
        $this->assertStringContainsString('Database connection failed', $result->description);
    }
}
