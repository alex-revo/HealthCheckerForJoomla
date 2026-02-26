<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Plugin Order Health Check
 *
 * This check analyzes the execution order of system plugins to identify potential
 * ordering issues that could affect site functionality or performance.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * System plugins execute in a specific order determined by their ordering value.
 * Incorrect ordering can cause functional issues - for example, if the SEF plugin
 * runs before the Redirect plugin, redirects may not work correctly. Similarly,
 * the Session plugin should run early, and the Cache plugin should typically run
 * last to cache the final output.
 *
 * RESULT MEANINGS:
 *
 * GOOD: System plugin ordering follows recommended practices. The Session plugin
 * runs early, Cache plugin runs late, and no known conflicts are detected.
 *
 * WARNING: Plugin ordering issues detected that may cause functional problems.
 * Common issues include: SEF running before Redirect, Session running too late,
 * or Cache running too early in the execution order.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class PluginOrderCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in the format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.plugin_order';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'extensions';
    }

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Extensions/PluginOrderCheck.php';
    }

    public function getActionUrl(?HealthStatus $healthStatus = null): ?string
    {
        if ($healthStatus === HealthStatus::Warning) {
            return '/administrator/index.php?option=com_plugins&view=plugins&filter[folder]=system';
        }

        return null;
    }

    /**
     * Perform the plugin order check.
     *
     * Analyzes the execution order of system plugins to identify potential ordering
     * issues that could affect functionality. System plugins execute in order determined
     * by their 'ordering' value in the database, and incorrect ordering can cause problems.
     *
     * Known ordering requirements:
     * - Session plugin should run early (to initialize session for other plugins)
     * - Redirect plugin should run before SEF plugin (handle redirects before URL rewriting)
     * - Cache plugin should run late (cache final output after all processing)
     *
     * This check verifies these common ordering patterns to prevent functional issues.
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        $issues = [];

        // Get all enabled system plugins ordered by their execution order
        // folder = 'system' filters to system event plugins only
        $query = $database->getQuery(true)
            ->select(['element', 'ordering'])
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('system'))
            ->where($database->quoteName('enabled') . ' = 1')
            ->order($database->quoteName('ordering') . ' ASC');

        $systemPlugins = $database->setQuery($query)
            ->loadObjectList();
        $pluginOrder = [];

        // Build associative array of plugin name => ordering value
        foreach ($systemPlugins as $systemPlugin) {
            $pluginOrder[$systemPlugin->element] = (int) $systemPlugin->ordering;
        }

        // Check: SEF plugin should run after Redirect plugin
        // Redirect handles HTTP redirects, SEF rewrites URLs - redirects must happen first
        if (isset($pluginOrder['sef']) && isset($pluginOrder['redirect']) && $pluginOrder['sef'] < $pluginOrder['redirect']) {
            $issues[] = Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_PLUGIN_ORDER_ISSUE_SEF');
        }

        // Check: Session plugin should run early in the execution order
        // Many other plugins depend on session being initialized
        if (isset($pluginOrder['session'])) {
            $sessionOrder = $pluginOrder['session'];
            $earlyThreshold = 5;

            // Count how many plugins run before session
            $runBefore = 0;

            foreach ($pluginOrder as $element => $order) {
                if ($order < $sessionOrder && $element !== 'session') {
                    $runBefore++;
                }
            }

            if ($runBefore > $earlyThreshold) {
                $issues[] = Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_PLUGIN_ORDER_ISSUE_SESSION');
            }
        }

        // Check: Cache plugin should typically run last
        // Cache should store final output after all other processing
        if (isset($pluginOrder['cache'])) {
            $cacheOrder = $pluginOrder['cache'];
            $maxOrder = max($pluginOrder);

            // If cache is more than 5 positions away from last, it may be too early
            if ($cacheOrder < ($maxOrder - 5)) {
                $issues[] = Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_PLUGIN_ORDER_ISSUE_CACHE');
            }
        }

        if ($issues !== []) {
            return $this->warning(
                Text::sprintf('COM_HEALTHCHECKER_CHECK_EXTENSIONS_PLUGIN_ORDER_WARNING', implode('; ', $issues)),
            );
        }

        return $this->good(
            Text::sprintf('COM_HEALTHCHECKER_CHECK_EXTENSIONS_PLUGIN_ORDER_GOOD', \count($systemPlugins)),
        );
    }
}
