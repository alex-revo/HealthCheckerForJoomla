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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\CategoryDepthCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CategoryDepthCheck::class)]
class CategoryDepthCheckTest extends TestCase
{
    private CategoryDepthCheck $check;

    protected function setUp(): void
    {
        $this->check = new CategoryDepthCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.category_depth', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenNoDeepCategories(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('No categories', $result->description);
    }

    public function testRunReturnsWarningWhenDeepCategoriesExist(): void
    {
        // First query returns count of deep categories (5), second query returns max level (8)
        $database = MockDatabaseFactory::createWithSequentialResults([5, 8]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('5 categories are', $result->description);
        $this->assertStringContainsString('max depth: 8', $result->description);
    }

    public function testRunReturnsWarningSingularWhenOneCategoryDeep(): void
    {
        // First query returns count of deep categories (1), second query returns max level (7)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 7]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('1 category is', $result->description);
        $this->assertStringContainsString('max depth: 7', $result->description);
    }

    public function testRunReturnsWarningWithHighMaxDepth(): void
    {
        // First query returns count of deep categories (10), second query returns max level (15)
        $database = MockDatabaseFactory::createWithSequentialResults([10, 15]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('10 categories are', $result->description);
        $this->assertStringContainsString('max depth: 15', $result->description);
        $this->assertStringContainsString('UX issues', $result->description);
    }
}
