<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\View\Report;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\Event\BeforeReportExportDisplayEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;

\defined('_JEXEC') || die;

/**
 * HTML Export View for Health Checker Report
 *
 * Generates a standalone, self-contained HTML export of the health check report.
 * This view creates a complete HTML document with embedded CSS that can be saved
 * or emailed without external dependencies.
 *
 * The HTML export includes:
 * - Site name and Joomla version in header
 * - Summary statistics cards (critical, warning, good, total)
 * - All health check results organized by category
 * - Provider attribution for third-party checks
 * - Plugin-injected banners via BeforeReportExportDisplayEvent
 * - Print-optimized CSS
 *
 * @since 1.0.0
 */
class HtmlexportView extends BaseHtmlView
{
    /**
     * Display the HTML export
     *
     * Executes all health checks, gathers metadata, and renders a complete standalone
     * HTML document with embedded styles. The document is sent as a downloadable file
     * with appropriate headers.
     *
     * Filename format: health-report-{domain}-YYYY-MM-DD.html
     *
     * This method terminates the application after sending the response.
     *
     * @param   string|null  $tpl  The name of the template file to parse (not used for export)
     *
     * @since   1.0.0
     */
    public function display($tpl = null): void
    {
        $cmsApplication = Factory::getApplication();
        $input = $cmsApplication->getInput();

        /** @var \MySitesGuru\HealthChecker\Component\Administrator\Model\ReportModel $model */
        $model = $this->getModel();
        $model->runChecks();

        $isFiltered = $input->getInt('export_filtered', 0) === 1;
        $statusFilter = $input->getString('export_status', 'all');
        $categoryFilter = $input->get('export_categories', [], 'array');
        $checkFilter = $input->get('export_checks', [], 'array');

        if ($isFiltered) {
            $results = $model->getFilteredExportResults($statusFilter, $categoryFilter, $checkFilter);
            $exportCounts = $model->getCountsFromResults($results);
        } else {
            $results = $model->getExportableResultsByCategory();
            $exportCounts = $model->getExportableCounts();
        }

        $categories = $model->getRunner()
            ->getCategoryRegistry()
            ->all();
        $providers = $model->getRunner()
            ->getProviderRegistry()
            ->all();

        $siteName = $cmsApplication->get('sitename', 'Joomla Site');
        $reportDate = date('F j, Y \a\t g:i A');
        $joomlaVersion = JVERSION;

        $criticalCount = $exportCounts['critical'];
        $warningCount = $exportCounts['warning'];
        $goodCount = $exportCounts['good'];
        $totalCount = $exportCounts['total'];

        // Dispatch event so plugins can inject banners into the HTML export
        $beforeReportExportDisplayEvent = new BeforeReportExportDisplayEvent();
        $cmsApplication->getDispatcher()
            ->dispatch(HealthCheckerEvents::BEFORE_REPORT_EXPORT_DISPLAY->value, $beforeReportExportDisplayEvent);
        $beforeExportHtml = $beforeReportExportDisplayEvent->getHtmlContent();

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $model::getExportFilename('html') . '"');

        $thirdPartyProviders = $model->getRunner()
            ->getProviderRegistry()
            ->getThirdParty();

        $this->renderHtmlReport(
            $results,
            $categories,
            $providers,
            $thirdPartyProviders,
            $siteName,
            $reportDate,
            $joomlaVersion,
            $criticalCount,
            $warningCount,
            $goodCount,
            $totalCount,
            $beforeExportHtml,
            $statusFilter,
        );

