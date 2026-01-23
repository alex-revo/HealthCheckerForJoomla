<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\MySitesGuru\Extension;

use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Plugin\MySitesGuru\Checks\MySitesGuruConnectionCheck;
use MySitesGuru\HealthChecker\Plugin\MySitesGuru\Extension\MySitesGuruPlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MySitesGuruPlugin::class)]
class MySitesGuruPluginTest extends TestCase
{
    public function testGetSubscribedEventsReturnsCorrectMapping(): void
    {
        $events = MySitesGuruPlugin::getSubscribedEvents();

        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_CATEGORIES->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_CHECKS->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_PROVIDERS->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::AFTER_TOOLBAR_BUILD->value, $events);
    }

    public function testGetSubscribedEventsReturnsCorrectHandlerMethods(): void
    {
        $events = MySitesGuruPlugin::getSubscribedEvents();

        $this->assertSame('onCollectCategories', $events[HealthCheckerEvents::COLLECT_CATEGORIES->value]);
        $this->assertSame('onCollectChecks', $events[HealthCheckerEvents::COLLECT_CHECKS->value]);
        $this->assertSame('onCollectProviders', $events[HealthCheckerEvents::COLLECT_PROVIDERS->value]);
        $this->assertSame('onBeforeReportDisplay', $events[HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value]);
        $this->assertSame('onAfterToolbarBuild', $events[HealthCheckerEvents::AFTER_TOOLBAR_BUILD->value]);
    }

    public function testOnCollectCategoriesAddsMySitesGuruCategory(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertCount(1, $categories);
        $this->assertInstanceOf(HealthCategory::class, $categories[0]);
    }

    public function testOnCollectCategoriesRegistersCorrectSlug(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertSame('mysitesguru', $categories[0]->slug);
    }

    public function testOnCollectCategoriesRegistersCorrectLabel(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertSame('mySites.guru Integration', $categories[0]->label);
    }

    public function testOnCollectCategoriesRegistersCorrectIcon(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertSame('fa-tachometer-alt', $categories[0]->icon);
    }

    public function testOnCollectCategoriesRegistersCorrectSortOrder(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertSame(90, $categories[0]->sortOrder);
    }

    public function testOnCollectCategoriesRegistersLogoUrl(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertSame('/media/plg_healthchecker_mysitesguru/logo.png', $categories[0]->logoUrl);
    }

    public function testOnCollectChecksAddsConnectionCheck(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectChecksEvent();

        $plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $this->assertCount(1, $checks);
        $this->assertInstanceOf(MySitesGuruConnectionCheck::class, $checks[0]);
    }

    public function testOnCollectProvidersAddsMySitesGuruProvider(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertCount(1, $providers);
        $this->assertInstanceOf(ProviderMetadata::class, $providers[0]);
    }

    public function testOnCollectProvidersRegistersCorrectSlug(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('mysitesguru', $providers[0]->slug);
    }

    public function testOnCollectProvidersRegistersCorrectName(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('mySites.guru', $providers[0]->name);
    }

    public function testOnCollectProvidersRegistersCorrectDescription(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame(
            'Joomla Monitoring Dashboard - Monitor unlimited sites from one place',
            $providers[0]->description,
        );
    }

    public function testOnCollectProvidersRegistersCorrectUrl(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('https://mysites.guru', $providers[0]->url);
    }

    public function testOnCollectProvidersRegistersCorrectIcon(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('fa-tachometer-alt', $providers[0]->icon);
    }

    public function testOnCollectProvidersRegistersCorrectLogoUrl(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('/media/plg_healthchecker_mysitesguru/logo.png', $providers[0]->logoUrl);
    }

    public function testOnCollectProvidersRegistersCorrectVersion(): void
    {
        $plugin = new MySitesGuruPlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('1.0.0', $providers[0]->version);
    }
}
