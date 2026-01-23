<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Users;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\UserRegistrationCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserRegistrationCheck::class)]
class UserRegistrationCheckTest extends TestCase
{
    private UserRegistrationCheck $check;

    protected function setUp(): void
    {
        $this->check = new UserRegistrationCheck();
    }

    protected function tearDown(): void
    {
        ComponentHelper::resetParams();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.user_registration', $this->check->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->check->getCategory());
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

    public function testRunReturnsValidStatus(): void
    {
        // ComponentHelper::getParams returns null from stub which defaults to 0
        $result = $this->check->run();

        // With default stub (null params), registration defaults to disabled (0)
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testRunReturnsGoodWhenRegistrationDisabled(): void
    {
        // Set up com_users params with registration disabled (0)
        $params = new Registry([
            'allowUserRegistration' => 0,
        ]);
        ComponentHelper::setParams('com_users', $params);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testRunReturnsWarningWhenRegistrationEnabled(): void
    {
        // Set up com_users params with registration enabled (1)
        $params = new Registry([
            'allowUserRegistration' => 1,
        ]);
        ComponentHelper::setParams('com_users', $params);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('enabled', $result->description);
        $this->assertStringContainsString('CAPTCHA', $result->description);
    }

    public function testRunReturnsGoodWhenRegistrationValueIsNotOne(): void
    {
        // Test that values other than 1 are treated as disabled
        // (e.g., 2 for admin activation required is still considered enabled in some Joomla versions)
        $params = new Registry([
            'allowUserRegistration' => 2,
        ]);
        ComponentHelper::setParams('com_users', $params);

        $result = $this->check->run();

        // Value is cast to int and compared with === 1, so 2 should return good
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}
