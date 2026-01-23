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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\BrokenLinksCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BrokenLinksCheck::class)]
class BrokenLinksCheckTest extends TestCase
{
    private BrokenLinksCheck $check;

    protected function setUp(): void
    {
        $this->check = new BrokenLinksCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.broken_links', $this->check->getSlug());
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

    public function testRunWithRedirectComponentNotInstalledReturnsGood(): void
    {
        // First query checks if com_redirect is installed (0 = not installed)
        $database = MockDatabaseFactory::createWithResult(0);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('not installed', $result->description);
    }

    public function testRunWithNo404ErrorsReturnsGood(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 0
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('No unhandled 404 errors', $result->description);
    }

    public function testRunWithFew404ErrorsReturnsGood(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 20
        $database = MockDatabaseFactory::createWithSequentialResults([1, 20]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('20 unhandled', $result->description);
        $this->assertStringContainsString('Consider creating redirects', $result->description);
    }

    public function testRunWithMany404ErrorsReturnsWarning(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 75
        $database = MockDatabaseFactory::createWithSequentialResults([1, 75]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('75 unhandled', $result->description);
        $this->assertStringContainsString('Review', $result->description);
    }

    public function testRunWithExactlyThreshold404sReturnsGood(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 50 (threshold is >50)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 50]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithAboveThreshold404sReturnsWarning(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 51
        $database = MockDatabaseFactory::createWithSequentialResults([1, 51]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }
}
