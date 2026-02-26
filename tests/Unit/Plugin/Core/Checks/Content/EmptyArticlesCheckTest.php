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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\EmptyArticlesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmptyArticlesCheck::class)]
class EmptyArticlesCheckTest extends TestCase
{
    private EmptyArticlesCheck $emptyArticlesCheck;

    protected function setUp(): void
    {
        $this->emptyArticlesCheck = new EmptyArticlesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.empty_articles', $this->emptyArticlesCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->emptyArticlesCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->emptyArticlesCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->emptyArticlesCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->emptyArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoEmptyArticlesReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->emptyArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->emptyArticlesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertSame('COM_HEALTHCHECKER_CHECK_CONTENT_EMPTY_ARTICLES_GOOD_2', $healthCheckResult->description);
    }

    public function testRunWithFewEmptyArticlesReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(3);
        $this->emptyArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->emptyArticlesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertSame('COM_HEALTHCHECKER_CHECK_CONTENT_EMPTY_ARTICLES_GOOD', $healthCheckResult->description);
    }

    public function testRunWithManyEmptyArticlesReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(10);
        $this->emptyArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->emptyArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertSame('COM_HEALTHCHECKER_CHECK_CONTENT_EMPTY_ARTICLES_WARNING', $healthCheckResult->description);
    }
}
