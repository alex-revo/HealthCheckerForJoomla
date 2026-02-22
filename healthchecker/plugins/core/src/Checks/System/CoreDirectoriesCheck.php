<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Core Directories Health Check
 *
 * This check verifies that all core Joomla root-level directories exist. These are
 * the directories that Joomla's System Information page (SysinfoModel::getDirectory())
 * expects to be present. After migrations (e.g., Akeeba Backup restores), directories
 * like /images can be missing, causing errors on the System Information page.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Missing core directories can cause fatal errors in Joomla's admin panel, break
 * media management, prevent extension installation, and cause template rendering
 * failures. These issues are difficult to diagnose without checking each directory.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All core Joomla directories exist on the filesystem.
 *
 * WARNING: This check does not produce warning results.
 *
 * CRITICAL: One or more core directories are missing. The missing directories
 * are listed in the result. Create the missing directories to resolve.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class CoreDirectoriesCheck extends AbstractHealthCheck
{
    /**
     * Core root-level directories from Joomla's SysinfoModel::getDirectory().
     *
     * @var list<string>
     */
    private const CORE_DIRECTORIES = [
        'administrator/components',
        'administrator/language',
        'administrator/manifests',
        'administrator/modules',
        'administrator/templates',
        'components',
        'images',
        'language',
        'libraries',
        'media',
        'media/cache',
        'modules',
        'plugins',
        'templates',
    ];

    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.core_directories'
     */
    public function getSlug(): string
    {
        return 'system.core_directories';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/System/CoreDirectoriesCheck.php';
    }

    /**
     * Perform the core directories health check.
     *
     * Validates that all core Joomla root-level directories exist on the filesystem.
     * These directories are required by Joomla's System Information page and various
     * core operations.
     *
     * @return HealthCheckResult Critical if any directories missing, Good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $missing = [];

        foreach (self::CORE_DIRECTORIES as $directory) {
            if (! is_dir(JPATH_ROOT . '/' . $directory)) {
                $missing[] = $directory;
            }
        }

        if ($missing !== []) {
            return $this->critical(sprintf(
                'Missing core %s: %s',
                \count($missing) === 1 ? 'directory' : 'directories',
                implode(', ', $missing),
            ));
        }

        return $this->good('All core directories exist.');
    }
}
