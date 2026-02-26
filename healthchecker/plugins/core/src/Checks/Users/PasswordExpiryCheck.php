<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Password Expiry Health Check
 *
 * This check identifies active users whose passwords have not been changed in
 * over 365 days. It examines the lastResetTime field which tracks when each
 * user last reset their password.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Stale passwords increase security risk over time. Passwords may be exposed
 * through data breaches on other sites (where users reused passwords), shoulder
 * surfing, phishing, or simply being shared and forgotten. Regular password
 * rotation ensures that any compromised credentials have a limited window of
 * usefulness to attackers. While modern guidance de-emphasizes forced rotation
 * for complexity's sake, annual review of password age helps identify accounts
 * with potentially compromised or weak legacy passwords.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Fewer than 75% of users have passwords older than 365 days, or all users
 *       have recently updated their passwords. The specific count is reported for
 *       awareness. Some older passwords are normal and acceptable. Users who have
 *       never explicitly reset their password but registered within the last 365
 *       days are NOT counted as expired (prevents false positives on fresh installs).
 *
 * WARNING: More than 75% of active users have not changed their password in over
 *          a year. This high percentage suggests a need to implement or encourage
 *          password hygiene. Consider prompting users to update passwords or
 *          implementing a password expiry policy.
 *
 * Note: This check does not produce CRITICAL results as password age alone is not
 * an immediate threat, but a long-term risk factor to address through policy.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class PasswordExpiryCheck extends AbstractHealthCheck
{
    /**
     * Number of days after which a password is considered expired.
     *
     * @var int
     */
    private const PASSWORD_EXPIRY_DAYS = 365;

    /**
     * Returns the unique identifier for this check.
     *
     * @return string The check slug in the format 'users.password_expiry'
     */
    public function getSlug(): string
    {
        return 'users.password_expiry';
    }

    /**
     * Returns the category this check belongs to.
     *
     * @return string The category slug 'users'
     */
    public function getCategory(): string
    {
        return 'users';
    }

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Users/PasswordExpiryCheck.php';
    }

    /**
     * Performs the password expiry health check.
     *
     * Identifies active users whose passwords have not been changed in over 365 days
     * by examining the lastResetTime field. Calculates the percentage of users with
     * expired passwords and returns WARNING if more than 25% have stale passwords.
     *
     * @return HealthCheckResult WARNING if >25% of users have expired passwords, GOOD otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Calculate cutoff date (365 days ago)
        $cutoffDate = (new \DateTime())->modify('-' . self::PASSWORD_EXPIRY_DAYS . ' days')->format('Y-m-d H:i:s');

        // Check users where lastResetTime is older than the cutoff date
        // lastResetTime tracks when password was last reset
        // For users with NULL/zero lastResetTime, use registerDate as fallback
        // This prevents false positives on fresh installs where users have never reset
        // their password but their account is less than 365 days old
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__users'))
            ->where($database->quoteName('block') . ' = 0')
            ->where('(' .
                // Password was explicitly reset more than 365 days ago
                '(' . $database->quoteName('lastResetTime') . ' < ' . $database->quote($cutoffDate) .
                ' AND ' . $database->quoteName('lastResetTime') . ' != ' . $database->quote('0000-00-00 00:00:00') .
                ' AND ' . $database->quoteName('lastResetTime') . ' IS NOT NULL)' .
                ' OR ' .
                // Password was never reset AND account is older than 365 days
                '((' . $database->quoteName('lastResetTime') . ' IS NULL' .
                ' OR ' . $database->quoteName('lastResetTime') . ' = ' . $database->quote('0000-00-00 00:00:00') . ')' .
                ' AND ' . $database->quoteName('registerDate') . ' < ' . $database->quote($cutoffDate) . ')' .
            ')');

        $expiredCount = (int) $database->setQuery($query)
            ->loadResult();

        // Get total active users for percentage calculation
        $totalQuery = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__users'))
            ->where($database->quoteName('block') . ' = 0');

        $totalUsers = (int) $database->setQuery($totalQuery)
            ->loadResult();

        if ($expiredCount > 0 && $totalUsers > 0) {
            // Calculate percentage of users with expired passwords
            $percentage = round(($expiredCount / $totalUsers) * 100);

            // Critical threshold: >75% of users have expired passwords
            if ($percentage > 75) {
                return $this->warning(
                    Text::sprintf(
                        'COM_HEALTHCHECKER_CHECK_USERS_PASSWORD_EXPIRY_WARNING',
                        $expiredCount,
                        $totalUsers,
                        $percentage,
                        self::PASSWORD_EXPIRY_DAYS,
                    ),
                );
            }

            // Warning threshold: >25% of users have expired passwords
            if ($percentage > 25) {
                return $this->warning(
                    Text::sprintf(
                        'COM_HEALTHCHECKER_CHECK_USERS_PASSWORD_EXPIRY_WARNING_2',
                        $expiredCount,
                        $totalUsers,
                        $percentage,
                        self::PASSWORD_EXPIRY_DAYS,
                    ),
                );
            }

            // Some expired passwords but within acceptable threshold
            return $this->good(
                Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_USERS_PASSWORD_EXPIRY_GOOD',
                    $expiredCount,
                    $totalUsers,
                    self::PASSWORD_EXPIRY_DAYS,
                ),
            );
        }

        return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_USERS_PASSWORD_EXPIRY_GOOD_2'));
    }
}
