<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\MetaKeywordsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetaKeywordsCheck::class)]
class MetaKeywordsCheckTest extends TestCase
{
    private MetaKeywordsCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        Factory::setApplication($this->app);
        $this->check = new MetaKeywordsCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.meta_keywords', $this->check->getSlug());
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

    public function testRunWithEmptyMetaKeywordsReturnsGood(): void
    {
        $this->app->set('MetaKeys', '');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('not set', $result->description);
        $this->assertStringContainsString('2009', $result->description);
    }

    public function testRunWithMetaKeywordsSetReturnsGood(): void
    {
        $this->app->set('MetaKeys', 'joomla, cms, website');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Meta keywords are set', $result->description);
        $this->assertStringContainsString('no longer use', $result->description);
    }

    public function testRunAlwaysReturnsGood(): void
    {
        // This check always returns good status since meta keywords
        // are neither helpful nor harmful to SEO

        $this->app->set('MetaKeys', 'test, keywords');
        $result = $this->check->run();
        $this->assertSame(HealthStatus::Good, $result->healthStatus);

        $this->app->set('MetaKeys', '');
        $result = $this->check->run();
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithWhitespaceOnlyMetaKeywordsReturnsGoodAsNotSet(): void
    {
        $this->app->set('MetaKeys', '   ');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('not set', $result->description);
    }
}
