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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\DefaultSecretCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultSecretCheck::class)]
class DefaultSecretCheckTest extends TestCase
{
    private DefaultSecretCheck $check;

    protected function setUp(): void
    {
        $this->check = new DefaultSecretCheck();
    }

    protected function tearDown(): void
    {
        // Reset Factory application
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.default_secret', $this->check->getSlug());
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

    public function testRunReturnsCriticalWhenSecretIsEmpty(): void
    {
        $app = new CMSApplication();
        $app->set('secret', '');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('empty', $result->description);
    }

    public function testRunReturnsCriticalWhenSecretIsKnownDefault(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'FBVtggIk5lAXBMqz');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('default', $result->description);
    }

    public function testRunReturnsWarningWhenSecretIsTooShort(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'abc123xyz'); // 9 characters, less than 16
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('shorter', $result->description);
    }

    public function testRunReturnsWarningWhenSecretIs15Characters(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'abc123xyzABC012'); // exactly 15 characters
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenSecretIsUniqueAndLongEnough(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'myUniqueSecretKey123456'); // 22 characters, unique
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('unique', $result->description);
    }

    public function testRunReturnsGoodWhenSecretIsExactly16Characters(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'abc123xyzABC0123'); // exactly 16 characters
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunReturnsCriticalWhenSecretNotSet(): void
    {
        $app = new CMSApplication();
        // Don't set secret, it will use the default empty string
        Factory::setApplication($app);

        $result = $this->check->run();

        // The default is empty string which triggers critical
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }
}
