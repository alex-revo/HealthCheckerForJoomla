<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Joomla Update Channel Health Check
 *
 * This check verifies whether the Joomla Update component is configured to use
 * the stable release channel or a non-stable channel (testing, next major, custom).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Running a production site on a non-stable update channel means Joomla will offer
 * pre-release versions (alpha, beta, release candidates) as updates. These versions
 * may contain bugs, incomplete features, or security issues that have not been fully
 * tested. Production sites should always use the stable release channel.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The Joomla Update component is configured to use the stable release channel.
 *
 * WARNING: The Joomla Update component is configured to use a non-stable channel
 * (testing, next major, or custom). Change the update channel to stable for
 * production sites at Global Configuration > Joomla Update.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class JoomlaUpdateChannelCheck extends AbstractHealthCheck
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
        return 'extensions.joomla_update_channel';
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
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Extensions/JoomlaUpdateChannelCheck.php';
    }

    public function getActionUrl(?HealthStatus $healthStatus = null): ?string
    {
        if ($healthStatus === HealthStatus::Warning) {
            return '/administrator/index.php?option=com_config&view=component&component=com_joomlaupdate';
        }

        return null;
    }

    /**
     * Perform the Joomla update channel check.
     *
     * Reads the com_joomlaupdate component params from the #__extensions table
     * to determine which update source (channel) is configured.
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        $query = $database->getQuery(true)
            ->select($database->quoteName('params'))
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('com_joomlaupdate'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('component'));

        $database->setQuery($query);
        $paramsJson = $database->loadResult();

        $updateSource = $this->extractUpdateSource($paramsJson);
        $channelLabel = self::CHANNEL_LABELS[$updateSource] ?? $updateSource;

        if ($updateSource !== 'default') {
            return $this->warning(
                Text::sprintf('COM_HEALTHCHECKER_CHECK_EXTENSIONS_JOOMLA_UPDATE_CHANNEL_WARNING', $channelLabel),
            );
        }

        return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_JOOMLA_UPDATE_CHANNEL_GOOD'));
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
}
