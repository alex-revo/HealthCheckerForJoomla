<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

defined('_JEXEC') || die;

use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            InstallerScriptInterface::class,
            new class implements InstallerScriptInterface {
                public function install(\Joomla\CMS\Installer\InstallerAdapter $installerAdapter): bool
                {
                    return true;
                }

                public function update(\Joomla\CMS\Installer\InstallerAdapter $installerAdapter): bool
                {
                    return true;
                }

                public function uninstall(\Joomla\CMS\Installer\InstallerAdapter $installerAdapter): bool
                {
                    return true;
                }

                public function preflight(string $type, \Joomla\CMS\Installer\InstallerAdapter $installerAdapter): bool
                {
                    return true;
                }

                public function postflight(string $type, \Joomla\CMS\Installer\InstallerAdapter $installerAdapter): bool
                {
                    if ($type === 'update') {
                        $this->removeObsoleteFiles();
                    }

                    return true;
                }

                /**
                 * Remove files from previous versions that no longer exist in
                 * the current release. Joomla's upgrade installer copies new
                 * files but does not delete removed ones.
                 */
                private function removeObsoleteFiles(): void
                {
                    $pluginDir = JPATH_PLUGINS . '/healthchecker/core/';

                    $files = [
                        // Removed in 3.0.38: replaced by akeeba_backup.last_backup
                        $pluginDir . 'src/Checks/Database/BackupAgeCheck.php',
                        // Removed in 3.0.36: phantom check for non-existent plg_user_userlog
                        $pluginDir . 'src/Checks/Security/UserActionsLogCheck.php',
                    ];

                    foreach ($files as $file) {
                        if (file_exists($file)) {
                            @unlink($file);
                        }
                    }
                }
            },
        );
    }
};
