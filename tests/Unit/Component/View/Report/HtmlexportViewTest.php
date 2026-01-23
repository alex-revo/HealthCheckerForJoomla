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
use MySitesGuru\HealthChecker\Component\Administrator\View\Report\HtmlexportView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlexportView::class)]
class HtmlexportViewTest extends TestCase
{
    private ?CMSApplication $originalApp = null;

    protected function setUp(): void
    {
        parent::setUp();

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

        parent::tearDown();
    }

    public function testViewCanBeInstantiated(): void
    {
        $view = new HtmlexportView();

        $this->assertInstanceOf(HtmlexportView::class, $view);
    }

    public function testViewExtendsBaseHtmlView(): void
    {
        $view = new HtmlexportView();

        $this->assertInstanceOf(\Joomla\CMS\MVC\View\HtmlView::class, $view);
    }

    public function testDisplayMethodExists(): void
    {
        $this->assertTrue(method_exists(HtmlexportView::class, 'display'));
    }

    public function testDisplayMethodAcceptsNullTemplate(): void
    {
        $reflection = new \ReflectionMethod(HtmlexportView::class, 'display');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('tpl', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->allowsNull());
    }

    public function testDisplayMethodReturnsVoid(): void
    {
        $reflection = new \ReflectionMethod(HtmlexportView::class, 'display');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    public function testViewHasCorrectNamespace(): void
    {
        $reflection = new \ReflectionClass(HtmlexportView::class);

        $this->assertSame(
            'MySitesGuru\HealthChecker\Component\Administrator\View\Report',
            $reflection->getNamespaceName(),
        );
    }

    public function testViewIsNotAbstract(): void
    {
        $reflection = new \ReflectionClass(HtmlexportView::class);

        $this->assertFalse($reflection->isAbstract());
    }

    public function testViewIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(HtmlexportView::class);

        $this->assertFalse($reflection->isFinal());
    }

    public function testViewClassName(): void
    {
        $reflection = new \ReflectionClass(HtmlexportView::class);

        $this->assertSame('HtmlexportView', $reflection->getShortName());
    }

    public function testRenderHtmlReportMethodExists(): void
    {
        $reflection = new \ReflectionClass(HtmlexportView::class);

        $this->assertTrue($reflection->hasMethod('renderHtmlReport'));
    }

    public function testRenderHtmlReportMethodIsPrivate(): void
    {
        $reflection = new \ReflectionMethod(HtmlexportView::class, 'renderHtmlReport');

        $this->assertTrue($reflection->isPrivate());
    }

    public function testRenderHtmlReportHasExpectedParameters(): void
    {
        $reflection = new \ReflectionMethod(HtmlexportView::class, 'renderHtmlReport');
        $parameters = $reflection->getParameters();

        // Method has 12 parameters based on the source
        $this->assertCount(12, $parameters);

        // Check parameter names
        $paramNames = array_map(fn($p) => $p->getName(), $parameters);
        $this->assertContains('results', $paramNames);
        $this->assertContains('categories', $paramNames);
        $this->assertContains('providers', $paramNames);
        $this->assertContains('siteName', $paramNames);
        $this->assertContains('reportDate', $paramNames);
        $this->assertContains('joomlaVersion', $paramNames);
        $this->assertContains('criticalCount', $paramNames);
        $this->assertContains('warningCount', $paramNames);
        $this->assertContains('goodCount', $paramNames);
        $this->assertContains('totalCount', $paramNames);
        $this->assertContains('showMySitesGuruBanner', $paramNames);
        $this->assertContains('logoUrl', $paramNames);
    }

    public function testViewUsesModelForData(): void
    {
        $reflection = new \ReflectionMethod(HtmlexportView::class, 'display');
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('getModel', $source);
    }

    public function testViewUsesRunChecksFromModel(): void
    {
        $reflection = new \ReflectionMethod(HtmlexportView::class, 'display');
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('runChecks', $source);
    }

    public function testViewGetsResultsByCategory(): void
    {
        $reflection = new \ReflectionMethod(HtmlexportView::class, 'display');
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('getResultsByCategory', $source);
    }

    public function testViewUsesPluginHelper(): void
    {
        $reflection = new \ReflectionClass(HtmlexportView::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('PluginHelper', $source);
    }

    public function testViewUsesHealthStatusEnum(): void
    {
        $reflection = new \ReflectionClass(HtmlexportView::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('HealthStatus', $source);
    }
}
