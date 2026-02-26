<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Component\Administrator\Extension\HealthCheckerComponent;
use MySitesGuru\HealthChecker\Component\Administrator\Service\HealthCheckRunner;

\defined('_JEXEC') || die;

/**
 * Report Model for Health Checker Component
 *
 * Provides data access methods for the health check report views. This model acts as a
 * facade to the HealthCheckRunner service, exposing its functionality to views while
 * following Joomla's MVC pattern.
 *
 * Key responsibilities:
 * - Executing health checks via the runner
 * - Retrieving results (all, by category, or filtered)
 * - Providing statistics (counts by status)
 * - Formatting data for export (JSON)
 *
 * @since 1.0.0
 */
class ReportModel extends BaseDatabaseModel
{
    /**
     * The health check runner service instance
     *
     * @since 1.0.0
     */
    private ?HealthCheckRunner $healthCheckRunner = null;

    /**
     * Execute all registered health checks
     *
     * Triggers the health check runner to execute all checks across all categories.
     * Results are stored in the session and can be retrieved via getResults() methods.
     *
     * @since   1.0.0
     */
    public function runChecks(): void
    {
        $this->getRunner()
            ->run();
    }

    /**
     * Get the health check runner instance
     *
     * Lazily initializes and returns the HealthCheckRunner service from the component.
     * The runner is cached in the model to avoid repeated container lookups.
     *
     * @return  HealthCheckRunner  The health check runner service
     *
     * @since   1.0.0
     */
    public function getRunner(): HealthCheckRunner
    {
        if (! $this->healthCheckRunner instanceof \MySitesGuru\HealthChecker\Component\Administrator\Service\HealthCheckRunner) {
            /** @var HealthCheckerComponent $component */
            $component = Factory::getApplication()->bootComponent('com_healthchecker');
            $this->healthCheckRunner = $component->getHealthCheckRunner();
        }

        return $this->healthCheckRunner;
    }

    /**
     * Get all health check results
     *
     * Returns a flat array of all health check results from the last execution.
     * Results are in the order they were executed, not grouped by category.
     *
     * @return  HealthCheckResult[]  Array of health check result objects
     *
     * @since   1.0.0
     */
    public function getResults(): array
    {
        return $this->getRunner()
            ->getResults();
    }

    /**
     * Get health check results grouped by category
     *
     * Returns an associative array where keys are category slugs and values are arrays
     * of HealthCheckResult objects belonging to that category.
     *
     * Example:
     * [
     *   'system' => [HealthCheckResult, HealthCheckResult, ...],
     *   'database' => [HealthCheckResult, ...],
     *   ...
     * ]
     *
     * @return  array<string, HealthCheckResult[]>  Results grouped by category slug
     *
     * @since   1.0.0
     */
    public function getResultsByCategory(): array
    {
        return $this->getRunner()
            ->getResultsByCategory();
    }

    /**
     * Get filtered health check results
     *
     * Returns results grouped by category, optionally filtered by status and/or category.
     * Empty categories (after filtering) are removed from the result.
     *
     * @param   string|null  $statusFilter    Filter by health status ('critical', 'warning', 'good')
     * @param   string|null  $categoryFilter  Filter by category slug (e.g., 'system', 'database')
     *
     * @return  array<string, HealthCheckResult[]>  Filtered results grouped by category slug
     *
     * @since   1.0.0
     */
    public function getFilteredResults(?string $statusFilter = null, ?string $categoryFilter = null): array
    {
        $results = $this->getResultsByCategory();

        if (! in_array($categoryFilter, [null, '', '0'], true)) {
            $results = array_filter($results, fn($slug): bool => $slug === $categoryFilter, ARRAY_FILTER_USE_KEY);
        }

        if (! in_array($statusFilter, [null, '', '0'], true)) {
            $statusEnum = HealthStatus::tryFrom($statusFilter);
            if ($statusEnum instanceof \MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus) {
                foreach ($results as $category => $categoryResults) {
                    $results[$category] = array_filter(
                        $categoryResults,
                        fn(HealthCheckResult $healthCheckResult): bool => $healthCheckResult->healthStatus === $statusEnum,
                    );
                }

                $results = array_filter($results, fn(array $r): bool => $r !== []);
            }
        }

        return $results;
    }

