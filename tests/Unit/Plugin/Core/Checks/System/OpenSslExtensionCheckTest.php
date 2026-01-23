<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\OpenSslExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenSslExtensionCheck::class)]
class OpenSslExtensionCheckTest extends TestCase
{
    private OpenSslExtensionCheck $check;

    protected function setUp(): void
    {
        $this->check = new OpenSslExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.openssl_extension', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenOpenSslLoaded(): void
    {
        // OpenSSL is typically loaded in PHP environments
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('OpenSSL', $result->description);
        $this->assertStringContainsString('loaded', $result->description);
    }

    public function testRunIncludesVersionWhenOpenSslLoaded(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension not available');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        // The description should include the OpenSSL version text
        $this->assertStringContainsString(OPENSSL_VERSION_TEXT, $result->description);
    }

    public function testRunReturnsCriticalWhenOpenSslNotAvailable(): void
    {
        if (extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension is available - cannot test critical path');
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('OpenSSL', $result->description);
        $this->assertStringContainsString('not loaded', $result->description);
    }
}
