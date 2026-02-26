<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Third-Party Service Connectivity Health Check (Example)
 *
 * EXAMPLE TEMPLATE FOR THIRD-PARTY DEVELOPERS
 *
 * This example check demonstrates how to create a health check that:
 * - Uses a custom category registered by this plugin ('thirdparty')
 * - Doesn't require database access (pure logic/HTTP check)
 * - Checks external service connectivity using HTTP client
 * - Returns multiple status types based on performance and availability
 *
 * DEVELOPER NOTES:
 * - This is a reference implementation for third-party plugin developers
 * - Copy this pattern when creating checks that call external APIs/services
 * - Not all checks need database access - this demonstrates a standalone check
 * - Uses the injectable HTTP client for testability
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Many Joomla sites depend on external services for updates, extensions,
 * and integrations. If your server cannot reach these services, critical
 * functionality may fail silently. This check verifies that outbound HTTP
 * connections work properly and respond in a timely manner.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The external service (Joomla API) is reachable and responding
 *       within an acceptable time frame (under 3 seconds).
 *
 * WARNING: The service is reachable but responding slowly (over 3 seconds).
 *          This may indicate network issues, server overload, or firewall
 *          configuration problems that could affect update checks.
 *
 * CRITICAL: The service cannot be reached at all. Check your server's
 *           internet connectivity, firewall rules, and whether outbound
 *           HTTP requests are allowed by your hosting provider.
 *
 * @subpackage  HealthChecker.Example
 * @since       1.0.0
 */

namespace MySitesGuru\HealthChecker\Plugin\Example\Checks;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

/**
 * Example health check demonstrating external service monitoring.
 *
 * This check shows how to:
 * - Use custom categories (defined in your plugin)
 * - Check external HTTP services
 * - Handle HTTP failures gracefully
 * - Return different statuses based on performance metrics
 *
 * @since  1.0.0
 */
final class ThirdPartyServiceCheck extends AbstractHealthCheck
{
    /**
     * HTTP request timeout in seconds.
     */
    private const HTTP_TIMEOUT_SECONDS = 10;

    /**
     * Slow response threshold in seconds.
     */
    private const SLOW_THRESHOLD_SECONDS = 3.0;

    /**
     * The service URL to check.
     */
    private const SERVICE_URL = 'https://api.joomla.org/';

    /**
     * Returns the unique identifier for this health check.
     *
     * Format: {provider_slug}.{check_name}
     * - Must be lowercase
     * - Use underscores for spaces
     * - Must be unique across all plugins
     *
     * @return string The check slug in format 'provider.check_name'
     *
     * @since  1.0.0
     */
    public function getSlug(): string
    {
        return 'example.thirdparty_service';
    }

    /**
     * Returns the category this check belongs to.
     *
     * This check uses a CUSTOM category ('thirdparty') that is registered
     * by the example plugin in its onCollectCategories() event handler.
     *
     * DEVELOPER NOTES:
     * - Custom categories must be registered before checks are collected
     * - See ExamplePlugin::onCollectCategories() for registration example
     * - Custom categories appear in the UI alongside core categories
     *
     * @return string The category slug 'thirdparty'
     *
     * @since  1.0.0
     */
    public function getCategory(): string
    {
        // Using a custom category registered by this plugin
        return 'thirdparty';
    }

    /**
     * Returns the provider slug that owns this check.
     *
     * @return string The provider slug 'example'
     *
     * @since  1.0.0
     */
    public function getProvider(): string
    {
        return 'example';
    }

    /**
     * Performs the actual health check logic.
     *
     * This check demonstrates:
     * - Testing external HTTP service availability
     * - Measuring response time for performance warnings
     * - Using the injectable HTTP client for testability
     * - Using multiple return status types
     *
     * PERFORMANCE NOTES:
     * - This check makes a real HTTP request (can be slow)
     * - Uses HEAD request to minimize data transfer
     * - Has timeout protection (10 seconds max)
     * - Consider caching results if this is too slow
     *
     * @return HealthCheckResult Result object with status and description
     *
     * @since  1.0.0
     */
    protected function performCheck(): HealthCheckResult
    {
        try {
            $startTime = microtime(true);
            $http = $this->getHttpClient();
            $response = $http->head(self::SERVICE_URL, [], self::HTTP_TIMEOUT_SECONDS);
            $duration = microtime(true) - $startTime;

            // Check for HTTP errors
            if ($response->code === 0 || $response->code >= 400) {
                return $this->critical(
                    Text::_('PLG_HEALTHCHECKER_EXAMPLE_CHECK_EXAMPLE_THIRDPARTY_SERVICE_CRITICAL'),
                );
            }

            // Check for slow response
            if ($duration > self::SLOW_THRESHOLD_SECONDS) {
                return $this->warning(
                    Text::_('PLG_HEALTHCHECKER_EXAMPLE_CHECK_EXAMPLE_THIRDPARTY_SERVICE_WARNING'),
                );
            }

            // Everything is good
            return $this->good(Text::_('PLG_HEALTHCHECKER_EXAMPLE_CHECK_EXAMPLE_THIRDPARTY_SERVICE_GOOD'));
        } catch (\Exception) {
            return $this->critical(Text::_('PLG_HEALTHCHECKER_EXAMPLE_CHECK_EXAMPLE_THIRDPARTY_SERVICE_CRITICAL'));
        }
    }
}
