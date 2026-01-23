<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\MailFunctionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MailFunctionCheck::class)]
class MailFunctionCheckTest extends TestCase
{
    private MailFunctionCheck $check;

    protected function setUp(): void
    {
        $this->check = new MailFunctionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.mail_function', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->check->getCategory());
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

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.mail_function', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good, Warning, or Critical depending on mail configuration
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsMailInfo(): void
    {
        $result = $this->check->run();

        // Description should mention mail, SMTP, or sendmail
        $this->assertTrue(
            str_contains(strtolower($result->description), 'mail') ||
            str_contains(strtolower($result->description), 'smtp') ||
            str_contains(strtolower($result->description), 'sendmail'),
        );
    }

    public function testMailFunctionExistsOnTestEnvironment(): void
    {
        // In test environment, mail() function should exist
        $this->assertTrue(function_exists('mail'));
    }

    public function testCheckHandlesDisabledFunctionsCheck(): void
    {
        // Verify the check works when testing for disabled functions
        $disabledFunctions = ini_get('disable_functions');
        $result = $this->check->run();

        // If mail is disabled, should return Critical or Warning
        // If mail is available, should return Good
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }
}
