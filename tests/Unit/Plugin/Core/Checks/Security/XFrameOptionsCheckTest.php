<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\XFrameOptionsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XFrameOptionsCheck::class)]
class XFrameOptionsCheckTest extends TestCase
{
    private XFrameOptionsCheck $check;

    protected function setUp(): void
    {
        $this->check = new XFrameOptionsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.x_frame_options', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->check->getCategory());
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
    }

    public function testRunReturnsWarningWhenHttpHeadersPluginNotFound(): void
    {
        // loadObject returns null - plugin not found
        $database = MockDatabaseFactory::createWithObject(null);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('HTTP Headers plugin not found', $result->description);
    }

    public function testRunReturnsWarningWhenHttpHeadersPluginDisabled(): void
    {
        $pluginData = (object) [
            'enabled' => 0,
            'params' => '{"xframeoptions":1}',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('HTTP Headers plugin is disabled', $result->description);
    }

    public function testRunReturnsWarningWhenParamsEmpty(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not configured', $result->description);
    }

    public function testRunReturnsWarningWhenParamsIsEmptyArray(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '[]',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not configured', $result->description);
    }

    public function testRunReturnsCriticalWhenXFrameOptionsExplicitlyDisabled(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '{"xframeoptions":0}',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('X-Frame-Options is disabled', $result->description);
    }

    public function testRunReturnsGoodWhenXFrameOptionsEnabled(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '{"xframeoptions":1}',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('X-Frame-Options header is enabled', $result->description);
    }

    public function testRunReturnsGoodWhenXFrameOptionsNotSetInParams(): void
    {
        // When xframeoptions is not explicitly set, it defaults to 1 (enabled)
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '{"some_other_setting":true}',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenParamsIsInvalidJson(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => 'not valid json',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not configured', $result->description);
    }

    public function testRunReturnsCriticalWhenXFrameOptionsExplicitlyDisabledAsString(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '{"xframeoptions":"0"}',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }
}
