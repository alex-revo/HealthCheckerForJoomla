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
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata;

\defined('_JEXEC') || die;

/**
 * Markdown Export View for Health Checker Report
 *
 * Generates a plain-text Markdown export of the health check report suitable for
 * pasting into support tickets, client messages, chat apps, or documentation.
 *
 * The Markdown export includes:
 * - Site name and Joomla version in header
 * - Summary statistics table
 * - All health check results organized by category
 * - Provider attribution for third-party checks
 * - Text-only footer with attribution and community plugin links
 *
 * @since 3.4.0
 */
class MarkdownView extends BaseHtmlView
{
    /**
     * Display the Markdown export
     *
     * Executes all health checks, gathers metadata, and renders a Markdown document.
     * The document is sent as a downloadable .md file with appropriate headers.
     *
     * Filename format: health-report-YYYY-MM-DD.md
     *
     * This method terminates the application after sending the response.
     *
     * @param   string|null  $tpl  The name of the template file to parse (not used for export)
     *
     * @since   3.4.0
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
        $thirdPartyProviders = $model->getRunner()
            ->getProviderRegistry()
            ->getThirdParty();

        $siteName = $cmsApplication->get('sitename', 'Joomla Site');
        $reportDate = date('F j, Y \a\t g:i A');
        $joomlaVersion = JVERSION;

        $criticalCount = $exportCounts['critical'];
        $warningCount = $exportCounts['warning'];
        $goodCount = $exportCounts['good'];
        $totalCount = $exportCounts['total'];

        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="health-report-' . date('Y-m-d') . '.md"');

        echo $this->renderMarkdownReport(
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
        );

        $cmsApplication->close();
    }

    /**
     * Render the complete Markdown report
     *
     * Builds a Markdown document with header, summary table, categorized check results,
     * and footer with attribution.
     *
     * @param   array<string, array<HealthCheckResult>>  $results               Results grouped by category
     * @param   array                                   $categories            Category metadata registry
     * @param   array<string, ProviderMetadata>         $providers             Provider metadata registry
     * @param   array<string, ProviderMetadata>         $thirdPartyProviders   Non-core providers
     * @param   string                                  $siteName              Name of the Joomla site
     * @param   string                                  $reportDate            Formatted date/time
     * @param   string                                  $joomlaVersion         Joomla version string
     * @param   int                                     $criticalCount         Count of critical checks
     * @param   int                                     $warningCount          Count of warning checks
     * @param   int                                     $goodCount             Count of good checks
     * @param   int                                     $totalCount            Total count of all checks
     *
     * @return  string  The complete Markdown document
     *
     * @since   3.4.0
     */
    private function renderMarkdownReport(
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
    ): string {
        $lines = [];

        // Header
        $lines[] = '# ' . Text::_('COM_HEALTHCHECKER_REPORT') . ' - ' . $siteName;
        $lines[] = '';
        $lines[] = 'Generated on ' . $reportDate . ' | Joomla ' . $joomlaVersion;
        $lines[] = '';

        // Summary table
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = '| Status | Count |';
        $lines[] = '|--------|------:|';
        $lines[] = '| ' . $this->statusEmoji(
            HealthStatus::Critical,
        ) . ' ' . Text::_('COM_HEALTHCHECKER_CRITICAL') . ' | ' . $criticalCount . ' |';
        $lines[] = '| ' . $this->statusEmoji(
            HealthStatus::Warning,
        ) . ' ' . Text::_('COM_HEALTHCHECKER_WARNING') . ' | ' . $warningCount . ' |';
        $lines[] = '| ' . $this->statusEmoji(
            HealthStatus::Good,
        ) . ' ' . Text::_('COM_HEALTHCHECKER_GOOD') . ' | ' . $goodCount . ' |';
        $lines[] = '| **Total** | **' . $totalCount . '** |';
        $lines[] = '';

        // Categories and checks
        foreach ($results as $categorySlug => $categoryResults) {
            if (empty($categoryResults)) {
                continue;
            }

            $category = $categories[$categorySlug] ?? null;
            $categoryTitle = $category ? Text::_($category->label) : $categorySlug;

            $lines[] = '## ' . $categoryTitle;
            $lines[] = '';
            $lines[] = '| Status | Check | Description |';
            $lines[] = '|--------|-------|-------------|';

            foreach ($categoryResults as $categoryResult) {
                $statusLabel = strtoupper($categoryResult->healthStatus->value);
                $emoji = $this->statusEmoji($categoryResult->healthStatus);
                $title = $this->stripHtml($categoryResult->title);

                $providerSuffix = '';

                if ($categoryResult->provider !== 'core') {
                    $provider = $providers[$categoryResult->provider] ?? null;
                    $providerName = $provider instanceof ProviderMetadata ? $provider->name : $categoryResult->provider;
                    $providerSuffix = ' _(' . $providerName . ')_';
                }

                $description = $this->htmlToMarkdownInline($categoryResult->description);

                if ($categoryResult->docsUrl !== null) {
                    $description .= ($description !== '' ? ' ' : '') . '[Docs](' . $categoryResult->docsUrl . ')';
                }

                $lines[] = '| ' . $emoji . ' ' . $statusLabel . ' | ' . $title . $providerSuffix . ' | ' . $description . ' |';
            }

            $lines[] = '';
        }

        // Footer
        $lines[] = '---';
        $lines[] = '';
        $footer = 'Generated by [Health Checker for Joomla](https://github.com/mySites-guru/HealthCheckerForJoomla) | A free GPL extension from [mySites.guru](https://mysites.guru)';

        if ($thirdPartyProviders !== []) {
            $pluginLinks = [];

            foreach ($thirdPartyProviders as $thirdPartyProvider) {
                if ($thirdPartyProvider->url !== null) {
                    $pluginLinks[] = '[' . $thirdPartyProvider->name . '](' . $thirdPartyProvider->url . ')';
                } else {
                    $pluginLinks[] = $thirdPartyProvider->name;
                }
            }

            $footer .= ' | with Community plugins: ' . implode(', ', $pluginLinks);
        }

        $lines[] = $footer;
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Get the status emoji for a health status
     *
     * @param HealthStatus $healthStatus The health status
     *
     * @return  string  The emoji character
     *
     * @since   3.4.0
     */
    private function statusEmoji(HealthStatus $healthStatus): string
    {
        return match ($healthStatus) {
            HealthStatus::Critical => "\u{1F534}",
            HealthStatus::Warning => "\u{1F7E1}",
            HealthStatus::Good => "\u{1F7E2}",
        };
    }

    /**
     * Strip all HTML tags from a string
     *
     * @param   string  $text  The HTML text
     *
     * @return  string  Plain text
     *
     * @since   3.4.0
     */
    private function stripHtml(string $text): string
    {
        return trim(strip_tags($text));
    }

    /**
     * Convert HTML to single-line Markdown suitable for table cells
     *
     * Produces inline Markdown with no line breaks and escaped pipe characters,
     * so the output can safely be placed inside a Markdown table cell.
     *
     * @param   string  $html  The HTML content
     *
     * @return  string  Single-line Markdown text
     *
     * @since   3.5.0
     */
    private function htmlToMarkdownInline(string $html): string
    {
        $text = $this->htmlToMarkdown($html);

        // Collapse all whitespace/newlines into single spaces for table cells
        $text = (string) preg_replace('/\s+/', ' ', $text);

        // Escape pipe characters so they don't break table formatting
        $text = str_replace('|', '\|', $text);

        return trim($text);
    }

    /**
     * Convert simple HTML to Markdown
     *
     * Handles common HTML elements found in health check descriptions:
     * code, pre, strong, em, br, p, ul/li tags.
     *
     * @param   string  $html  The HTML content
     *
     * @return  string  Markdown-formatted text
     *
     * @since   3.4.0
     */
    private function htmlToMarkdown(string $html): string
    {
        $text = $html;

        // Convert <br> to newlines
        $text = (string) preg_replace('/<br\s*\/?>/i', "\n", $text);

        // Convert <p> tags to double newlines
        $text = (string) preg_replace('/<\/p>\s*/i', "\n\n", $text);
        $text = (string) preg_replace('/<p[^>]*>/i', '', $text);

        // Convert <strong>/<b> to bold
        $text = (string) preg_replace('/<(?:strong|b)>(.*?)<\/(?:strong|b)>/is', '**$1**', $text);

        // Convert <em>/<i> to italic
        $text = (string) preg_replace('/<(?:em|i)>(.*?)<\/(?:em|i)>/is', '*$1*', $text);

        // Convert <code> to backticks
        $text = (string) preg_replace('/<code>(.*?)<\/code>/is', '`$1`', $text);

        // Convert <pre> blocks to fenced code blocks
        $text = (string) preg_replace('/<pre[^>]*>(.*?)<\/pre>/is', "\n```\n$1\n```\n", $text);

        // Convert <a> to Markdown links
        $text = (string) preg_replace('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '[$2]($1)', $text);

        // Convert <ul>/<ol> list items
        $text = (string) preg_replace('/<li[^>]*>(.*?)<\/li>/is', "- $1\n", $text);
        $text = (string) preg_replace('/<\/?(?:ul|ol)[^>]*>/i', "\n", $text);

        // Strip remaining HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Clean up excessive whitespace while preserving intentional line breaks
        $text = (string) preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}
