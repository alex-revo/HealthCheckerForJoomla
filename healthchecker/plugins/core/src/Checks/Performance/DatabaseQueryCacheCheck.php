<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Query Cache Health Check
 *
 * This check examines the MySQL/MariaDB query cache configuration. Note that
 * query cache was deprecated in MySQL 5.7 and removed entirely in MySQL 8.0.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * For MySQL < 8.0 and MariaDB, query cache can improve performance by storing
 * the results of SELECT queries in memory. However, for MySQL 8.0+, query cache
 * no longer exists and application-level caching (like Joomla's cache system)
 * should be used instead.
 *
 * RESULT MEANINGS:
 *
 * GOOD: For MySQL 8.0+: Query cache is not available (expected behavior), use
 * application-level caching. For MySQL < 8.0 or MariaDB: Query cache is properly
 * configured with memory allocated.
 *
 * WARNING: For MySQL < 8.0 or MariaDB: Query cache is disabled or has no memory
 * allocated. Consider enabling it for read-heavy workloads, or use Joomla's
 * built-in caching system instead.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class DatabaseQueryCacheCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'performance.database_query_cache'
     */
    public function getSlug(): string
    {
        return 'performance.database_query_cache';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'performance'
     */
    public function getCategory(): string
    {
        return 'performance';
    }

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Performance/DatabaseQueryCacheCheck.php';
    }

    /**
     * Perform the database query cache health check.
     *
     * This method examines MySQL/MariaDB query cache configuration. Query cache
     * stores SELECT query results in memory for faster retrieval on subsequent
     * identical queries. However, this feature has version-specific considerations:
     *
     * - MySQL 5.7: Query cache deprecated (use application-level caching)
     * - MySQL 8.0+: Query cache removed entirely (not available)
     * - MariaDB: Query cache still available and can improve read-heavy workloads
     *
     * The check performs these steps:
     * 1. Detects database version and type (MySQL vs MariaDB)
     * 2. For MySQL 8.0+: Reports cache unavailable (expected)
     * 3. For MySQL < 8.0 or MariaDB: Examines query_cache% variables
     * 4. Checks query_cache_type (ON/OFF) and query_cache_size (memory allocation)
     *
     * Returns:
     * - GOOD: MySQL 8.0+ (cache not applicable), or cache properly configured
     * - WARNING: Cache disabled, size is 0, or variables unavailable
     *
     * @return HealthCheckResult The result indicating query cache configuration status
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Detect database version and type for version-specific handling
        $version = $database->getVersion();
        $isMariaDb = stripos($version, 'mariadb') !== false;
        $numericVersion = preg_replace('/[^0-9.]/', '', $version);

        if ($numericVersion === null || $numericVersion === '') {
            return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_PERFORMANCE_DATABASE_QUERY_CACHE_WARNING'));
        }

        // MySQL 8.0+ removed query cache entirely - this is expected behavior
        if (! $isMariaDb && version_compare($numericVersion, '8.0', '>=')) {
            return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_PERFORMANCE_DATABASE_QUERY_CACHE_GOOD'));
        }

        // For MySQL < 8.0 or MariaDB, query the query_cache% server variables
        $query = 'SHOW VARIABLES LIKE ' . $database->quote('query_cache%');
        $results = $database->setQuery($query)
            ->loadAssocList('Variable_name', 'Value');

        if ($results === []) {
            return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_PERFORMANCE_DATABASE_QUERY_CACHE_GOOD_2'));
        }

        // Extract query cache configuration values
        $queryCacheType = $results['query_cache_type'] ?? 'OFF';
        $queryCacheSize = (int) ($results['query_cache_size'] ?? 0);

        // Check if query cache is disabled (type = OFF or 0)
        if ($queryCacheType === 'OFF' || $queryCacheType === '0') {
            // MariaDB still supports query cache - could be enabled
            if ($isMariaDb) {
                return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_PERFORMANCE_DATABASE_QUERY_CACHE_WARNING_2'));
            }

            // MySQL 5.7 - query cache deprecated, recommend application caching
            return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_PERFORMANCE_DATABASE_QUERY_CACHE_GOOD_3'));
        }

        // Query cache type is ON but no memory allocated - misconfiguration
        if ($queryCacheSize === 0) {
            return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_PERFORMANCE_DATABASE_QUERY_CACHE_WARNING_3'));
        }

        // Query cache is properly configured - convert bytes to MB for readability
        $sizeInMb = round($queryCacheSize / 1024 / 1024, 2);

        return $this->good(Text::sprintf('COM_HEALTHCHECKER_CHECK_PERFORMANCE_DATABASE_QUERY_CACHE_GOOD_4', $sizeInMb));
    }
}
