<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Joomla Update Minimum Stability Health Check
 *
 * This check verifies whether the Joomla Update component is configured to only
 * offer stable releases, or whether it will also offer pre-release versions.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The minimum stability setting controls which types of releases Joomla will offer
 * as available updates. If set below "Stable", Joomla may offer alpha, beta, or
 * release candidate versions that could contain bugs, incomplete features, or
 * unresolved security issues. Production sites should always use the "Stable"
 * minimum stability setting.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The minimum stability is set to "Stable" (value 4), meaning only
 * fully tested, production-ready releases will be offered.
 *
 * WARNING: The minimum stability is set below "Stable" (Dev, Alpha, Beta, or RC).
 * Pre-release versions may be offered as updates. Change to "Stable" at
 * Global Configuration > Joomla Update for production sites.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class JoomlaUpdateStabilityCheck extends AbstractHealthCheck
{
    private const STABLE = '4';

    private const STABILITY_LABELS = [
        '0' => 'Development',
        '1' => 'Alpha',
        '2' => 'Beta',
        '3' => 'Release Candidate',
        '4' => 'Stable',
    ];

    public function getSlug(): string
    {
        return 'extensions.joomla_update_stability';
    }

    public function getCategory(): string
    {
        return 'extensions';
    }

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Extensions/JoomlaUpdateStabilityCheck.php';
    }

    public function getActionUrl(?HealthStatus $healthStatus = null): ?string
    {
        if ($healthStatus === HealthStatus::Warning) {
            return '/administrator/index.php?option=com_config&view=component&component=com_joomlaupdate';
        }

        return null;
    }

    /**
     * Perform the Joomla update minimum stability check.
     *
     * Reads the com_joomlaupdate component params from the #__extensions table
     * to determine the configured minimum stability level.
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

        $stability = $this->extractMinimumStability($paramsJson);
        $stabilityLabel = self::STABILITY_LABELS[$stability] ?? $stability;

        if ($stability !== self::STABLE) {
            return $this->warning(
                Text::sprintf('COM_HEALTHCHECKER_CHECK_EXTENSIONS_JOOMLA_UPDATE_STABILITY_WARNING', $stabilityLabel),
            );
        }

        return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_JOOMLA_UPDATE_STABILITY_GOOD'));
    }

    /**
     * Extract the minimum_stability value from the component params JSON.
     *
     * @param string|null $paramsJson The raw JSON params string from #__extensions
     *
     * @return string The minimum stability value, defaults to '4' (Stable) if not set
     */
    private function extractMinimumStability(?string $paramsJson): string
    {
        if ($paramsJson === null || $paramsJson === '') {
            return self::STABLE;
        }

        /** @var mixed $params */
        $params = json_decode($paramsJson, true);

        if (! \is_array($params) || ! isset($params['minimum_stability']) || $params['minimum_stability'] === '') {
            return self::STABLE;
        }

        return (string) $params['minimum_stability'];
    }
}
