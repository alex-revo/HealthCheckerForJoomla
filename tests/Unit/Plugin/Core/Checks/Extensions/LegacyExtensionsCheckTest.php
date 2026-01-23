<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\LegacyExtensionsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LegacyExtensionsCheck::class)]
class LegacyExtensionsCheckTest extends TestCase
{
    private LegacyExtensionsCheck $check;

    protected function setUp(): void
    {
        $this->check = new LegacyExtensionsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.legacy_extensions', $this->check->getSlug());
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

    public function testRunWithNoExtensionsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithRecentExtensionsReturnsGood(): void
    {
        $recentDate = (new \DateTime())->modify('-1 year')
            ->format('F Y');
        $extensions = [
            (object) [
                'name' => 'Third Party Extension',
                'element' => 'com_thirdparty',
                'manifest_cache' => json_encode([
                    'creationDate' => $recentDate,
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithOldExtensionReturnsWarning(): void
    {
        $oldDate = (new \DateTime())->modify('-3 years')
            ->format('F Y');
        $extensions = [
            (object) [
                'name' => 'Old Extension',
                'element' => 'com_oldextension',
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Old Extension', $result->description);
    }

    public function testRunSkipsCoreExtensions(): void
    {
        // com_content is a core extension and should be skipped
        $oldDate = (new \DateTime())->modify('-5 years')
            ->format('F Y');
        $extensions = [
            (object) [
                'name' => 'Content',
                'element' => 'com_content',
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithMultipleOldExtensionsReturnsWarning(): void
    {
        $oldDate = (new \DateTime())->modify('-3 years')
            ->format('F Y');
        $extensions = [];

        for ($i = 1; $i <= 15; $i++) {
            $extensions[] = (object) [
                'name' => "Old Extension {$i}",
                'element' => "com_old{$i}",
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ];
        }

        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('15', $result->description);
    }

    public function testRunWithInvalidManifestCacheSkipsExtension(): void
    {
        $extensions = [
            (object) [
                'name' => 'Invalid Manifest Extension',
                'element' => 'com_invalid',
                'manifest_cache' => 'not-valid-json{',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithEmptyManifestCacheSkipsExtension(): void
    {
        $extensions = [
            (object) [
                'name' => 'Empty Manifest Extension',
                'element' => 'com_empty',
                'manifest_cache' => json_encode([]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithMissingCreationDateSkipsExtension(): void
    {
        $extensions = [
            (object) [
                'name' => 'No Date Extension',
                'element' => 'com_nodate',
                'manifest_cache' => json_encode([
                    'version' => '1.0.0',
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunHandlesVariousDateFormats(): void
    {
        $extensions = [
            (object) [
                'name' => 'Extension with Y-m-d date',
                'element' => 'com_ymd',
                'manifest_cache' => json_encode([
                    'creationDate' => '2020-01-15',
                ]),
            ],
            (object) [
                'name' => 'Extension with F Y date',
                'element' => 'com_fy',
                'manifest_cache' => json_encode([
                    'creationDate' => 'January 2020',
                ]),
            ],
            (object) [
                'name' => 'Extension with year only',
                'element' => 'com_year',
                'manifest_cache' => json_encode([
                    'creationDate' => '2020',
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // All three have old dates, so should return warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }
}
