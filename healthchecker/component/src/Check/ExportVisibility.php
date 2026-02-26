<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace MySitesGuru\HealthChecker\Component\Administrator\Check;

\defined('_JEXEC') || die;

/**
 * Export visibility modes for health checks.
 *
 * Controls whether a health check result is included in exported reports
 * (HTML export, JSON export). This is independent of whether the check
 * is enabled — a check can run and appear in the admin UI but be excluded
 * from exports.
 *
 * The visibility can be set in two ways:
 * 1. Override getExportVisibility() in the check class (developer default)
 * 2. Configure via plugin XML params (admin override)
 *
 * @since 3.4.0
 */
enum ExportVisibility: string
{
    /**
     * Always include this check in exports regardless of status.
     * This is the default for all checks.
     */
    case Always = 'always';

    /**
     * Only include this check in exports when the result is Warning or Critical.
     * Good results are excluded from exports.
     */
    case IssuesOnly = 'issues';

    /**
     * Never include this check in exports regardless of status.
     * The check still runs and appears in the admin UI.
     */
    case Never = 'never';
}
