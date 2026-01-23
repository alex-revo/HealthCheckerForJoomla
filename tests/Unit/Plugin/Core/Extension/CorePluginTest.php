<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Extension;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectCategoriesEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\CollectChecksEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use MySitesGuru\HealthChecker\Plugin\Core\Extension\CorePlugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CorePlugin::class)]
class CorePluginTest extends TestCase
{
    private CorePlugin $plugin;

    protected function setUp(): void
    {
        $this->plugin = new CorePlugin(new \stdClass());

        // Set up params as a Registry object (required for ->get() method)
        $this->plugin->params = new Registry();

        // Set up database
        $database = $this->createMockDatabase();
        $this->plugin->setDatabase($database);
    }

    public function testGetSubscribedEventsReturnsExpectedEvents(): void
    {
        $events = CorePlugin::getSubscribedEvents();

        $this->assertIsArray($events);
        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_CATEGORIES->value, $events);
        $this->assertArrayHasKey(HealthCheckerEvents::COLLECT_CHECKS->value, $events);
    }

    public function testGetSubscribedEventsReturnsCorrectHandlerMethods(): void
    {
        $events = CorePlugin::getSubscribedEvents();

        $this->assertSame('onCollectCategories', $events[HealthCheckerEvents::COLLECT_CATEGORIES->value]);
        $this->assertSame('onCollectChecks', $events[HealthCheckerEvents::COLLECT_CHECKS->value]);
    }

    public function testGetSubscribedEventsDoesNotIncludeProviders(): void
    {
        $events = CorePlugin::getSubscribedEvents();

        // CorePlugin doesn't register a provider (it uses core provider from component)
        $this->assertArrayNotHasKey(HealthCheckerEvents::COLLECT_PROVIDERS->value, $events);
    }

    public function testOnCollectCategoriesRegisters8CoreCategories(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $this->assertCount(8, $categories);
    }

    public function testOnCollectCategoriesReturnsHealthCategoryInstances(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        foreach ($event->getCategories() as $category) {
            $this->assertInstanceOf(HealthCategory::class, $category);
        }
    }

    public function testOnCollectCategoriesRegistersSystemCategory(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $systemCategory = $this->findCategoryBySlug($categories, 'system');

        $this->assertNotNull($systemCategory);
        $this->assertSame('COM_HEALTHCHECKER_CATEGORY_SYSTEM', $systemCategory->label);
        $this->assertSame('fa-server', $systemCategory->icon);
        $this->assertSame(10, $systemCategory->sortOrder);
    }

    public function testOnCollectCategoriesRegistersDatabaseCategory(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $databaseCategory = $this->findCategoryBySlug($categories, 'database');

        $this->assertNotNull($databaseCategory);
        $this->assertSame('COM_HEALTHCHECKER_CATEGORY_DATABASE', $databaseCategory->label);
        $this->assertSame('fa-database', $databaseCategory->icon);
        $this->assertSame(20, $databaseCategory->sortOrder);
    }

    public function testOnCollectCategoriesRegistersSecurityCategory(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $securityCategory = $this->findCategoryBySlug($categories, 'security');

        $this->assertNotNull($securityCategory);
        $this->assertSame('COM_HEALTHCHECKER_CATEGORY_SECURITY', $securityCategory->label);
        $this->assertSame('fa-shield-alt', $securityCategory->icon);
        $this->assertSame(30, $securityCategory->sortOrder);
    }

    public function testOnCollectCategoriesRegistersUsersCategory(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $usersCategory = $this->findCategoryBySlug($categories, 'users');

        $this->assertNotNull($usersCategory);
        $this->assertSame('COM_HEALTHCHECKER_CATEGORY_USERS', $usersCategory->label);
        $this->assertSame('fa-users', $usersCategory->icon);
        $this->assertSame(40, $usersCategory->sortOrder);
    }

    public function testOnCollectCategoriesRegistersExtensionsCategory(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $extensionsCategory = $this->findCategoryBySlug($categories, 'extensions');

        $this->assertNotNull($extensionsCategory);
        $this->assertSame('COM_HEALTHCHECKER_CATEGORY_EXTENSIONS', $extensionsCategory->label);
        $this->assertSame('fa-puzzle-piece', $extensionsCategory->icon);
        $this->assertSame(50, $extensionsCategory->sortOrder);
    }

    public function testOnCollectCategoriesRegistersPerformanceCategory(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $performanceCategory = $this->findCategoryBySlug($categories, 'performance');

        $this->assertNotNull($performanceCategory);
        $this->assertSame('COM_HEALTHCHECKER_CATEGORY_PERFORMANCE', $performanceCategory->label);
        $this->assertSame('fa-tachometer-alt', $performanceCategory->icon);
        $this->assertSame(60, $performanceCategory->sortOrder);
    }

    public function testOnCollectCategoriesRegistersSeoCategory(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $seoCategory = $this->findCategoryBySlug($categories, 'seo');

        $this->assertNotNull($seoCategory);
        $this->assertSame('COM_HEALTHCHECKER_CATEGORY_SEO', $seoCategory->label);
        $this->assertSame('fa-search', $seoCategory->icon);
        $this->assertSame(70, $seoCategory->sortOrder);
    }

    public function testOnCollectCategoriesRegistersContentCategory(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $contentCategory = $this->findCategoryBySlug($categories, 'content');

        $this->assertNotNull($contentCategory);
        $this->assertSame('COM_HEALTHCHECKER_CATEGORY_CONTENT', $contentCategory->label);
        $this->assertSame('fa-file-alt', $contentCategory->icon);
        $this->assertSame(80, $contentCategory->sortOrder);
    }

    public function testOnCollectCategoriesRegistersCorrectSlugs(): void
    {
        $event = new CollectCategoriesEvent();

        $this->plugin->onCollectCategories($event);

        $categories = $event->getCategories();
        $slugs = array_map(static fn(HealthCategory $cat) => $cat->slug, $categories);

        $expectedSlugs = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        foreach ($expectedSlugs as $expectedSlug) {
            $this->assertContains($expectedSlug, $slugs);
        }
    }

    public function testAllChecksImplementHealthCheckInterface(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        foreach ($checks as $check) {
            $this->assertInstanceOf(HealthCheckInterface::class, $check);
        }
    }

    public function testAllChecksHaveValidProvider(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        foreach ($event->getChecks() as $check) {
            $provider = $check->getProvider();
            $this->assertSame('core', $provider, "Check {$check->getSlug()} has unexpected provider '{$provider}'");
        }
    }

    public function testAllChecksHaveValidCategory(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectCategories(new CollectCategoriesEvent());
        $this->plugin->onCollectChecks($event);

        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];

        foreach ($event->getChecks() as $check) {
            $category = $check->getCategory();
            $this->assertContains(
                $category,
                $validCategories,
                "Check {$check->getSlug()} has invalid category '{$category}'",
            );
        }
    }

    public function testAllChecksHaveUniqueSlugs(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $slugs = array_map(static fn(HealthCheckInterface $check) => $check->getSlug(), $checks);
        $uniqueSlugs = array_unique($slugs);

        $this->assertSame(count($slugs), count($uniqueSlugs), 'Not all check slugs are unique');
    }

    public function testAllChecksHaveTitles(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        foreach ($event->getChecks() as $check) {
            $title = $check->getTitle();
            $this->assertIsString($title);
            $this->assertNotEmpty($title, "Check {$check->getSlug()} has empty title");
        }
    }

    public function testCheckSlugsFollowCategoryDotNameFormat(): void
    {
        $event = new CollectChecksEvent();

        $this->plugin->onCollectChecks($event);

        foreach ($event->getChecks() as $check) {
            $slug = $check->getSlug();
            // Slug should be in format {category}.{check_name}
            $this->assertStringContainsString('.', $slug, "Check slug '{$slug}' should contain a dot separator");
            $parts = explode('.', $slug);
            $this->assertCount(2, $parts, "Check slug '{$slug}' should have exactly 2 parts separated by dot");
        }
    }

    public function testDisabledCheckNotRegistered(): void
    {
        // Create params with a disabled check
        // Slug is 'system.php_version' so param is 'check_system_php_version'
        $params = new Registry();
        $params->set('check_system_php_version', 0);
        $this->plugin->params = $params;

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $slugs = array_map(static fn(HealthCheckInterface $check) => $check->getSlug(), $checks);

        $this->assertNotContains('system.php_version', $slugs);
    }

    public function testEnabledCheckIsRegistered(): void
    {
        // Create params with an enabled check
        // Slug is 'system.php_version' so param is 'check_system_php_version'
        $params = new Registry();
        $params->set('check_system_php_version', 1);
        $this->plugin->params = $params;

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();
        $slugs = array_map(static fn(HealthCheckInterface $check) => $check->getSlug(), $checks);

        $this->assertContains('system.php_version', $slugs);
    }

    public function testChecksEnabledByDefault(): void
    {
        // Empty params means all checks should be enabled by default
        $params = new Registry();
        $this->plugin->params = $params;

        $event = new CollectChecksEvent();
        $this->plugin->onCollectChecks($event);

        $checks = $event->getChecks();

        // We should have checks registered
        $this->assertNotEmpty($checks, 'Checks should be enabled by default');
    }

    /**
     * Find a category by its slug from a list of categories
     *
     * @param array<HealthCategory> $categories
     */
    private function findCategoryBySlug(array $categories, string $slug): ?HealthCategory
    {
        foreach ($categories as $category) {
            if ($category->slug === $slug) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Create a mock database for testing
     */
    private function createMockDatabase(): DatabaseInterface
    {
        return new class implements DatabaseInterface {
            private int $queryCount = 0;

            public function getVersion(): string
            {
                return '8.0.30';
            }

            public function getQuery(bool $new = false): QueryInterface
            {
                return $this->createMockQuery();
            }

            public function setQuery(QueryInterface|string $query, int $offset = 0, int $limit = 0): self
            {
                return $this;
            }

            public function loadResult(): mixed
            {
                $this->queryCount++;

                return match ($this->queryCount) {
                    default => date('Y-m-d H:i:s', strtotime('-1 day')),
                };
            }

            public function loadColumn(): array
            {
                return [];
            }

            public function loadAssoc(): ?array
            {
                return null;
            }

            public function loadAssocList(string $key = '', string $column = ''): array
            {
                return [];
            }

            public function loadObject(): ?object
            {
                return null;
            }

            public function loadObjectList(): array
            {
                return [];
            }

            public function execute(): bool
            {
                return true;
            }

            public function quoteName(array|string $name, ?string $as = null): array|string
            {
                return is_array($name) ? '' : $name;
            }

            public function quote(array|string $text, bool $escape = true): array|string
            {
                return is_string($text) ? "'{$text}'" : '';
            }

            public function getPrefix(): string
            {
                return '#__';
            }

            public function getNullDate(): string
            {
                return '0000-00-00 00:00:00';
            }

            public function getTableList(): array
            {
                return [];
            }

            private function createMockQuery(): QueryInterface
            {
                return new class implements QueryInterface {
                    public function select(array|string $columns): self
                    {
                        return $this;
                    }

                    public function from(string $table, ?string $alias = null): self
                    {
                        return $this;
                    }

                    public function where(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function join(string $type, string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function leftJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function innerJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function order(array|string $columns): self
                    {
                        return $this;
                    }

                    public function group(array|string $columns): self
                    {
                        return $this;
                    }

                    public function having(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function __toString(): string
                    {
                        return '';
                    }

                    public function setLimit(int $limit = 0, int $offset = 0): self
                    {
                        return $this;
                    }
                };
            }
        };
    }
}
