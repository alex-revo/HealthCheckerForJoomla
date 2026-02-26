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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\JoomlaUpdateChannelCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JoomlaUpdateChannelCheck::class)]
class JoomlaUpdateChannelCheckTest extends TestCase
{
    private JoomlaUpdateChannelCheck $joomlaUpdateChannelCheck;

    protected function setUp(): void
    {
        $this->joomlaUpdateChannelCheck = new JoomlaUpdateChannelCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.joomla_update_channel', $this->joomlaUpdateChannelCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->joomlaUpdateChannelCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->joomlaUpdateChannelCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->joomlaUpdateChannelCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->joomlaUpdateChannelCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenStableChannel(): void
    {
        $database = MockDatabaseFactory::createWithResult('{"updatesource":"default"}');
        $this->joomlaUpdateChannelCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateChannelCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('JOOMLA_UPDATE_CHANNEL_GOOD', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenEmptyParams(): void
    {
        $database = MockDatabaseFactory::createWithResult('');
        $this->joomlaUpdateChannelCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateChannelCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNullParams(): void
    {
        $database = MockDatabaseFactory::createWithResult(null);
        $this->joomlaUpdateChannelCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateChannelCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenTestingChannel(): void
    {
        $database = MockDatabaseFactory::createWithResult('{"updatesource":"testing"}');
        $this->joomlaUpdateChannelCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateChannelCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('JOOMLA_UPDATE_CHANNEL_WARNING', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenNextChannel(): void
    {
        $database = MockDatabaseFactory::createWithResult('{"updatesource":"next"}');
        $this->joomlaUpdateChannelCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateChannelCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenCustomChannel(): void
    {
        $database = MockDatabaseFactory::createWithResult('{"updatesource":"custom"}');
        $this->joomlaUpdateChannelCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateChannelCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testActionUrlSetOnWarning(): void
    {
        $this->assertNotNull($this->joomlaUpdateChannelCheck->getActionUrl(HealthStatus::Warning));
        $this->assertStringContainsString(
            'com_joomlaupdate',
            $this->joomlaUpdateChannelCheck->getActionUrl(HealthStatus::Warning) ?? '',
        );
    }

    public function testActionUrlNullOnGood(): void
    {
        $this->assertNull($this->joomlaUpdateChannelCheck->getActionUrl(HealthStatus::Good));
    }
}
