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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\JoomlaUpdateStabilityCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JoomlaUpdateStabilityCheck::class)]
class JoomlaUpdateStabilityCheckTest extends TestCase
{
    private JoomlaUpdateStabilityCheck $joomlaUpdateStabilityCheck;

    protected function setUp(): void
    {
        $this->joomlaUpdateStabilityCheck = new JoomlaUpdateStabilityCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.joomla_update_stability', $this->joomlaUpdateStabilityCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->joomlaUpdateStabilityCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->joomlaUpdateStabilityCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->joomlaUpdateStabilityCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->joomlaUpdateStabilityCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenStable(): void
    {
        $database = MockDatabaseFactory::createWithResult('{"minimum_stability":"4"}');
        $this->joomlaUpdateStabilityCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateStabilityCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('JOOMLA_UPDATE_STABILITY_GOOD', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenEmptyParams(): void
    {
        $database = MockDatabaseFactory::createWithResult('');
        $this->joomlaUpdateStabilityCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateStabilityCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNullParams(): void
    {
        $database = MockDatabaseFactory::createWithResult(null);
        $this->joomlaUpdateStabilityCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateStabilityCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenDev(): void
    {
        $database = MockDatabaseFactory::createWithResult('{"minimum_stability":"0"}');
        $this->joomlaUpdateStabilityCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateStabilityCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('JOOMLA_UPDATE_STABILITY_WARNING', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenAlpha(): void
    {
        $database = MockDatabaseFactory::createWithResult('{"minimum_stability":"1"}');
        $this->joomlaUpdateStabilityCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateStabilityCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenBeta(): void
    {
        $database = MockDatabaseFactory::createWithResult('{"minimum_stability":"2"}');
        $this->joomlaUpdateStabilityCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateStabilityCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenRc(): void
    {
        $database = MockDatabaseFactory::createWithResult('{"minimum_stability":"3"}');
        $this->joomlaUpdateStabilityCheck->setDatabase($database);

        $healthCheckResult = $this->joomlaUpdateStabilityCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testActionUrlSetOnWarning(): void
    {
        $this->assertNotNull($this->joomlaUpdateStabilityCheck->getActionUrl(HealthStatus::Warning));
        $this->assertStringContainsString(
            'com_joomlaupdate',
            $this->joomlaUpdateStabilityCheck->getActionUrl(HealthStatus::Warning) ?? '',
        );
    }

    public function testActionUrlNullOnGood(): void
    {
        $this->assertNull($this->joomlaUpdateStabilityCheck->getActionUrl(HealthStatus::Good));
    }
}
