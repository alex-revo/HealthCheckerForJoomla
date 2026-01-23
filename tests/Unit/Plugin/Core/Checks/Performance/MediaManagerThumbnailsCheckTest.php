<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\MediaManagerThumbnailsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MediaManagerThumbnailsCheck::class)]
class MediaManagerThumbnailsCheckTest extends TestCase
{
    private MediaManagerThumbnailsCheck $check;

    protected function setUp(): void
    {
        $this->check = new MediaManagerThumbnailsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.media_manager_thumbnails', $this->check->getSlug());
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

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('database', strtolower($result->description));
    }

    public function testRunWithPluginNotFoundReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithObject(null);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not found', strtolower($result->description));
    }

    public function testRunWithPluginDisabledReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 0,
            'params' => json_encode([
                'thumbnail_size' => 200,
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('disabled', strtolower($result->description));
    }

    public function testRunWithThumbnailsEnabledReturnsGood(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'thumbnail_size' => 200,
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('200', $result->description);
    }

    public function testRunWithThumbnailsDisabledReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'thumbnail_size' => 0,
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('disabled', strtolower($result->description));
    }

    public function testRunWithInvalidParamsReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => 'invalid-json{',
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('read', strtolower($result->description));
    }

    public function testRunWithNegativeThumbnailSizeReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'thumbnail_size' => -100,
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunWithMissingThumbnailSizeParamReturnsWarning(): void
    {
        $plugin = (object) [
            'enabled' => 1,
            'params' => json_encode([
                'other_param' => 'value',
            ]),
        ];
        $database = MockDatabaseFactory::createWithObject($plugin);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }
}
