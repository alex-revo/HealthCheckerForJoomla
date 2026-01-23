<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\SmartSearchIndexCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SmartSearchIndexCheck::class)]
class SmartSearchIndexCheckTest extends TestCase
{
    private SmartSearchIndexCheck $check;

    protected function setUp(): void
    {
        $this->check = new SmartSearchIndexCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.smart_search_index', $this->check->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->check->getCategory());
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
        $this->assertStringContainsString('database', strtolower($result->description));
    }

    public function testRunWithSmartSearchDisabledReturnsGood(): void
    {
        // com_finder not enabled (first query returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('not enabled', strtolower($result->description));
    }

    public function testRunWithSmartSearchEnabledAndEmptyIndexReturnsWarning(): void
    {
        // com_finder enabled (first query returns 1), index empty (second query returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('empty', strtolower($result->description));
    }

    public function testRunWithSmartSearchEnabledAndPopulatedIndexReturnsGood(): void
    {
        // com_finder enabled (first query returns 1), index has 150 items (second query returns 150)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 150]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('150', $result->description);
    }

    public function testRunWithLargeIndexReturnsGood(): void
    {
        // com_finder enabled with 10000 items indexed
        $database = MockDatabaseFactory::createWithSequentialResults([1, 10000]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('10000', $result->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // Try various scenarios
        $database = MockDatabaseFactory::createWithSequentialResults([0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testWarningMessageContainsIndexerInstruction(): void
    {
        // Smart Search enabled but empty index
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertStringContainsString('indexer', strtolower($result->description));
    }
}
