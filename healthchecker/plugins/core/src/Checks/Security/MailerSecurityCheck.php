<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Mailer Security Health Check
 *
 * This check verifies that email is configured securely, particularly when using
 * SMTP. It checks whether SMTP connections use encryption (TLS/SSL).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * When SMTP is used without encryption, email credentials are transmitted in plain
 * text, making them vulnerable to interception. Additionally, email content including
 * password reset links and sensitive notifications could be captured by attackers
 * monitoring network traffic.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Email is configured using PHP mail(), sendmail, or SMTP with TLS/SSL
 *       encryption. SMTP connections are protected from eavesdropping.
 *
 * WARNING: SMTP is configured without encryption. Email credentials and content
 *          are transmitted in plain text. Configure TLS or SSL encryption for
 *          your SMTP server in Global Configuration.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class MailerSecurityCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'security.mailer_security';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'security';
    }

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Security/MailerSecurityCheck.php';
    }

    /**
     * Perform the mailer security configuration check.
     *
     * Verifies that email is configured securely by checking:
     * - The configured mailer type (SMTP, mail(), sendmail, or other)
     * - For SMTP, whether encryption (TLS/SSL) is enabled
     *
     * Without SMTP encryption, email credentials and content are transmitted
     * in plain text, making them vulnerable to interception. This is especially
     * critical for password reset emails and sensitive notifications.
     *
     * @return HealthCheckResult Result indicating mailer security status:
     *                           - GOOD: Using PHP mail()/sendmail, or SMTP with TLS/SSL encryption
     *                           - WARNING: SMTP configured without encryption (none/empty)
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get mailer configuration from global Joomla settings
        $mailer = Factory::getApplication()->get('mailer', 'mail');
        $smtpSecurity = Factory::getApplication()->get('smtpsecure', 'none');

        // Check if SMTP is used
        if ($mailer === 'smtp') {
            // Verify SMTP encryption is configured
            if ($smtpSecurity === 'none' || empty($smtpSecurity)) {
                return $this->warning(Text::_('COM_HEALTHCHECKER_CHECK_SECURITY_MAILER_SECURITY_WARNING'));
            }

            // SMTP with encryption is secure
            return $this->good(
                Text::sprintf('COM_HEALTHCHECKER_CHECK_SECURITY_MAILER_SECURITY_GOOD', strtoupper(
                    (string) $smtpSecurity,
                )),
            );
        }

        // PHP mail() function is acceptable (security handled by server)
        if ($mailer === 'mail') {
            return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_SECURITY_MAILER_SECURITY_GOOD_2'));
        }

        // Sendmail is acceptable (security handled by server)
        if ($mailer === 'sendmail') {
            return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_SECURITY_MAILER_SECURITY_GOOD_3'));
        }

        // Other mailer types (fallback)
        return $this->good(Text::sprintf('COM_HEALTHCHECKER_CHECK_SECURITY_MAILER_SECURITY_GOOD_4', $mailer));
    }
}