        $cmsApplication->close();
    }

    /**
     * Render the HTML report document
     *
     * Outputs a complete, self-contained HTML document with embedded CSS.
     * The document includes all check results organized by category with status badges,
     * summary statistics, and optional promotional content.
     *
     * @param   array   $results                  Health check results grouped by category
     * @param   array   $categories               Category metadata registry
     * @param   array   $providers                Provider metadata registry
     * @param   array   $thirdPartyProviders      Non-core provider metadata
     * @param   string  $siteName                 Name of the Joomla site
     * @param   string  $reportDate               Formatted date/time of report generation
     * @param   string  $joomlaVersion            Joomla version string
     * @param   int     $criticalCount            Count of critical status checks
     * @param   int     $warningCount             Count of warning status checks
     * @param   int     $goodCount                Count of good status checks
     * @param   int     $totalCount               Total count of all checks
     * @param   string  $beforeExportHtml         HTML content injected by plugins via BeforeReportExportDisplayEvent
     * @param   string  $statusFilter             Status filter ('all' or 'issues')
     *
     * @since   1.0.0
     */
    private function renderHtmlReport(
        array $results,
        array $categories,
        array $providers,
        array $thirdPartyProviders,
        string $siteName,
        string $reportDate,
        string $joomlaVersion,
        int $criticalCount,
        int $warningCount,
        int $goodCount,
        int $totalCount,
        string $beforeExportHtml,
        string $statusFilter,
    ): void {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Check Report - <?php echo htmlspecialchars($siteName); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 30px;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header .subtitle {
            opacity: 0.9;
            font-size: 14px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .summary-card {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .summary-card .count {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .summary-card .label {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-card.critical .count { color: #dc3545; }
        .summary-card.warning .count { color: #ffc107; }
        .summary-card.good .count { color: #28a745; }
        .summary-card.total .count { color: #007bff; }

        .content {
            padding: 30px;
        }

        .category {
            margin-bottom: 40px;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .category-header h2 {
            font-size: 20px;
            color: #495057;
        }

        .check {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: white;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .check-body {
            flex: 1;
            min-width: 0;
        }

        .check-header {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 10px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
            min-width: 94px;
            text-align: center;
        }

        .status-badge.critical {
            background: #dc3545;
            color: white;
        }

        .status-badge.warning {
            background: #ffc107;
            color: #000;
        }

        .status-badge.good {
            background: #28a745;
            color: white;
        }

        .check-title {
            font-size: 16px;
            font-weight: 600;
            color: #212529;
            flex: 1;
        }

        .check-provider {
            font-size: 12px;
            color: #6c757d;
            background: #e9ecef;
            padding: 4px 10px;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .check-description {
            color: #495057;
            line-height: 1.6;
        }

        .check-description code {
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #e83e8c;
        }

        .check-description pre {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px 16px;
            overflow-x: auto;
            font-size: 0.9em;
            margin: 8px 0;
        }

        .check-description pre code {
            background: none;
            padding: 0;
            color: inherit;
        }

        .check-footer {
            margin-top: 8px;
            font-size: 13px;
        }

        .check-footer a {
            color: #3498db;
            text-decoration: none;
        }

        .check-footer a:hover {
            text-decoration: underline;
        }

        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }

        .footer a {
            color: #3498db;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
            }

            .check {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo Text::_('COM_HEALTHCHECKER_REPORT'); ?> - <?php echo htmlspecialchars($siteName); ?></h1>
            <div class="subtitle">
                Generated on <?php echo $reportDate; ?> | Joomla <?php echo htmlspecialchars($joomlaVersion); ?>
            </div>
        </div>

        <div class="summary">
            <div class="summary-card critical">
                <div class="count"><?php echo $criticalCount; ?></div>
                <div class="label"><?php echo Text::_('COM_HEALTHCHECKER_CRITICAL'); ?></div>
            </div>
            <div class="summary-card warning">
                <div class="count"><?php echo $warningCount; ?></div>
                <div class="label"><?php echo Text::_('COM_HEALTHCHECKER_WARNING'); ?></div>
            </div>
            <?php if ($statusFilter !== 'issues'): ?>
            <div class="summary-card good">
                <div class="count"><?php echo $goodCount; ?></div>
                <div class="label"><?php echo Text::_('COM_HEALTHCHECKER_GOOD'); ?></div>
            </div>
            <?php endif; ?>
            <div class="summary-card total">
                <div class="count"><?php echo $totalCount; ?></div>
                <div class="label"><?php echo Text::_('COM_HEALTHCHECKER_TOTAL_CHECKS'); ?></div>
            </div>
        </div>

        <?php if ($beforeExportHtml !== ''): ?>
            <?php
            // Security note: This HTML comes from installed Joomla plugins which are trusted code
            // (they require administrator installation privileges). No user input flows here.
            echo $beforeExportHtml;
            ?>
        <?php endif; ?>

        <div class="content">
            <?php foreach ($results as $categorySlug => $categoryResults) {
                ?>
                <?php
                if (empty($categoryResults)) {
                    continue;
                }

                ?>

                <?php
                $category = $categories[$categorySlug] ?? null;
                $categoryTitle = $category ? Text::_($category->label) : $categorySlug;
                $categoryIcon = $category ? $category->icon : '';
                ?>

                <div class="category">
                    <div class="category-header">
                        <h2><?php
                echo htmlspecialchars((string) $categoryTitle);
                ?></h2>
                    </div>

                    <?php
                foreach ($categoryResults as $categoryResult): ?>
                        <div class="check">
                            <span class="status-badge <?php echo $categoryResult->healthStatus->value; ?>">
                                <?php echo $categoryResult->healthStatus === HealthStatus::Critical ? '🔴 ' : ($categoryResult->healthStatus === HealthStatus::Warning ? '🟡 ' : '🟢 '); ?>
                                <?php echo strtoupper((string) $categoryResult->healthStatus->value); ?>
                            </span>
                            <div class="check-body">
                                <div class="check-header">
                                    <span class="check-title"><?php echo htmlspecialchars((string) $categoryResult->title); ?></span>
                                    <?php if ($categoryResult->provider !== 'core'): ?>
                                        <?php
                                            $provider = $providers[$categoryResult->provider] ?? null;
                                        $providerName = $provider ? $provider->name : $categoryResult->provider;
                                        ?>
                                        <span class="check-provider"><?php echo htmlspecialchars((string) $providerName); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="check-description">
                                    <?php
                                        $descriptionSanitizer = new \MySitesGuru\HealthChecker\Component\Administrator\Service\DescriptionSanitizer();
                    echo $descriptionSanitizer->sanitize((string) $categoryResult->description);
                    ?>
                                </div>
                                <?php if ($categoryResult->docsUrl !== null): ?>
                                    <div class="check-footer">
                                        <a href="<?php echo htmlspecialchars($categoryResult->docsUrl); ?>" target="_blank" rel="noopener">Documentation</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
<?php endforeach;

                ?>
                </div>
            <?php
            }
        ?>
        </div>

        <div class="footer">
            Generated by <a href="https://github.com/mySites-guru/health-checker-for-joomla" target="_blank">Health Checker for Joomla</a>
            | A free GPL extension from <a href="https://mysites.guru" target="_blank">mySites.guru</a>
            <?php
            $presentProviders = [];

        foreach ($results as $result) {
            foreach ($result as $categoryResult) {
                if ($categoryResult->provider !== 'core') {
                    $presentProviders[$categoryResult->provider] = true;
                }
            }
        }

        $filteredProviders = array_filter(
            $thirdPartyProviders,
            fn($provider): bool => isset($presentProviders[$provider->slug]),
        );

        if ($filteredProviders !== []):
            $pluginLinks = [];

            foreach ($filteredProviders as $filteredProvider) {
                if ($filteredProvider->url !== null) {
                    $pluginLinks[] = '<a href="' . htmlspecialchars(
                        $filteredProvider->url,
                    ) . '" target="_blank">' . htmlspecialchars((string) $filteredProvider->name) . '</a>';
                } else {
                    $pluginLinks[] = htmlspecialchars((string) $filteredProvider->name);
                }
            }

        ?>
            | with Community plugins: <?php echo implode(', ', $pluginLinks); ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
    }
}
