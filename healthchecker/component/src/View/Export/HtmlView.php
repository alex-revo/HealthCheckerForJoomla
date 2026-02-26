<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\View\Export;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') || die;

/**
 * HTML View for Export Report Page
 *
 * Displays the export configuration page where users can select format,
 * filter by status, categories, and individual checks before downloading
 * the health check report.
 *
 * @since 4.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Display the export configuration page
     *
     * @param   string|null  $tpl  The name of the template file to parse (optional)
     *
     * @since   4.0.0
     */
    public function display($tpl = null): void
    {
        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page toolbar
     *
     * @since   4.0.0
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_HEALTHCHECKER_EXPORT_REPORT'), 'icon-download');

        $toolbar = Toolbar::getInstance('toolbar');

        $reportUrl = Route::_('index.php?option=com_healthchecker&view=report', false);
        $toolbar->linkButton('back')
            ->text('JTOOLBAR_BACK')
            ->url($reportUrl)
            ->icon('icon-arrow-left');

        $user = Factory::getApplication()->getIdentity();

        if ($user instanceof \Joomla\CMS\User\User && $user->authorise('core.admin', 'com_healthchecker')) {
            ToolbarHelper::preferences('com_healthchecker');
        }
    }
}
