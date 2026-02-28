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
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;
use MySitesGuru\HealthChecker\Component\Administrator\View\Report\MarkdownView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MarkdownView::class)]
class MarkdownViewTest extends TestCase
{
    private ?CMSApplication $cmsApplication = null;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->cmsApplication = Factory::getApplication();
        } catch (\Exception) {
            $this->cmsApplication = null;
        }

        $cmsApplication = new CMSApplication();
        Factory::setApplication($cmsApplication);
    }

    protected function tearDown(): void
    {
        Factory::setApplication($this->cmsApplication);

        parent::tearDown();
    }

    public function testViewCanBeInstantiated(): void
    {
        $markdownView = new MarkdownView();

        $this->assertInstanceOf(MarkdownView::class, $markdownView);
    }

    public function testViewExtendsBaseHtmlView(): void
    {
        $markdownView = new MarkdownView();

        $this->assertInstanceOf(\Joomla\CMS\MVC\View\HtmlView::class, $markdownView);
    }

    public function testDisplayMethodExists(): void
    {
        $this->assertTrue(method_exists(MarkdownView::class, 'display'));
    }

    public function testDisplayMethodAcceptsNullTemplate(): void
    {
        $reflectionMethod = new \ReflectionMethod(MarkdownView::class, 'display');
        $parameters = $reflectionMethod->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('tpl', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->allowsNull());
    }

    public function testDisplayMethodReturnsVoid(): void
    {
        $reflectionMethod = new \ReflectionMethod(MarkdownView::class, 'display');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    public function testRenderMarkdownReportMethodExists(): void
    {
        $reflectionClass = new \ReflectionClass(MarkdownView::class);

        $this->assertTrue($reflectionClass->hasMethod('renderMarkdownReport'));
    }

    public function testRenderMarkdownReportMethodIsPrivate(): void
    {
        $reflectionMethod = new \ReflectionMethod(MarkdownView::class, 'renderMarkdownReport');

        $this->assertTrue($reflectionMethod->isPrivate());
    }

    public function testReportContainsHeader(): void
    {
        $markdown = $this->renderReport([]);

        $this->assertStringContainsString('# ', $markdown);
        $this->assertStringContainsString('Test Site', $markdown);
    }

    public function testReportContainsDateAndJoomlaVersion(): void
    {
        $markdown = $this->renderReport([]);

        $this->assertStringContainsString('January 1, 2026 at 12:00 PM', $markdown);
        $this->assertStringContainsString('Joomla 5.2.0', $markdown);
    }

    public function testReportContainsSummaryTable(): void
    {
        $markdown = $this->renderReport([], criticalCount: 2, warningCount: 3, goodCount: 10, totalCount: 15);

        $this->assertStringContainsString('## Summary', $markdown);
        $this->assertStringContainsString('| Status | Count |', $markdown);
        $this->assertStringContainsString('| 2 |', $markdown);
        $this->assertStringContainsString('| 3 |', $markdown);
        $this->assertStringContainsString('| 10 |', $markdown);
        $this->assertStringContainsString('| **15** |', $markdown);
    }

    public function testReportContainsCategoryHeadings(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test Check',
            description: 'All good',
            slug: 'core.test',
            category: 'system',
        );

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString('## system', $markdown);
    }

    public function testReportContainsCheckWithStatusBadge(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Critical,
            title: 'PHP Version',
            description: 'PHP version is outdated',
            slug: 'core.php_version',
            category: 'system',
        );

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString('CRITICAL', $markdown);
        $this->assertStringContainsString('PHP Version', $markdown);
        $this->assertStringContainsString('PHP version is outdated', $markdown);
    }

    public function testReportContainsWarningEmoji(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: 'Test',
            description: 'Warn',
            slug: 'core.test',
            category: 'system',
        );

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString("\u{1F7E1}", $markdown);
        $this->assertStringContainsString('WARNING', $markdown);
    }

    public function testReportContainsGoodEmoji(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Good',
            slug: 'core.test',
            category: 'system',
        );

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString("\u{1F7E2}", $markdown);
        $this->assertStringContainsString('GOOD', $markdown);
    }

    public function testReportShowsProviderAttributionForNonCoreChecks(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Backup Check',
            description: 'Backup OK',
            slug: 'akeeba.backup',
            category: 'system',
            provider: 'akeeba',
        );

        $providers = [
            'core' => new ProviderMetadata(slug: 'core', name: 'Core'),
            'akeeba' => new ProviderMetadata(slug: 'akeeba', name: 'Akeeba Backup', url: 'https://akeeba.com'),
        ];

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
        ], providers: $providers);

        $this->assertStringContainsString('_(Akeeba Backup)_', $markdown);
    }

    public function testReportShowsDocsLink(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Good',
            slug: 'core.test',
            category: 'system',
            docsUrl: 'https://example.com/docs/test',
        );

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString('[Docs](https://example.com/docs/test)', $markdown);
    }

    public function testReportHidesDocsLinkWhenNull(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Good',
            slug: 'core.test',
            category: 'system',
        );

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringNotContainsString('[Documentation]', $markdown);
    }

    public function testReportContainsFooterAttribution(): void
    {
        $markdown = $this->renderReport([]);

        $this->assertStringContainsString('---', $markdown);
        $this->assertStringContainsString('[Health Checker for Joomla]', $markdown);
        $this->assertStringContainsString('[mySites.guru]', $markdown);
        $this->assertStringContainsString('free GPL extension', $markdown);
    }

    public function testReportFooterIncludesThirdPartyPlugins(): void
    {
        $thirdParty = [
            'akeeba' => new ProviderMetadata(slug: 'akeeba', name: 'Akeeba Backup', url: 'https://akeeba.com'),
            'custom' => new ProviderMetadata(slug: 'custom', name: 'Custom Plugin'),
        ];

        $results = [
            'system' => [
                new HealthCheckResult(
                    healthStatus: HealthStatus::Good,
                    title: 'Akeeba Check',
                    description: 'OK',
                    slug: 'akeeba.backup',
                    category: 'system',
                    provider: 'akeeba',
                ),
                new HealthCheckResult(
                    healthStatus: HealthStatus::Warning,
                    title: 'Custom Check',
                    description: 'Warn',
                    slug: 'custom.test',
                    category: 'system',
                    provider: 'custom',
                ),
            ],
        ];

        $markdown = $this->renderReport($results, thirdPartyProviders: $thirdParty);

        $this->assertStringContainsString('Community plugins:', $markdown);
        $this->assertStringContainsString('[Akeeba Backup](https://akeeba.com)', $markdown);
        $this->assertStringContainsString('Custom Plugin', $markdown);
    }

    public function testReportFooterOmitsPluginSectionWhenNoThirdParty(): void
    {
        $markdown = $this->renderReport([]);

        $this->assertStringNotContainsString('Community plugins', $markdown);
    }

    public function testReportConvertsHtmlToMarkdown(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: 'Test',
            description: '<p>This is <strong>bold</strong> and <code>inline code</code></p>',
            slug: 'core.test',
            category: 'system',
        );

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString('**bold**', $markdown);
        $this->assertStringContainsString('`inline code`', $markdown);
        $this->assertStringNotContainsString('<strong>', $markdown);
        $this->assertStringNotContainsString('<code>', $markdown);
    }

    public function testReportConvertsHtmlLinksToMarkdown(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Warning,
            title: 'Test',
            description: '<a href="https://example.com">Click here</a>',
            slug: 'core.test',
            category: 'system',
        );

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString('[Click here](https://example.com)', $markdown);
    }

    public function testReportStripsHtmlFromTitles(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: '<b>Bold Title</b>',
            description: 'Good',
            slug: 'core.test',
            category: 'system',
        );

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
        ]);

        $this->assertStringContainsString('Bold Title', $markdown);
        $this->assertStringNotContainsString('<b>', $markdown);
    }

    public function testReportSkipsEmptyCategories(): void
    {
        $healthCheckResult = new HealthCheckResult(
            healthStatus: HealthStatus::Good,
            title: 'Test',
            description: 'Good',
            slug: 'core.test',
            category: 'system',
        );

        $markdown = $this->renderReport([
            'system' => [$healthCheckResult],
            'empty_category' => [],
        ]);

        $this->assertStringContainsString('## system', $markdown);
        $this->assertStringNotContainsString('empty_category', $markdown);
    }

    /**
     * Helper to invoke the private renderMarkdownReport method.
     *
     * @param array<string, array<HealthCheckResult>> $results
     * @param array<string, ProviderMetadata>         $providers
     * @param array<string, ProviderMetadata>         $thirdPartyProviders
     */
    private function renderReport(
        array $results,
        array $providers = [],
        array $thirdPartyProviders = [],
        int $criticalCount = 0,
        int $warningCount = 0,
        int $goodCount = 0,
        int $totalCount = 0,
        string $statusFilter = 'all',
    ): string {
        $markdownView = new MarkdownView();
        $reflectionMethod = new \ReflectionMethod(MarkdownView::class, 'renderMarkdownReport');

        return $reflectionMethod->invoke(
            $markdownView,
            $results,
            [],
            $providers,
            $thirdPartyProviders,
            'Test Site',
            'January 1, 2026 at 12:00 PM',
            '5.2.0',
            $criticalCount,
            $warningCount,
            $goodCount,
            $totalCount,
            $statusFilter,
        );
    }
}
