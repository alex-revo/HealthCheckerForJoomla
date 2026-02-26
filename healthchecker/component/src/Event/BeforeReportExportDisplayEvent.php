<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Event;

use Joomla\Event\Event;

\defined('_JEXEC') || die;

/**
 * Event triggered before the health check HTML export report is displayed
 *
 * This event allows plugins to inject content (like promotional banners) into the
 * HTML export report before it's rendered. Plugins can add HTML content that will
 * be displayed between the summary cards and the check results in the exported file.
 *
 * Unlike BeforeReportDisplayEvent (which targets the admin UI), this event targets
 * the standalone HTML export that users can download and share. Banners injected here
 * should be self-contained since the export has no external CSS or JavaScript dependencies.
 *
 * @since 3.4.0
 */
final class BeforeReportExportDisplayEvent extends Event
{
    /**
     * HTML content to inject before the report export results
     *
     * @var string[]
     * @since 3.4.0
     */
    private array $htmlContent = [];

    /**
     * Constructs the BeforeReportExportDisplayEvent.
     *
     * Initializes the event with the name from the HealthCheckerEvents enum
     * to ensure consistency across the codebase.
     */
    public function __construct()
    {
        parent::__construct(HealthCheckerEvents::BEFORE_REPORT_EXPORT_DISPLAY->value);
    }

    /**
     * Add HTML content to be displayed before the export report results
     *
     * Content should be self-contained with inline styles since the HTML export
     * is a standalone document without external stylesheets.
     *
     * @param   string  $html  HTML content to inject
     *
     * @since   3.4.0
     */
    public function addHtmlContent(string $html): void
    {
        $this->htmlContent[] = $html;
    }

    /**
     * Get all HTML content to display
     *
     * @return  string  Combined HTML content from all plugins
     *
     * @since   3.4.0
     */
    public function getHtmlContent(): string
    {
        return implode("\n", $this->htmlContent);
    }
}
