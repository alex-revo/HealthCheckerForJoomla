<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\CachePluginCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CachePluginCheck::class)]
class CachePluginCheckTest extends TestCase
{
    private CachePluginCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        Factory::setApplication($this->app);
        $this->check = new CachePluginCheck();
        PluginHelper::resetEnabled();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
        PluginHelper::resetEnabled();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.cache_plugin', $this->check->getSlug());
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

    public function testRunReturnsWarningWhenBothDisabled(): void
    {
        // Both plugin and system cache disabled
        $this->app->set('caching', 0);
        PluginHelper::setEnabled('system', 'cache', false);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testRunReturnsGoodWhenSystemCacheEnabledButPluginDisabled(): void
    {
        // System cache enabled but plugin disabled - basic caching works
        $this->app->set('caching', 1);
        PluginHelper::setEnabled('system', 'cache', false);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('System caching is enabled', $result->description);
    }

    public function testRunReturnsWarningWhenPluginEnabledButSystemCacheDisabled(): void
    {
        // Plugin enabled but system caching is off - plugin will not function
        $this->app->set('caching', 0);
        PluginHelper::setEnabled('system', 'cache', true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('plugin is enabled but system caching is disabled', $result->description);
    }

    public function testRunReturnsGoodWhenBothEnabled(): void
    {
        // Both enabled - optimal configuration
        $this->app->set('caching', 1);
        $this->app->set('cache_handler', 'file');
        $this->app->set('cachetime', 30);
        PluginHelper::setEnabled('system', 'cache', true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Page cache plugin is enabled', $result->description);
        $this->assertStringContainsString('file', $result->description);
        $this->assertStringContainsString('30', $result->description);
    }

    public function testRunReturnsGoodWithMemcachedHandler(): void
    {
        $this->app->set('caching', 1);
        $this->app->set('cache_handler', 'memcached');
        $this->app->set('cachetime', 60);
        PluginHelper::setEnabled('system', 'cache', true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('memcached', $result->description);
        $this->assertStringContainsString('60 minutes', $result->description);
    }

    public function testRunReturnsGoodWithRedisHandler(): void
    {
        $this->app->set('caching', 1);
        $this->app->set('cache_handler', 'redis');
        $this->app->set('cachetime', 15);
        PluginHelper::setEnabled('system', 'cache', true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('redis', $result->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // This check never returns critical status
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }
}
