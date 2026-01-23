<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\FacebookOpenGraphCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FacebookOpenGraphCheck::class)]
class FacebookOpenGraphCheckTest extends TestCase
{
    private FacebookOpenGraphCheck $check;

    protected function setUp(): void
    {
        $this->check = new FacebookOpenGraphCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.facebook_open_graph', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->check->getCategory());
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

    /**
     * Note: This check requires HTTP requests to fetch the homepage,
     * which cannot be easily unit tested without mocking the HTTP layer.
     * The run() method will return a warning due to HTTP errors in test environment.
     */
    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        // In test environment, HTTP request will likely fail
        // Just verify it returns a valid result object
        $this->assertInstanceOf(
            \MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult::class,
            $result,
        );
    }
}
