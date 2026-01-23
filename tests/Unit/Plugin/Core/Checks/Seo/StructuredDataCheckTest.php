<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\StructuredDataCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StructuredDataCheck::class)]
class StructuredDataCheckTest extends TestCase
{
    private StructuredDataCheck $check;

    protected function setUp(): void
    {
        $this->check = new StructuredDataCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.structured_data', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->check->getCategory());
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

    public function testRunWithNoStructuredDataPluginsReturnsGood(): void
    {
        // This check returns Good even when no plugins found (informational only)
        $database = MockDatabaseFactory::createWithResult(0);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('No structured data plugin', $result->description);
        $this->assertStringContainsString('Consider adding', $result->description);
    }

    public function testRunWithStructuredDataPluginReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(1);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('1 enabled plugin', $result->description);
        $this->assertStringContainsString('Schema.org', $result->description);
    }

    public function testRunWithMultipleStructuredDataPluginsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(2);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('2 enabled plugin', $result->description);
    }

    public function testRunAlwaysReturnsGood(): void
    {
        // This check never returns warning/critical - it's purely informational
        $database = MockDatabaseFactory::createWithResult(0);
        $this->check->setDatabase($database);

        $result = $this->check->run();
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}
