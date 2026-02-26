<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Image Alt Text Health Check
 *
 * This check scans published articles for images that are missing alt text
 * attributes, which are important for both accessibility and SEO.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Alt text serves two critical purposes: it makes images accessible to visually
 * impaired users who rely on screen readers, and it helps search engines understand
 * image content for better indexing and image search results. Missing alt text
 * is an accessibility violation (WCAG) and a missed SEO opportunity. Images
 * without alt text cannot rank in image search results.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Either no images with missing alt text were found, or only a small
 * number (10 or fewer) were detected. Small numbers are noted as informational
 * since some images (decorative) may intentionally have empty alt attributes.
 *
 * WARNING: More than 10 images across published articles are missing alt text.
 * This indicates a systemic issue that should be addressed. Review articles
 * and add descriptive alt text to all meaningful images.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class AltTextCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'seo.alt_text'
     */
    public function getSlug(): string
    {
        return 'seo.alt_text';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'seo'
     */
    public function getCategory(): string
    {
        return 'seo';
    }

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Seo/AltTextCheck.php';
    }

    /**
     * Perform the alt text health check.
     *
     * Scans all published articles for images missing alt text attributes.
     * Uses regex patterns to detect img tags without alt attributes or with
     * empty alt values. Returns WARNING if more than 10 images are missing
     * alt text to indicate a systemic accessibility/SEO issue.
     *
     * @return HealthCheckResult The check result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Query all published articles to scan for images missing alt text.
        // Only checking published content since unpublished articles don't
        // affect accessibility or SEO until they go live.
        $query = $database->getQuery(true)
            ->select([
                $database->quoteName('id'),
                $database->quoteName('title'),
                $database->quoteName('introtext'),
                $database->quoteName('fulltext'),
            ])
            ->from($database->quoteName('#__content'))
            ->where($database->quoteName('state') . ' = 1'); // Only published articles

        $database->setQuery($query);
        $articles = $database->loadObjectList();

        $articlesWithMissingAlt = 0;
        $totalImagesWithoutAlt = 0;

        foreach ($articles as $article) {
            // Combine intro and full text to check all article content
            $content = $article->introtext . $article->fulltext;

            // Find all img tags using regex pattern that matches any image tag
            if (preg_match_all('/<img\s[^>]*>/i', $content, $matches)) {
                foreach ($matches[0] as $imgTag) {
                    // Check if alt attribute is missing or contains only whitespace.
                    // Pattern looks for alt= followed by non-empty, non-whitespace content.
                    // If this pattern doesn't match, the alt is missing/empty/whitespace-only.
                    // This is critical for screen readers and SEO image indexing.
                    if (in_array(preg_match('/\salt\s*=\s*["\'][^"\'\s]+/i', $imgTag), [0, false], true)) {
                        $totalImagesWithoutAlt++;
                    }
                }
            }

            // Track which articles have at least one problematic image.
            // Uses negative lookahead to match img tags that don't have proper alt attributes.
            if (preg_match('/<img\s(?![^>]*\salt\s*=\s*["\'][^"\']+["\'])[^>]*>/i', $content)) {
                $articlesWithMissingAlt++;
            }
        }

        // More than 10 missing alt texts indicates systemic issue requiring attention.
        // This threshold suggests content editors aren't following accessibility guidelines.
        if ($totalImagesWithoutAlt > 10) {
            return $this->warning(
                Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_SEO_ALT_TEXT_WARNING',
                    $totalImagesWithoutAlt,
                    $articlesWithMissingAlt,
                ),
            );
        }

        // Small number of missing alt texts - could be decorative images or oversight.
        // Still worth addressing but not as urgent as systemic issues.
        if ($totalImagesWithoutAlt > 0) {
            return $this->warning(
                Text::sprintf('COM_HEALTHCHECKER_CHECK_SEO_ALT_TEXT_WARNING_2', $totalImagesWithoutAlt),
            );
        }

        // All images in published articles have alt text - excellent for accessibility and SEO.
        return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_SEO_ALT_TEXT_GOOD'));
    }
}