    /**
     * Get exportable results grouped by category
     *
     * Returns results filtered by export visibility and grouped by category.
     * Used by export views to exclude checks marked as non-exportable.
     *
     * @return array<string, HealthCheckResult[]> Exportable results grouped by category slug
     *
     * @since 3.4.0
     */
    public function getExportableResultsByCategory(): array
    {
        return $this->getRunner()
            ->getExportableResultsByCategory();
    }

    /**
     * Get count of exportable results by status
     *
     * Returns counts for only the results that pass export visibility filtering.
     *
     * @return array{critical: int, warning: int, good: int, total: int} Exportable result counts
     *
     * @since 3.4.0
     */
    public function getExportableCounts(): array
    {
        $results = $this->getRunner()
            ->getExportableResults();
        $counts = [
            'critical' => 0,
            'warning' => 0,
            'good' => 0,
            'total' => count($results),
        ];

        foreach ($results as $result) {
            $counts[$result->healthStatus->value]++;
        }

        return $counts;
    }

    /**
     * Get exportable results filtered by export page parameters
     *
     * Applies additional filters on top of export visibility:
     * - Status filter: 'issues' to include only warning/critical
     * - Category filter: array of category slugs to include
     * - Check filter: array of check slugs to include
     *
     * @param   string        $statusFilter      'all' or 'issues'
     * @param   string[]      $categoryFilter    Category slugs to include (empty = all)
     * @param   string[]      $checkFilter       Check slugs to include (empty = all)
     *
     * @return  array<string, HealthCheckResult[]>  Filtered results grouped by category slug
     *
     * @since   4.0.0
     */
    public function getFilteredExportResults(
        string $statusFilter = 'all',
        array $categoryFilter = [],
        array $checkFilter = [],
    ): array {
        $results = $this->getExportableResultsByCategory();

        // Filter by selected categories (empty = no categories = empty result)
        $results = array_intersect_key($results, array_flip($categoryFilter));

        // Filter by selected checks (empty = no checks = empty result)
        if ($checkFilter !== []) {
            $checkSet = array_flip($checkFilter);

            foreach ($results as $category => $categoryResults) {
                $results[$category] = array_filter(
                    $categoryResults,
                    static fn(HealthCheckResult $healthCheckResult): bool => isset($checkSet[$healthCheckResult->slug]),
                );
            }

            $results = array_filter($results, static fn(array $r): bool => $r !== []);
        }

        // Filter by status
        if ($statusFilter === 'issues') {
            foreach ($results as $category => $categoryResults) {
                $results[$category] = array_filter(
                    $categoryResults,
                    static fn(HealthCheckResult $healthCheckResult): bool => $healthCheckResult->healthStatus !== HealthStatus::Good,
                );
            }

            $results = array_filter($results, static fn(array $r): bool => $r !== []);
        }

        return $results;
    }

    /**
     * Get counts from a pre-filtered result set
     *
     * @param   array<string, HealthCheckResult[]>  $results  Results grouped by category
     *
     * @return  array{critical: int, warning: int, good: int, total: int}
     *
     * @since   4.0.0
     */
    public function getCountsFromResults(array $results): array
    {
        $counts = [
            'critical' => 0,
            'warning' => 0,
            'good' => 0,
            'total' => 0,
        ];

        foreach ($results as $result) {
            foreach ($result as $categoryResult) {
                $counts[$categoryResult->healthStatus->value]++;
                $counts['total']++;
            }
        }

        return $counts;
    }

