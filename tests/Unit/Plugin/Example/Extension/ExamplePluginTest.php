<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Example\Extension;

use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectProvidersEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Plugin\Example\Checks\CustomConfigCheck;
use MySitesGuru\HealthChecker\Plugin\Example\Checks\ThirdPartyServiceCheck;
use MySitesGuru\HealthChecker\Plugin\Example\Extension\ExamplePlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExamplePlugin::class)]
class ExamplePluginTest extends TestCase
{
    public function testGetSubscribedEventsReturnsCorrectMapping(): void
    {
        $events = ExamplePlugin::getSubscribedEvents();

        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_CATEGORIES->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_CHECKS->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_PROVIDERS->value, $events);
    }

    public function testGetSubscribedEventsReturnsCorrectHandlerMethods(): void
    {
        $events = ExamplePlugin::getSubscribedEvents();

        $this->assertSame('onCollectCategories', $events[HealthCheckerEvents::COLLECT_CATEGORIES->value]);
        $this->assertSame('onCollectChecks', $events[HealthCheckerEvents::COLLECT_CHECKS->value]);
        $this->assertSame('onCollectProviders', $events[HealthCheckerEvents::COLLECT_PROVIDERS->value]);
    }

    public function testOnCollectCategoriesAddsThirdPartyCategory(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectCategoriesEvent();

        $plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertCount(1, $categories);
        $this->assertInstanceOf(HealthCategory::class, $categories[0]);
        $this->assertSame('thirdparty', $categories[0]->slug);
        $this->assertSame('COM_HEALTHCHECKER_CATEGORY_THIRDPARTY', $categories[0]->label);
        $this->assertSame('fa-plug', $categories[0]->icon);
        $this->assertSame(90, $categories[0]->sortOrder);
    }

    public function testOnCollectChecksAddsTwoChecks(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectChecksEvent();

        $plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $this->assertCount(2, $checks);
    }

    public function testOnCollectChecksAddsCustomConfigCheck(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectChecksEvent();

        $plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $hasCustomConfigCheck = false;

        foreach ($checks as $check) {
            if ($check instanceof CustomConfigCheck) {
                $hasCustomConfigCheck = true;

                break;
            }
        }

        $this->assertTrue($hasCustomConfigCheck, 'CustomConfigCheck should be registered');
    }

    public function testOnCollectChecksAddsThirdPartyServiceCheck(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectChecksEvent();

        $plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $hasThirdPartyServiceCheck = false;

        foreach ($checks as $check) {
            if ($check instanceof ThirdPartyServiceCheck) {
                $hasThirdPartyServiceCheck = true;

                break;
            }
        }

        $this->assertTrue($hasThirdPartyServiceCheck, 'ThirdPartyServiceCheck should be registered');
    }

    public function testOnCollectProvidersAddsExampleProvider(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertCount(1, $providers);
        $this->assertInstanceOf(ProviderMetadata::class, $providers[0]);
    }

    public function testOnCollectProvidersRegistersCorrectSlug(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('example', $providers[0]->slug);
    }

    public function testOnCollectProvidersRegistersCorrectName(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('Example Provider', $providers[0]->name);
    }

    public function testOnCollectProvidersRegistersCorrectDescription(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('Example health checks demonstrating the SDK', $providers[0]->description);
    }

    public function testOnCollectProvidersRegistersCorrectUrl(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame(
            'https://github.com/mySites-guru/HealthCheckerForJoomla/tree/main/healthchecker/plugins/example',
            $providers[0]->url,
        );
    }

    public function testOnCollectProvidersRegistersCorrectIcon(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('fa-flask', $providers[0]->icon);
    }

    public function testOnCollectProvidersRegistersCorrectVersion(): void
    {
        $plugin = new ExamplePlugin(new \stdClass());
        $event = new CollectProvidersEvent();

        $plugin->onCollectProviders($event);

        $providers = $event->getProviders();
        $this->assertSame('1.0.0', $providers[0]->version);
    }
}
