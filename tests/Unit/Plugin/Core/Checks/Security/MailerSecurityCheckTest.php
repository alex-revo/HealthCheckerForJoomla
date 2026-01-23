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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\MailerSecurityCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MailerSecurityCheck::class)]
class MailerSecurityCheckTest extends TestCase
{
    private MailerSecurityCheck $check;

    protected function setUp(): void
    {
        $this->check = new MailerSecurityCheck();
    }

    protected function tearDown(): void
    {
        // Reset Factory application
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.mailer_security', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenUsingPhpMail(): void
    {
        $app = new CMSApplication();
        $app->set('mailer', 'mail');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('PHP mail()', $result->description);
    }

    public function testRunReturnsGoodWhenUsingSendmail(): void
    {
        $app = new CMSApplication();
        $app->set('mailer', 'sendmail');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('sendmail', $result->description);
    }

    public function testRunReturnsWarningWhenSmtpWithoutEncryption(): void
    {
        $app = new CMSApplication();
        $app->set('mailer', 'smtp');
        $app->set('smtpsecure', 'none');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('without encryption', $result->description);
    }

    public function testRunReturnsWarningWhenSmtpWithEmptyEncryption(): void
    {
        $app = new CMSApplication();
        $app->set('mailer', 'smtp');
        $app->set('smtpsecure', '');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenSmtpWithTls(): void
    {
        $app = new CMSApplication();
        $app->set('mailer', 'smtp');
        $app->set('smtpsecure', 'tls');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('TLS', $result->description);
    }

    public function testRunReturnsGoodWhenSmtpWithSsl(): void
    {
        $app = new CMSApplication();
        $app->set('mailer', 'smtp');
        $app->set('smtpsecure', 'ssl');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('SSL', $result->description);
    }

    public function testRunReturnsGoodForOtherMailerTypes(): void
    {
        $app = new CMSApplication();
        $app->set('mailer', 'custom_mailer');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('custom_mailer', $result->description);
    }

    public function testRunWithDefaultMailerSetting(): void
    {
        $app = new CMSApplication();
        // mailer default is 'mail' when not set
        Factory::setApplication($app);

        $result = $this->check->run();

        // Default is 'mail' which returns Good
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}
