<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\HttpsRedirectCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HttpsRedirectCheck::class)]
class HttpsRedirectCheckTest extends TestCase
{
    private HttpsRedirectCheck $check;

    protected function setUp(): void
    {
        $this->check = new HttpsRedirectCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.https_redirect', $this->check->getSlug());
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
        // This test relies on the actual environment (Factory::getApplication(), Uri, .htaccess)
        // We can only verify it returns a valid status
        $result = $this->check->run();

        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunResultDescriptionIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testRunResultDescriptionContainsHttpsOrSsl(): void
    {
        $result = $this->check->run();

        // The description should contain HTTPS or SSL related information
        $this->assertTrue(
            stripos($result->description, 'https') !== false ||
            stripos($result->description, 'ssl') !== false ||
            stripos($result->description, 'redirect') !== false ||
            stripos($result->description, 'configured') !== false,
            'Description should mention HTTPS, SSL, redirect, or configuration status',
        );
    }
}
