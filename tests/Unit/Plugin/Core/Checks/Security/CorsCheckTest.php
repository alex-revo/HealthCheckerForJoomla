<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\CorsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CorsCheck::class)]
class CorsCheckTest extends TestCase
{
    private CorsCheck $check;

    protected function setUp(): void
    {
        $this->check = new CorsCheck();
    }

    protected function tearDown(): void
    {
        // Reset Factory application
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.cors', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenCorsDisabled(): void
    {
        $app = new CMSApplication();
        $app->set('cors', false);
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testRunReturnsWarningWhenCorsEnabledWithWildcard(): void
    {
        $app = new CMSApplication();
        $app->set('cors', true);
        $app->set('cors_allow_origin', '*');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('wildcard', $result->description);
    }

    public function testRunReturnsGoodWhenCorsEnabledWithRestrictedOrigin(): void
    {
        $app = new CMSApplication();
        $app->set('cors', true);
        $app->set('cors_allow_origin', 'https://example.com');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('restricted', $result->description);
        $this->assertStringContainsString('example.com', $result->description);
    }

    public function testRunReturnsWarningWhenCorsEnabledAsString1(): void
    {
        $app = new CMSApplication();
        $app->set('cors', '1');
        $app->set('cors_allow_origin', '*');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenCorsEnabledAsInteger1(): void
    {
        $app = new CMSApplication();
        $app->set('cors', 1);
        $app->set('cors_allow_origin', '*');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenCorsDisabledWithString0(): void
    {
        $app = new CMSApplication();
        $app->set('cors', '0');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }
}
