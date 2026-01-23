<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\MySitesGuru\Checks;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\MySitesGuru\Checks\MySitesGuruConnectionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MySitesGuruConnectionCheck::class)]
class MySitesGuruConnectionCheckTest extends TestCase
{
    private MySitesGuruConnectionCheck $check;

    protected function setUp(): void
    {
        $this->check = new MySitesGuruConnectionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('mysitesguru.connection', $this->check->getSlug());
    }

    public function testGetCategoryReturnsMySitesGuru(): void
    {
        $this->assertSame('mysitesguru', $this->check->getCategory());
    }

    public function testGetProviderReturnsMySitesGuru(): void
    {
        $this->assertSame('mysitesguru', $this->check->getProvider());
    }

    public function testGetTitleReturnsNonEmptyString(): void
    {
        $title = $this->check->getTitle();

        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenNoMySitesGuruPluginFound(): void
    {
        $database = $this->createMockDatabaseWithObjectList([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not connected', $result->description);
        $this->assertStringContainsString('mysites.guru', $result->description);
    }

    public function testRunReturnsGoodWhenPluginIsEnabled(): void
    {
        $extensions = [
            (object) [
                'name' => 'plg_system_mysitesguru',
                'enabled' => 1,
            ],
        ];
        $database = $this->createMockDatabaseWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('connected', $result->description);
        $this->assertStringContainsString('24/7', $result->description);
    }

    public function testRunReturnsWarningWhenPluginIsDisabled(): void
    {
        $extensions = [
            (object) [
                'name' => 'plg_system_mysitesguru',
                'enabled' => 0,
            ],
        ];
        $database = $this->createMockDatabaseWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testRunReturnsGoodWhenMultiplePluginsAndOneIsEnabled(): void
    {
        $extensions = [
            (object) [
                'name' => 'plg_system_mysitesguru',
                'enabled' => 0,
            ],
            (object) [
                'name' => 'plg_api_mysitesguru',
                'enabled' => 1,
            ],
        ];
        $database = $this->createMockDatabaseWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenMultiplePluginsAllDisabled(): void
    {
        $extensions = [
            (object) [
                'name' => 'plg_system_mysitesguru',
                'enabled' => 0,
            ],
            (object) [
                'name' => 'plg_api_mysitesguru',
                'enabled' => 0,
            ],
        ];
        $database = $this->createMockDatabaseWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testResultHasCorrectSlug(): void
    {
        $database = $this->createMockDatabaseWithObjectList([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame('mysitesguru.connection', $result->slug);
    }

    public function testResultHasCorrectCategory(): void
    {
        $database = $this->createMockDatabaseWithObjectList([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame('mysitesguru', $result->category);
    }

    public function testResultHasCorrectProvider(): void
    {
        $database = $this->createMockDatabaseWithObjectList([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame('mysitesguru', $result->provider);
    }

    public function testWarningDescriptionContainsLearnMoreLink(): void
    {
        $database = $this->createMockDatabaseWithObjectList([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertStringContainsString('https://mysites.guru', $result->description);
    }

    /**
     * Create a mock database that returns the given object list and supports orWhere
     *
     * @param array<object> $objectList
     */
    private function createMockDatabaseWithObjectList(array $objectList): DatabaseInterface
    {
        return new class ($objectList) implements DatabaseInterface {
            /**
             * @param array<object> $objectList
             */
            public function __construct(
                private readonly array $objectList,
            ) {}

            public function getVersion(): string
            {
                return '8.0.30';
            }

            public function getQuery(bool $new = false): QueryInterface
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

                    public function orWhere(array|string $conditions): self
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
                };
            }

            public function setQuery(QueryInterface|string $query, int $offset = 0, int $limit = 0): self
            {
                return $this;
            }

            public function loadResult(): mixed
            {
                return null;
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
                return $this->objectList;
            }

            public function execute(): bool
            {
                return true;
            }

            public function quoteName(array|string $name, ?string $as = null): array|string
            {
                if (is_array($name)) {
                    return array_map(static fn(string $n): string => $n, $name);
                }

                return $name;
            }

            public function quote(array|string $text, bool $escape = true): array|string
            {
                if (is_array($text)) {
                    return array_map(static fn(string $t): string => "'{$t}'", $text);
                }

                return "'{$text}'";
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
        };
    }
}