    /**
     * Export filtered results as JSON
     *
     * @param   string    $statusFilter    'all' or 'issues'
     * @param   string[]  $categoryFilter  Category slugs to include
     * @param   string[]  $checkFilter     Check slugs to include
     *
     * @return  string  Pretty-printed JSON
     *
     * @since   4.0.0
     */
    public function toFilteredExportJson(
        string $statusFilter = 'all',
        array $categoryFilter = [],
        array $checkFilter = [],
    ): string {
        $results = $this->getFilteredExportResults($statusFilter, $categoryFilter, $checkFilter);
        $counts = $this->getCountsFromResults($results);
        $healthCheckRunner = $this->getRunner();

        $flatResults = [];

        foreach ($results as $result) {
            foreach ($result as $categoryResult) {
                $flatResults[] = $categoryResult->toArray();
            }
        }

        $data = [
            'lastRun' => $healthCheckRunner->getLastRun()?->format('c'),
            'summary' => $counts,
            'categories' => array_map(
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory $healthCategory): array => $healthCategory->toArray(),
                $healthCheckRunner->getCategoryRegistry()
                    ->all(),
            ),
            'providers' => array_map(
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata $providerMetadata): array => $providerMetadata->toArray(),
                $healthCheckRunner->getProviderRegistry()
                    ->all(),
            ),
            'results' => $flatResults,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode health check results to JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Get count of checks with critical status
     *
     * @return  int  Number of checks that returned a critical status
     *
     * @since   1.0.0
     */
    public function getCriticalCount(): int
    {
        return $this->getRunner()
            ->getCriticalCount();
    }

    /**
     * Get count of checks with warning status
     *
     * @return  int  Number of checks that returned a warning status
     *
     * @since   1.0.0
     */
    public function getWarningCount(): int
    {
        return $this->getRunner()
            ->getWarningCount();
    }

    /**
     * Get count of checks with good status
     *
     * @return  int  Number of checks that returned a good status
     *
     * @since   1.0.0
     */
    public function getGoodCount(): int
    {
        return $this->getRunner()
            ->getGoodCount();
    }

    /**
     * Get total count of all checks
     *
     * @return  int  Total number of checks executed
     *
     * @since   1.0.0
     */
    public function getTotalCount(): int
    {
        return $this->getRunner()
            ->getTotalCount();
    }

    /**
     * Get timestamp of last check execution
     *
     * @return  \DateTimeImmutable|null  Timestamp of last run, or null if checks haven't been run
     *
     * @since   1.0.0
     */
    public function getLastRun(): ?\DateTimeImmutable
    {
        return $this->getRunner()
            ->getLastRun();
    }

    /**
     * Export all results as formatted JSON string
     *
     * Converts the complete health check report to a formatted JSON string suitable
     * for download/export. Includes results, metadata, and statistics.
     *
     * @return  string  Pretty-printed JSON representation of all results
     *
     * @since   1.0.0
     */
    public function toJson(): string
    {
        $json = json_encode(
            $this->getRunner()
                ->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        if ($json === false) {
            throw new \RuntimeException('Failed to encode health check results to JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Export only exportable results as formatted JSON string
     *
     * Same as toJson() but filters results by export visibility before encoding.
     * Checks with ExportVisibility::Never are excluded, and checks with
     * ExportVisibility::IssuesOnly are excluded when their status is Good.
     *
     * @return  string  Pretty-printed JSON representation of exportable results
     *
     * @since   3.4.0
     */
    public function toExportJson(): string
    {
        $healthCheckRunner = $this->getRunner();
        $exportableResults = $healthCheckRunner->getExportableResults();
        $counts = $this->getExportableCounts();

        $data = [
            'lastRun' => $healthCheckRunner->getLastRun()?->format('c'),
            'summary' => $counts,
            'categories' => array_map(
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Category\HealthCategory $healthCategory): array => $healthCategory->toArray(),
                $healthCheckRunner->getCategoryRegistry()
                    ->all(),
            ),
            'providers' => array_map(
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Provider\ProviderMetadata $providerMetadata): array => $providerMetadata->toArray(),
                $healthCheckRunner->getProviderRegistry()
                    ->all(),
            ),
            'results' => array_map(
                fn(\MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult $healthCheckResult): array => $healthCheckResult->toArray(),
                $exportableResults,
            ),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode health check results to JSON: ' . json_last_error_msg());
        }

        return $json;
    }
}
