<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Module Position Health Check
 *
 * This check identifies published modules assigned to positions that do not exist
 * in the active template's templateDetails.xml manifest file.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Modules assigned to non-existent template positions will not be displayed,
 * even though they are published. This commonly occurs when switching templates
 * or after template updates that rename positions. These "orphaned" modules
 * represent content that visitors cannot see and may need reassignment.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All published modules are assigned to valid template positions
 * defined in the active template.
 *
 * WARNING: One or more published modules are assigned to positions not defined
 * in the active template. These modules will not be displayed until moved to
 * a valid position.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class ModulePositionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in the format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.module_positions';
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
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Extensions/ModulePositionCheck.php';
    }

    public function getActionUrl(?HealthStatus $healthStatus = null): ?string
    {
        if ($healthStatus === HealthStatus::Warning) {
            return '/administrator/index.php?option=com_modules&view=modules&client_id=0';
        }

        return null;
    }

    /**
     * Perform the module position check.
     *
     * Identifies published modules assigned to positions that don't exist in the
     * active template's manifest (templateDetails.xml). This commonly occurs when:
     * - Switching templates (old positions may not exist in new template)
     * - Template updates that rename or remove positions
     * - Manual position name typos in module configuration
     *
     * Template position flow:
     * 1. Get active site template from #__template_styles (client_id = 0, home = 1)
     * 2. Parse templateDetails.xml to extract defined positions
     * 3. Compare module positions against template positions
     * 4. Report modules assigned to non-existent positions
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Get the active site template (client_id = 0 for frontend, home = 1 for default)
        $query = $database->getQuery(true)
            ->select(['template', 'params'])
            ->from($database->quoteName('#__template_styles'))
            ->where($database->quoteName('client_id') . ' = 0')
            ->where($database->quoteName('home') . ' = 1');

        $activeTemplate = $database->setQuery($query)
            ->loadObject();

        if ($activeTemplate === null) {
            return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_MODULE_POSITIONS_WARNING'));
        }

        // Get template positions from templateDetails.xml manifest
        $templatePath = JPATH_SITE . '/templates/' . $activeTemplate->template;
        $xmlPath = $templatePath . '/templateDetails.xml';

        if (! file_exists($xmlPath)) {
            return $this->warning(
                Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_EXTENSIONS_MODULE_POSITIONS_WARNING_2',
                    $activeTemplate->template,
                ),
            );
        }

        $xml = simplexml_load_file($xmlPath);

        // Some templates may not define positions in the manifest
        if (! $xml || (! property_exists($xml, 'positions') || $xml->positions === null)) {
            return $this->good(
                Text::sprintf('COM_HEALTHCHECKER_CHECK_EXTENSIONS_MODULE_POSITIONS_GOOD', $activeTemplate->template),
            );
        }

        // Extract position names from XML into array
        $templatePositions = [];

        foreach ($xml->positions->position as $position) {
            $templatePositions[] = (string) $position;
        }

        // Get all published site modules with assigned positions
        // client_id = 0 restricts to frontend modules
        // Empty position names are excluded (custom positioning)
        $query = $database->getQuery(true)
            ->select(['id', 'title', 'position'])
            ->from($database->quoteName('#__modules'))
            ->where($database->quoteName('client_id') . ' = 0')
            ->where($database->quoteName('published') . ' = 1')
            ->where($database->quoteName('position') . ' != ' . $database->quote(''));

        $modules = $database->setQuery($query)
            ->loadObjectList();

        // Check each module position against template positions
        $orphanedModules = [];

        foreach ($modules as $module) {
            if (! \in_array($module->position, $templatePositions, true)) {
                $orphanedModules[] = $module->title . ' (' . $module->position . ')';
            }
        }

        $orphanedCount = \count($orphanedModules);

        if ($orphanedCount > 0) {
            $list = '<ul><li>' . implode(
                '</li><li>',
                array_map(htmlspecialchars(...), $orphanedModules),
            ) . '</li></ul>';

            return $this->warning(
                Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_EXTENSIONS_MODULE_POSITIONS_WARNING_3',
                    $orphanedCount,
                    htmlspecialchars($activeTemplate->template),
                    $list,
                ),
            );
        }

        return $this->good(
            Text::sprintf(
                'COM_HEALTHCHECKER_CHECK_EXTENSIONS_MODULE_POSITIONS_GOOD_2',
                \count($modules),
                $activeTemplate->template,
            ),
        );
    }
}
