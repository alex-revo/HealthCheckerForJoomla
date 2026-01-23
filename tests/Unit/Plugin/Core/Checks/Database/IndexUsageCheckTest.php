<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Database;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\IndexUsageCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexUsageCheck::class)]
class IndexUsageCheckTest extends TestCase
{
    private IndexUsageCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        $this->app->set('dbprefix', 'test_');
        $this->app->set('db', 'test_database');
        Factory::setApplication($this->app);
        $this->check = new IndexUsageCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.index_usage', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenNoTables(): void
    {
        // Mock returns empty column for table list
        $database = MockDatabaseFactory::createWithColumn([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('0 tables', $result->description);
    }

    public function testRunReturnsGoodWhenAllTablesHaveIndexes(): void
    {
        // Tables returned by SHOW TABLES
        // Second query for SHOW INDEX returns indexes for that table
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_content', 'test_users'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                    (object) [
                        'Key_name' => 'idx_state',
                        'Column_name' => 'state',
                    ],
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                    (object) [
                        'Key_name' => 'idx_email',
                        'Column_name' => 'email',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('2 tables', $result->description);
        $this->assertStringContainsString('primary keys', $result->description);
    }

    public function testRunReturnsWarningWhenTableMissingPrimaryKey(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_custom'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    // No PRIMARY key, only regular index
                    (object) [
                        'Key_name' => 'idx_state',
                        'Column_name' => 'state',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('missing primary key', $result->description);
        $this->assertStringContainsString('test_custom', $result->description);
    }

    public function testRunReturnsWarningWhenTableHasNoIndexes(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_noindex'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ], // No indexes at all
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        // Could be "no indexes" or "missing primary key" depending on implementation
        $this->assertTrue(
            str_contains($result->description, 'no indexes') || str_contains(
                $result->description,
                'missing primary key',
            ),
        );
    }

    public function testRunExcludesTablesDesignedWithoutPrimaryKey(): void
    {
        // Tables like contentitem_tag_map are designed without primary keys
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_contentitem_tag_map', 'test_content'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    // contentitem_tag_map - only has composite index, no PRIMARY
                    (object) [
                        'Key_name' => 'idx_tag_type',
                        'Column_name' => 'tag_id',
                    ],
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    // content - has PRIMARY
                    (object) [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Should be GOOD because contentitem_tag_map is excluded from primary key check
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReportsMultipleTablesMissingPrimaryKey(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [
                    'test_custom1',
                    'test_custom2',
                    'test_custom3',
                    'test_custom4',
                    'test_custom5',
                    'test_custom6', // 6 tables to test truncation
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ], // No indexes
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('6 table(s)', $result->description);
        // Should show only first 5 and then "..."
        $this->assertStringContainsString('...', $result->description);
    }
}
