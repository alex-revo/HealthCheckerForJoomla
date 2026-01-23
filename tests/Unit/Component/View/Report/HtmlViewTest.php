<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\View\Report;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use MySitesGuru\HealthChecker\Component\Administrator\View\Report\HtmlView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlView::class)]
class HtmlViewTest extends TestCase
{
    private ?CMSApplication $originalApp = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear toolbar instances
        Toolbar::clearInstances();

        // Store original app if set
        try {
            $this->originalApp = Factory::getApplication();
        } catch (\Exception) {
            $this->originalApp = null;
        }

        // Set up a mock application
        $app = new CMSApplication();
        Factory::setApplication($app);
    }

    protected function tearDown(): void
    {
        // Restore original application
        Factory::setApplication($this->originalApp);
        Toolbar::clearInstances();

        parent::tearDown();
    }

    public function testViewCanBeInstantiated(): void
    {
        $view = new HtmlView();

        $this->assertInstanceOf(HtmlView::class, $view);
    }

    public function testViewExtendsBaseHtmlView(): void
    {
        $view = new HtmlView();

        $this->assertInstanceOf(\Joomla\CMS\MVC\View\HtmlView::class, $view);
    }

    public function testBeforeReportHtmlDefaultsToEmptyString(): void
    {
        $view = new HtmlView();

        $this->assertSame('', $view->beforeReportHtml);
    }

    public function testBeforeReportHtmlIsPublic(): void
    {
        $reflection = new \ReflectionClass(HtmlView::class);
        $property = $reflection->getProperty('beforeReportHtml');

        $this->assertTrue($property->isPublic());
    }

    public function testBeforeReportHtmlCanBeModified(): void
    {
        $view = new HtmlView();
        $view->beforeReportHtml = '<div>Custom content</div>';

        $this->assertSame('<div>Custom content</div>', $view->beforeReportHtml);
    }

    public function testDisplayMethodExists(): void
    {
        $this->assertTrue(method_exists(HtmlView::class, 'display'));
    }

    public function testDisplayMethodAcceptsNullTemplate(): void
    {
        $reflection = new \ReflectionMethod(HtmlView::class, 'display');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('tpl', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->allowsNull());
    }

    public function testAddToolbarMethodExists(): void
    {
        $reflection = new \ReflectionClass(HtmlView::class);

        $this->assertTrue($reflection->hasMethod('addToolbar'));
    }

    public function testAddToolbarMethodIsProtected(): void
    {
        $reflection = new \ReflectionMethod(HtmlView::class, 'addToolbar');

        $this->assertTrue($reflection->isProtected());
    }

    public function testViewHasCorrectNamespace(): void
    {
        $reflection = new \ReflectionClass(HtmlView::class);

        $this->assertSame(
            'MySitesGuru\HealthChecker\Component\Administrator\View\Report',
            $reflection->getNamespaceName(),
        );
    }

    public function testViewIsNotAbstract(): void
    {
        $reflection = new \ReflectionClass(HtmlView::class);

        $this->assertFalse($reflection->isAbstract());
    }

    public function testViewIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(HtmlView::class);

        $this->assertFalse($reflection->isFinal());
    }
}
