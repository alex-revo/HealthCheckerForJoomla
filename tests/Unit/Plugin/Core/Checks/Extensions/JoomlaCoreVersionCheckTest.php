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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\JoomlaCoreVersionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JoomlaCoreVersionCheck::class)]
class JoomlaCoreVersionCheckTest extends TestCase
{
    private JoomlaCoreVersionCheck $joomlaCoreVersionCheck;

    protected function setUp(): void
    {
        $this->joomlaCoreVersionCheck = new JoomlaCoreVersionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.joomla_core_version', $this->joomlaCoreVersionCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->joomlaCoreVersionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->joomlaCoreVersionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->joomlaCoreVersionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->joomlaCoreVersionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoUpdateAvailable(): void
    {
        // First query: #__updates returns null (no update), second query: params
        $database = MockDatabaseFactory::createWithSequentialResults([null, '{"updatesource":"default"}']);
        $this->joomlaCoreVersionCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaCoreVersionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('JOOMLA_CORE_VERSION_GOOD', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenStableUpdateAvailable(): void
    {
        // Current version from stub is 5.0.0, newer stable version available, stable channel
        $database = MockDatabaseFactory::createWithSequentialResults(['5.1.0', '{"updatesource":"default"}']);
        $this->joomlaCoreVersionCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaCoreVersionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('JOOMLA_CORE_VERSION_WARNING', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenTestingChannelWithPreRelease(): void
    {
        // Testing channel with pre-release available — should be GOOD
        $database = MockDatabaseFactory::createWithSequentialResults(['5.1.0-beta1', '{"updatesource":"testing"}']);
        $this->joomlaCoreVersionCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaCoreVersionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('JOOMLA_CORE_VERSION_GOOD_CHANNEL', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenTestingChannelWithStableUpdate(): void
    {
        // Testing channel but a stable version is available — should still warn
        $database = MockDatabaseFactory::createWithSequentialResults(['5.1.0', '{"updatesource":"testing"}']);
        $this->joomlaCoreVersionCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaCoreVersionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNextChannelWithRcAvailable(): void
    {
        // Next major channel with RC available — should be GOOD
        $database = MockDatabaseFactory::createWithSequentialResults(['6.0.0-rc1', '{"updatesource":"next"}']);
        $this->joomlaCoreVersionCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaCoreVersionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testActionUrlSetOnWarning(): void
    {
        $this->assertNotNull($this->joomlaCoreVersionCheck->getActionUrl(HealthStatus::Warning));
        $this->assertStringContainsString(
            'com_joomlaupdate',
            $this->joomlaCoreVersionCheck->getActionUrl(HealthStatus::Warning) ?? '',
        );
    }

    public function testActionUrlNullOnGood(): void
    {
        $this->assertNull($this->joomlaCoreVersionCheck->getActionUrl(HealthStatus::Good));
    }
}
