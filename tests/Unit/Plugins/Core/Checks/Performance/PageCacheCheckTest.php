<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\Performance;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\PageCacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageCacheCheck::class)]
class PageCacheCheckTest extends TestCase
{
    private PageCacheCheck $check;

    protected function setUp(): void
    {
        $this->check = new PageCacheCheck();
        // Reset plugin helper state for test isolation
        PluginHelper::resetEnabled();
    }

    protected function tearDown(): void
    {
        // Reset plugin helper state after each test
        PluginHelper::resetEnabled();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.page_cache', $this->check->getSlug());
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

    public function testRunWithPluginDisabledReturnsWarning(): void
    {
        // PluginHelper::isEnabled returns false by default in stub
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testRunWithPluginEnabledAndBrowserCacheEnabledReturnsGood(): void
    {
        // Enable the plugin (element name is 'cache', not 'pagecache')
        PluginHelper::setEnabled('system', 'cache', true);

        $params = json_encode([
            'browsercache' => 1,
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('browser caching', $result->description);
    }

    public function testRunWithPluginEnabledAndBrowserCacheDisabledReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        $params = json_encode([
            'browsercache' => 0,
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('browser caching is disabled', $result->description);
    }

    public function testRunWithEmptyParamsReturnsGood(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        $database = MockDatabaseFactory::createWithResult('');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Plugin is enabled, params empty - still good (can't determine browser cache state)
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('enabled', $result->description);
    }

    public function testRunWithInvalidJsonParamsReturnsGood(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        $database = MockDatabaseFactory::createWithResult('invalid-json{');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Plugin is enabled, can't parse params - still good
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithMissingBrowserCacheParamReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        $params = json_encode([
            'other_setting' => 1,
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Missing browsercache param defaults to 0, so warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunWithNullParamsReturnsGood(): void
    {
        // Enable the plugin so we get to the params check
        PluginHelper::setEnabled('system', 'cache', true);

        $database = MockDatabaseFactory::createWithResult(null);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Plugin enabled but can't read params - still good
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('enabled', $result->description);
    }

    public function testRunWithBrowserCacheStringValueEnabledReturnsGood(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        // Test with string '1' instead of integer
        $params = json_encode([
            'browsercache' => '1',
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('browser caching', $result->description);
    }

    public function testRunWithBrowserCacheStringValueDisabledReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        // Test with string '0' instead of integer
        $params = json_encode([
            'browsercache' => '0',
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('browser caching is disabled', $result->description);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame('performance.page_cache', $result->slug);
        $this->assertSame('performance', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $result = $this->check->run();

        // Page cache check only returns Good or Warning, never Critical
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'Page cache check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testResultDescriptionMentionsCache(): void
    {
        $result = $this->check->run();

        $this->assertStringContainsStringIgnoringCase('cache', $result->description);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $result = $this->check->run();

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('provider', $array);
    }
}
