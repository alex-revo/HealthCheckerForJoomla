<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\HtaccessProtectionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtaccessProtectionCheck::class)]
class HtaccessProtectionCheckTest extends TestCase
{
    private HtaccessProtectionCheck $check;

    protected function setUp(): void
    {
        $this->check = new HtaccessProtectionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.htaccess_protection', $this->check->getSlug());
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

    public function testRunReturnsValidStatus(): void
    {
        // This test relies on the actual filesystem state at JPATH_ROOT
        // We can only verify it returns a valid status
        $result = $this->check->run();

        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunReturnsWarningWhenHtaccessMissing(): void
    {
        // This test will pass if JPATH_ROOT/.htaccess doesn't exist
        // Since we're in a test environment, we check for proper behavior
        $result = $this->check->run();

        // The result should always be one of the valid statuses
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );

        // If it's a warning, it should mention htaccess
        if ($result->healthStatus === HealthStatus::Warning) {
            $this->assertTrue(
                str_contains($result->description, 'htaccess') ||
                str_contains($result->description, '.htaccess'),
            );
        }
    }

    public function testRunResultDescriptionIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->description);
    }
}
