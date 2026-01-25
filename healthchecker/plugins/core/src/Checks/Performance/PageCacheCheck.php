<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Page Cache Health Check
 *
 * This check verifies whether the System - Page Cache plugin is enabled
 * and whether browser caching is configured within the plugin settings.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The Page Cache plugin stores complete rendered pages for guest visitors,
 * bypassing most of Joomla's processing on subsequent requests. This can
 * dramatically improve performance for sites with high guest traffic by
 * reducing database queries and PHP execution to near zero for cached pages.
 * Additionally, browser caching allows returning visitors to load pages
 * directly from their browser cache, further reducing server load.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The System - Page Cache plugin is enabled with browser caching.
 * Guest visitors will receive cached full-page responses and returning
 * visitors can load pages from their browser cache.
 *
 * WARNING: Either the plugin is disabled, or it's enabled but browser
 * caching is not configured. For production sites with significant guest
 * traffic, enabling this plugin with browser caching provides optimal
 * performance improvements.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class PageCacheCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check identifier in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'performance.page_cache';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'performance';
    }

    /**
     * Perform the page cache plugin status check.
     *
     * Verifies whether the System - Page Cache plugin is enabled and checks
     * the browser caching configuration. This is one of the most impactful
     * performance optimizations available in Joomla.
     *
     * Performance impact:
     * - Bypasses database queries and PHP execution for cached pages
     * - Can reduce page generation time from 500ms+ to under 10ms
     * - Dramatically reduces server load under high guest traffic
     * - Only caches pages for non-logged-in users (guests)
     * - Browser caching allows returning visitors to skip server requests
     *
     * The check performs these steps:
     * 1. Verifies System - Page Cache plugin is enabled
     * 2. Queries database for plugin parameters
     * 3. Checks if browsercache parameter is enabled
     *
     * @return HealthCheckResult Returns WARNING if disabled or browser caching off, GOOD if fully enabled
     */
    protected function performCheck(): HealthCheckResult
    {
        // Check if the System - Page Cache plugin is enabled
        // Note: The plugin element name is 'cache', not 'pagecache'
        $isEnabled = PluginHelper::isEnabled('system', 'cache');

        // Plugin disabled - significant performance opportunity missed
        if (! $isEnabled) {
            return $this->warning(
                'System - Page Cache plugin is disabled. Enable it in production for improved performance on guest page loads.',
            );
        }

        // Plugin is enabled - check browser caching configuration
        $database = $this->requireDatabase();

        $query = $database->getQuery(true)
            ->select($database->quoteName('params'))
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('cache'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('system'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'));

        $params = $database->setQuery($query)
            ->loadResult();

        if ($params === null || $params === '') {
            // Plugin enabled but can't read params - still good
            return $this->good('System - Page Cache plugin is enabled.');
        }

        $paramsObj = json_decode((string) $params, true);

        if (! is_array($paramsObj)) {
            return $this->good('System - Page Cache plugin is enabled.');
        }

        // Check browser caching setting (1 = enabled, 0 = disabled)
        $browserCache = (int) ($paramsObj['browsercache'] ?? 0);

        if ($browserCache === 1) {
            return $this->good('System - Page Cache plugin is enabled with browser caching.');
        }

        // Plugin enabled but browser caching disabled
        return $this->warning(
            'System - Page Cache plugin is enabled but browser caching is disabled. Enable browser caching in the plugin settings for additional performance.',
        );
    }
}
