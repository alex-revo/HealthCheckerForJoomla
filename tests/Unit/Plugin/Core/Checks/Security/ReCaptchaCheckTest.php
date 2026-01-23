<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\ReCaptchaCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReCaptchaCheck::class)]
class ReCaptchaCheckTest extends TestCase
{
    private ReCaptchaCheck $check;

    protected function setUp(): void
    {
        $this->check = new ReCaptchaCheck();
    }

    protected function tearDown(): void
    {
        // Reset Factory application
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.recaptcha', $this->check->getSlug());
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

    public function testRunReturnsWarningWhenNoCaptchaPluginsEnabled(): void
    {
        $app = new CMSApplication();
        $app->set('captcha', 'recaptcha');
        Factory::setApplication($app);

        // No captcha plugins enabled
        $database = MockDatabaseFactory::createWithResult(0);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('No CAPTCHA plugins are enabled', $result->description);
    }

    public function testRunReturnsWarningWhenCaptchaPluginEnabledButNotDefault(): void
    {
        $app = new CMSApplication();
        $app->set('captcha', '0'); // No default captcha set
        Factory::setApplication($app);

        // Captcha plugin is enabled
        $database = MockDatabaseFactory::createWithResult(1);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not set as default', $result->description);
    }

    public function testRunReturnsWarningWhenCaptchaPluginEnabledButDefaultEmpty(): void
    {
        $app = new CMSApplication();
        $app->set('captcha', ''); // Empty default captcha
        Factory::setApplication($app);

        // Captcha plugin is enabled
        $database = MockDatabaseFactory::createWithResult(1);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not set as default', $result->description);
    }

    public function testRunReturnsGoodWhenCaptchaProperlyConfigured(): void
    {
        $app = new CMSApplication();
        $app->set('captcha', 'recaptcha');
        Factory::setApplication($app);

        // Captcha plugin is enabled
        $database = MockDatabaseFactory::createWithResult(1);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('configured for form protection', $result->description);
    }

    public function testRunReturnsGoodWithMultipleCaptchaPluginsEnabled(): void
    {
        $app = new CMSApplication();
        $app->set('captcha', 'hcaptcha');
        Factory::setApplication($app);

        // Multiple captcha plugins are enabled
        $database = MockDatabaseFactory::createWithResult(3);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}
