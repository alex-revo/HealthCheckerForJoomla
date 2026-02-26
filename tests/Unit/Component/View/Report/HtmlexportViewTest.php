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
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\View\Report\HtmlexportView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlexportView::class)]
class HtmlexportViewTest extends TestCase
{
    private ?CMSApplication $cmsApplication = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Store original app if set
        try {
            $this->cmsApplication = Factory::getApplication();
        } catch (\Exception) {
            $this->cmsApplication = null;
        }

        // Set up a mock application
        $cmsApplication = new CMSApplication();
        Factory::setApplication($cmsApplication);
    }

    protected function tearDown(): void
    {
        // Restore original application
        Factory::setApplication($this->cmsApplication);

        parent::tearDown();
    }

    public function testViewCanBeInstantiated(): void
    {
        $htmlexportView = new HtmlexportView();

        $this->assertInstanceOf(HtmlexportView::class, $htmlexportView);
    }

    public function testViewExtendsBaseHtmlView(): void
    {
        $htmlexportView = new HtmlexportView();

        $this->assertInstanceOf(\Joomla\CMS\MVC\View\HtmlView::class, $htmlexportView);
    }

    public function testDisplayMethodExists(): void
    {
        $this->assertTrue(method_exists(HtmlexportView::class, 'display'));
    }

    public function testDisplayMethodAcceptsNullTemplate(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'display');
        $parameters = $reflectionMethod->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('tpl', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->allowsNull());
    }

    public function testDisplayMethodReturnsVoid(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'display');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    public function testViewHasCorrectNamespace(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);

        $this->assertSame(
            'MySitesGuru\HealthChecker\Component\Administrator\View\Report',
            $reflectionClass->getNamespaceName(),
        );
    }

    public function testViewIsNotAbstract(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);

        $this->assertFalse($reflectionClass->isAbstract());
    }

    public function testViewIsNotFinal(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);

        $this->assertFalse($reflectionClass->isFinal());
    }

    public function testViewClassName(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);

        $this->assertSame('HtmlexportView', $reflectionClass->getShortName());
    }

    public function testRenderHtmlReportMethodExists(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);

        $this->assertTrue($reflectionClass->hasMethod('renderHtmlReport'));
    }

    public function testRenderHtmlReportMethodIsPrivate(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'renderHtmlReport');

        $this->assertTrue($reflectionMethod->isPrivate());
    }

    public function testRenderHtmlReportHasExpectedParameters(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'renderHtmlReport');
        $parameters = $reflectionMethod->getParameters();

        // Method has 12 parameters based on the source
        $this->assertCount(12, $parameters);

        // Check parameter names
        $paramNames = array_map(
            fn(\ReflectionParameter $reflectionParameter): string => $reflectionParameter->getName(),
            $parameters,
        );
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
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'display');
        $source = file_get_contents($reflectionMethod->getFileName());

        $this->assertStringContainsString('getModel', $source);
    }

    public function testViewUsesRunChecksFromModel(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'display');
        $source = file_get_contents($reflectionMethod->getFileName());

        $this->assertStringContainsString('runChecks', $source);
    }

    public function testViewGetsResultsByCategory(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'display');
        $source = file_get_contents($reflectionMethod->getFileName());

        $this->assertStringContainsString('getExportableResultsByCategory', $source);
    }

    public function testViewUsesPluginHelper(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);
        $source = file_get_contents($reflectionClass->getFileName());

        $this->assertStringContainsString('PluginHelper', $source);
    }

    public function testViewUsesHealthStatusEnum(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);
        $source = file_get_contents($reflectionClass->getFileName());

        $this->assertStringContainsString('HealthStatus', $source);
    }

    public function testRenderHtmlReportOutputsHtmlDescriptionWithoutEscaping(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: 'Test Check',
            description: '<p>This is <strong>bold</strong> and <code>code</code></p>',
            slug: 'core.test',
            category: 'system',
        );

        $html = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString('<p>This is <strong>bold</strong> and <code>code</code></p>', $html);
    }

    public function testRenderHtmlReportDoesNotDoubleEscapeHtmlEntities(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Entity Check',
            description: '<p>Use <code>&lt;strong&gt;</code> for bold</p>',
            slug: 'core.entity_test',
            category: 'system',
        );

        $html = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        // Should contain the HTML as-is, not double-escaped
        $this->assertStringContainsString('<p>Use <code>&lt;strong&gt;</code> for bold</p>', $html);
        // Must not contain double-escaped entities
        $this->assertStringNotContainsString('&amp;lt;', $html);
    }

    public function testRenderHtmlReportEscapesTitleWithHtmlspecialchars(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Check <script>alert("xss")</script>',
            description: 'Safe description',
            slug: 'core.xss_test',
            category: 'system',
        );

        $html = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        // Title should be escaped
        $this->assertStringContainsString('Check &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $html);
        // Title should NOT contain raw script tag
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
    }

    public function testRenderHtmlReportShowsDocsLinkWhenDocsUrlProvided(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Docs Check',
            description: 'Has docs',
            slug: 'core.docs_test',
            category: 'system',
            docsUrl: 'https://example.com/docs/test',
        );

        $html = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString('href="https://example.com/docs/test"', $html);
        $this->assertStringContainsString('Documentation', $html);
    }

    public function testRenderHtmlReportHidesDocsLinkWhenDocsUrlIsNull(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'No Docs Check',
            description: 'No docs',
            slug: 'core.no_docs_test',
            category: 'system',
        );

        $html = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringNotContainsString('<div class="check-footer">', $html);
        $this->assertStringNotContainsString('>Documentation</a>', $html);
    }

    public function testRenderHtmlReportEscapesDocsUrl(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Escaped Docs',
            description: 'Test',
            slug: 'core.escaped_docs',
            category: 'system',
            docsUrl: 'https://example.com/docs?foo=1&bar=2',
        );

        $html = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString('href="https://example.com/docs?foo=1&amp;bar=2"', $html);
    }

    /**
     * Helper to invoke the private renderHtmlReport method and capture output.
     *
     * @param array $results Results grouped by category
     */
    private function renderReport(array $results): string
    {
        $htmlexportView = new HtmlexportView();
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'renderHtmlReport');

        ob_start();
        $reflectionMethod->invoke(
            $htmlexportView,
            $results,
            [],
            [],
            'Test Site',
            'January 1, 2026 at 12:00 PM',
            '5.2.0',
            0,
            0,
            1,
            1,
            false,
            '',
        );

        return ob_get_clean();
    }
}
