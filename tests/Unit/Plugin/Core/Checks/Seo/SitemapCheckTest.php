<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\SitemapCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SitemapCheck::class)]
class SitemapCheckTest extends TestCase
{
    private SitemapCheck $check;

    protected function setUp(): void
    {
        $this->check = new SitemapCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.sitemap', $this->check->getSlug());
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
     * Note: This check reads the filesystem at JPATH_ROOT/sitemap.xml
     * In test environment, JPATH_ROOT may not be defined, which causes warning
     */
    public function testRunReturnsWarningWhenSitemapNotFoundOrConstantUndefined(): void
    {
        $result = $this->check->run();

        // Should return warning when sitemap doesn't exist or JPATH_ROOT undefined
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }
}
