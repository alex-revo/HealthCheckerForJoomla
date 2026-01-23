<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\SearchPluginsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchPluginsCheck::class)]
class SearchPluginsCheckTest extends TestCase
{
    private SearchPluginsCheck $check;

    protected function setUp(): void
    {
        $this->check = new SearchPluginsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.search_plugins', $this->check->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->check->getCategory());
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
        // Finder disabled (enabled = 0), plugins count = 0, total plugins = 0
        $database = MockDatabaseFactory::createWithSequentialResults([0, 0, 0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('not enabled', strtolower($result->description));
    }

    public function testRunWithSmartSearchEnabledButNoPluginsReturnsWarning(): void
    {
        // Finder enabled (enabled = 1), plugins enabled = 0, total plugins = 5
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0, 5]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('no search plugins', strtolower($result->description));
    }

    public function testRunWithPluginsEnabledButEmptyIndexReturnsWarning(): void
    {
        // Finder enabled, 3 plugins enabled, 5 total plugins, 0 indexed items
        $database = MockDatabaseFactory::createWithSequentialResults([1, 3, 5, 0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('empty', strtolower($result->description));
        $this->assertStringContainsString('indexer', strtolower($result->description));
    }

    public function testRunWithPluginsEnabledAndPopulatedIndexReturnsGood(): void
    {
        // Finder enabled, 3 plugins enabled, 5 total plugins, 150 indexed items
        $database = MockDatabaseFactory::createWithSequentialResults([1, 3, 5, 150]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('3', $result->description);
        $this->assertStringContainsString('5', $result->description);
        $this->assertStringContainsString('150', $result->description);
    }

    public function testRunWithAllPluginsEnabledReturnsGood(): void
    {
        // Finder enabled, all 5 plugins enabled, 5 total plugins, 500 indexed items
        $database = MockDatabaseFactory::createWithSequentialResults([1, 5, 5, 500]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('5 of 5', $result->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }
}
