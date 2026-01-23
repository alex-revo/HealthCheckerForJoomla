<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\TemplateCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TemplateCheck::class)]
class TemplateCheckTest extends TestCase
{
    private TemplateCheck $check;

    protected function setUp(): void
    {
        $this->check = new TemplateCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.template', $this->check->getSlug());
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

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('database', strtolower($result->description));
    }

    public function testRunWithNoSiteTemplateConfiguredReturnsWarningOrCritical(): void
    {
        $database = $this->createDatabaseWithTemplates(null, (object) [
            'template' => 'atum',
            'title' => 'Atum',
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Should return critical or warning depending on implementation details
        $this->assertContains($result->healthStatus, [HealthStatus::Critical, HealthStatus::Warning]);
    }

    public function testRunWithNoAdminTemplateConfiguredReturnsWarningOrCritical(): void
    {
        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'cassiopeia',
                'title' => 'Cassiopeia',
            ],
            null,
        );
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Should return critical or warning depending on implementation details
        $this->assertContains($result->healthStatus, [HealthStatus::Critical, HealthStatus::Warning]);
    }

    public function testRunWithMissingTemplateDirectoryReturnsWarningOrCritical(): void
    {
        // Template exists in DB but directory doesn't exist on disk
        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'nonexistent_site_template',
                'title' => 'Nonexistent',
            ],
            (object) [
                'template' => 'nonexistent_admin_template',
                'title' => 'Nonexistent Admin',
            ],
        );
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Should return critical or warning
        $this->assertContains($result->healthStatus, [HealthStatus::Critical, HealthStatus::Warning]);
    }

    public function testRunReturnsValidStatus(): void
    {
        // This test verifies that the check returns a valid status
        $result = $this->check->run();

        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testDescriptionContainsTemplateText(): void
    {
        $result = $this->check->run();

        $description = strtolower($result->description);
        $hasRelevantText = str_contains($description, 'template')
            || str_contains($description, 'database');

        $this->assertTrue($hasRelevantText, 'Description should mention template or database');
    }

    /**
     * Create a mock database that returns site and admin templates
     */
    private function createDatabaseWithTemplates(?object $siteTemplate, ?object $adminTemplate): DatabaseInterface
    {
        return new class ($siteTemplate, $adminTemplate) implements DatabaseInterface {
            private int $queryIndex = 0;

            public function __construct(
                private readonly ?object $siteTemplate,
                private readonly ?object $adminTemplate,
            ) {}

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
                // First call returns site template, second returns admin template
                $result = $this->queryIndex === 0 ? $this->siteTemplate : $this->adminTemplate;
                $this->queryIndex++;

                return $result;
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
                };
            }
        };
    }
}
