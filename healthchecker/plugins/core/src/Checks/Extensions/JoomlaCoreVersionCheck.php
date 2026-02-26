<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Joomla Core Version Health Check
 *
 * This check compares the currently installed Joomla version against the latest
 * available version from Joomla's update servers to identify if an update is available.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Keeping Joomla core up to date is critical for security, performance, and compatibility.
 * New versions often contain security patches that protect against known vulnerabilities,
 * bug fixes that improve stability, and new features that enhance functionality.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The installed Joomla version is the latest available version. No update is needed.
 * When on a non-stable channel and only a pre-release is available, this also returns GOOD
 * since the channel concern is handled by JoomlaUpdateChannelCheck.
 *
 * WARNING: A newer stable version of Joomla is available. You should plan to update soon,
 * especially if the new version includes security fixes.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class JoomlaCoreVersionCheck extends AbstractHealthCheck
{
    /**
     * Human-readable labels for each update source value.
     *
     * @var array<string, string>
     */
    private const CHANNEL_LABELS = [
        'default' => 'Stable',
        'testing' => 'Testing',
        'next' => 'Next Major',
        'custom' => 'Custom',
    ];

    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in the format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.joomla_core_version';
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
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Extensions/JoomlaCoreVersionCheck.php';
    }

    public function getActionUrl(?HealthStatus $healthStatus = null): ?string
    {
        if ($healthStatus === HealthStatus::Warning) {
            return '/administrator/index.php?option=com_joomlaupdate';
        }

        return null;
    }

    /**
     * Perform the Joomla core version check.
     *
     * Compares the currently installed Joomla version against the latest available
     * version from Joomla's update servers. The update information is retrieved from
     * the #__updates table, which is populated by Joomla's update checking system.
     *
     * When the site is on a non-stable update channel and the available version is
     * a pre-release, this returns GOOD since the channel concern is handled separately
     * by JoomlaUpdateChannelCheck.
     *
     * Extension ID 700 is always Joomla core itself in the updates table.
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get currently installed Joomla version
        $version = new Version();
        $currentVersion = $version->getShortVersion();

        $database = $this->requireDatabase();

        // Query #__updates table for latest Joomla version
        // Extension ID 700 is always Joomla core
        $query = $database->getQuery(true)
            ->select($database->quoteName('version'))
            ->from($database->quoteName('#__updates'))
            ->where($database->quoteName('extension_id') . ' = 700');

        $database->setQuery($query);
        $latestVersion = $database->loadResult();

        // Query com_joomlaupdate params to detect configured update channel
        $query = $database->getQuery(true)
            ->select($database->quoteName('params'))
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('com_joomlaupdate'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('component'));

        $database->setQuery($query);
        $paramsJson = $database->loadResult();

        $updateSource = $this->extractUpdateSource($paramsJson);

        // Compare versions - if current is older than latest, check context
        if ($latestVersion && version_compare($currentVersion, (string) $latestVersion, '<')) {
            // On a non-stable channel with a pre-release available: return GOOD
            // The channel concern is handled by JoomlaUpdateChannelCheck
            if ($updateSource !== 'default' && $this->isPreRelease((string) $latestVersion)) {
                $channelLabel = self::CHANNEL_LABELS[$updateSource] ?? $updateSource;

                return $this->good(
                    Text::sprintf(
                        'COM_HEALTHCHECKER_CHECK_EXTENSIONS_JOOMLA_CORE_VERSION_GOOD_CHANNEL',
                        $currentVersion,
                        $channelLabel,
                    ),
                );
            }

            return $this->warning(
                Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_EXTENSIONS_JOOMLA_CORE_VERSION_WARNING',
                    $currentVersion,
                    $latestVersion,
                ),
            );
        }

        return $this->good(
            Text::sprintf('COM_HEALTHCHECKER_CHECK_EXTENSIONS_JOOMLA_CORE_VERSION_GOOD', $currentVersion),
        );
    }

    /**
     * Extract the updatesource value from the component params JSON.
     *
     * @param string|null $paramsJson The raw JSON params string from #__extensions
     *
     * @return string The update source value, defaults to 'default' if not set
     */
    private function extractUpdateSource(?string $paramsJson): string
    {
        if ($paramsJson === null || $paramsJson === '') {
            return 'default';
        }

        /** @var mixed $params */
        $params = json_decode($paramsJson, true);

        if (! \is_array($params) || ! isset($params['updatesource']) || $params['updatesource'] === '') {
            return 'default';
        }

        return (string) $params['updatesource'];
    }

    /**
     * Check if a version string represents a pre-release version.
     *
     * Detects pre-release indicators like alpha, beta, rc, dev in the version string.
     *
     * @param string $version The version string to check
     *
     * @return bool True if the version is a pre-release
     */
    private function isPreRelease(string $version): bool
    {
        return (bool) preg_match('/[-_.](alpha|beta|rc|dev|a|b)\d*/i', $version);
    }
}
