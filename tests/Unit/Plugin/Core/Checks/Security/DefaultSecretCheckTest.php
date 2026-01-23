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

    public function testRunResultContainsSlug(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'myUniqueSecretKey123456');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame('security.default_secret', $result->slug);
    }

    public function testRunResultContainsTitle(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'myUniqueSecretKey123456');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testRunResultHasProvider(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'myUniqueSecretKey123456');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame('core', $result->provider);
    }

    public function testRunResultHasCategory(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'myUniqueSecretKey123456');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame('security', $result->category);
    }

    public function testRunResultDescriptionIsNotEmpty(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'myUniqueSecretKey123456');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testRunReturnsValidStatus(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'test');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunReturnsWarningWithSingleCharacter(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'a');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsGoodWithLongSecret(): void
    {
        $app = new CMSApplication();
        // 64 character secret
        $app->set('secret', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789ab');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testCriticalForEmptyMentionsCriticalSecurityIssue(): void
    {
        $app = new CMSApplication();
        $app->set('secret', '');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('critical', strtolower($result->description));
        $this->assertStringContainsString('security', strtolower($result->description));
    }

    public function testCriticalForDefaultMentionsGenerate(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'FBVtggIk5lAXBMqz');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertTrue(
            stripos($result->description, 'generate') !== false ||
            stripos($result->description, 'Generate') !== false,
        );
    }

    public function testWarningMentionsRecommended(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'shortkey'); // 8 chars
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('recommended', $result->description);
    }

    public function testGoodMentionsConfigured(): void
    {
        $app = new CMSApplication();
        $app->set('secret', 'secureRandomKey12345');
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('configured', $result->description);
    }

    public function testRunWithIntegerSecretValue(): void
    {
        // Edge case: secret stored as integer
        $app = new CMSApplication();
        $app->set('secret', 123456789012345678);
        Factory::setApplication($app);

        $result = $this->check->run();

        // strlen on integer is cast to string, so it's 18 chars
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithNumericStringSecret(): void
    {
        $app = new CMSApplication();
        $app->set('secret', '1234567890123456'); // 16 digit numeric string
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithSpecialCharactersInSecret(): void
    {
        $app = new CMSApplication();
        $app->set('secret', '!@#$%^&*()_+-=[]{}'); // 18 special characters
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithUnicodeSecret(): void
    {
        $app = new CMSApplication();
        // Unicode secret - strlen will count bytes, not characters
        $app->set('secret', 'SecretKey' . "\u{1F511}" . 'Test12345');
        Factory::setApplication($app);

        $result = $this->check->run();

        // The emoji takes 4 bytes, so total is 9 + 4 + 9 = 22 bytes
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithWhitespaceOnlySecret(): void
    {
        $app = new CMSApplication();
        $app->set('secret', '                '); // 16 spaces
        Factory::setApplication($app);

        $result = $this->check->run();

        // While technically 16 characters, empty() returns false for whitespace string
        // The check uses empty() first, which returns false for whitespace
        // So it passes the empty check and proceeds to length check
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithNullSecret(): void
    {
        $app = new CMSApplication();
        $app->set('secret', null);
        Factory::setApplication($app);

        $result = $this->check->run();

        // empty(null) returns true, so should be critical
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testBoundaryAt15Characters(): void
    {
        $app = new CMSApplication();
        $app->set('secret', '123456789012345'); // exactly 15 characters
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testBoundaryAt16Characters(): void
    {
        $app = new CMSApplication();
        $app->set('secret', '1234567890123456'); // exactly 16 characters
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testBoundaryAt17Characters(): void
    {
        $app = new CMSApplication();
        $app->set('secret', '12345678901234567'); // exactly 17 characters
        Factory::setApplication($app);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}
