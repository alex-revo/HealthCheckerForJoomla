<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_healthchecker
 *
 * @copyright   (C) 2026 mySites.guru / Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \MySitesGuru\HealthChecker\Component\Administrator\View\Export\HtmlView $this */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_healthchecker.export', 'com_healthchecker/admin-export.css');
$wa->registerAndUseScript('com_healthchecker.export', 'com_healthchecker/admin-export.js', [], ['defer' => true]);

$token = Session::getFormToken();
$metadataUrl = Route::_('index.php?option=com_healthchecker&task=ajax.metadata&format=json&' . $token . '=1', false);
$jsonUrl = Route::_('index.php?option=com_healthchecker&view=report&format=json&' . $token . '=1', false);
$htmlUrl = Route::_('index.php?option=com_healthchecker&view=report&format=htmlexport&' . $token . '=1', false);
$markdownUrl = Route::_('index.php?option=com_healthchecker&view=report&format=markdown&' . $token . '=1', false);

// Load language strings for JavaScript
Text::script('COM_HEALTHCHECKER_EXPORT_LOADING_CHECKS');
Text::script('COM_HEALTHCHECKER_EXPORT_LOADING_ERROR');
Text::script('COM_HEALTHCHECKER_EXPORT_SELECT_ALL');
Text::script('COM_HEALTHCHECKER_EXPORT_SELECT_NONE');
Text::script('COM_HEALTHCHECKER_ERROR');

?>
<div id="healthchecker-export"
     data-metadata-url="<?php echo htmlspecialchars($metadataUrl); ?>"
     data-export-json-url="<?php echo htmlspecialchars($jsonUrl); ?>"
     data-export-html-url="<?php echo htmlspecialchars($htmlUrl); ?>"
     data-export-markdown-url="<?php echo htmlspecialchars($markdownUrl); ?>"
>
    <form id="exportForm" method="post" target="_blank">
        <input type="hidden" name="<?php echo htmlspecialchars($token); ?>" value="1">

        <div class="row">
            <div class="col-lg-8">
                <!-- Format Selection -->
                <fieldset class="options-form mb-4">
                    <legend><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_FORMAT'); ?></legend>
                    <div class="export-format-cards">
                        <label class="export-format-card active">
                            <input type="radio" name="export_format" value="htmlexport" checked>
                            <span class="export-format-icon"><span class="icon-file"></span></span>
                            <span class="export-format-label"><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_HTML'); ?></span>
                            <span class="export-format-desc"><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_HTML_DESC'); ?></span>
                        </label>
                        <label class="export-format-card">
                            <input type="radio" name="export_format" value="json">
                            <span class="export-format-icon"><span class="icon-code"></span></span>
                            <span class="export-format-label"><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_JSON'); ?></span>
                            <span class="export-format-desc"><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_JSON_DESC'); ?></span>
                        </label>
                        <label class="export-format-card">
                            <input type="radio" name="export_format" value="markdown">
                            <span class="export-format-icon"><span class="icon-file-alt"></span></span>
                            <span class="export-format-label"><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_MARKDOWN'); ?></span>
                            <span class="export-format-desc"><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_MARKDOWN_DESC'); ?></span>
                        </label>
                    </div>
                </fieldset>

                <!-- Status Filter -->
                <fieldset class="options-form mb-4">
                    <legend><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_STATUS_FILTER'); ?></legend>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="export_status" value="all" id="statusAll" checked>
                        <label class="btn btn-outline-primary" for="statusAll"><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_FULL_REPORT'); ?></label>

                        <input type="radio" class="btn-check" name="export_status" value="issues" id="statusIssues">
                        <label class="btn btn-outline-warning" for="statusIssues"><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_ISSUES_ONLY'); ?></label>
                    </div>
                    <div class="form-text mt-2"><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_STATUS_FILTER_DESC'); ?></div>
                </fieldset>

                <!-- Category Filter -->
                <fieldset class="options-form mb-4">
                    <legend>
                        <?php echo Text::_('COM_HEALTHCHECKER_EXPORT_CATEGORIES'); ?>
                        <span class="float-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllCategories">
                                <?php echo Text::_('COM_HEALTHCHECKER_EXPORT_SELECT_ALL'); ?>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectNoCategories">
                                <?php echo Text::_('COM_HEALTHCHECKER_EXPORT_SELECT_NONE'); ?>
                            </button>
                        </span>
                    </legend>
                    <div id="categoryFilters" class="export-loading">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden"><?php echo Text::_('COM_HEALTHCHECKER_LOADING'); ?></span>
                        </div>
                        <?php echo Text::_('COM_HEALTHCHECKER_EXPORT_LOADING_CHECKS'); ?>
                    </div>
                </fieldset>

                <!-- Per-Check Filter -->
                <fieldset class="options-form mb-4">
                    <legend><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_INDIVIDUAL_CHECKS'); ?></legend>
                    <div id="checkFilters" class="export-loading">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden"><?php echo Text::_('COM_HEALTHCHECKER_LOADING'); ?></span>
                        </div>
                        <?php echo Text::_('COM_HEALTHCHECKER_EXPORT_LOADING_CHECKS'); ?>
                    </div>
                </fieldset>
            </div>

            <div class="col-lg-4">
                <!-- Export Action -->
                <div class="card sticky-top" style="top: 80px;">
                    <div class="card-body text-center">
                        <h4 class="card-title mb-3"><?php echo Text::_('COM_HEALTHCHECKER_EXPORT_READY'); ?></h4>
                        <p class="card-text text-muted mb-3" id="exportSummary">
                            <?php echo Text::_('COM_HEALTHCHECKER_EXPORT_SUMMARY_LOADING'); ?>
                        </p>
                        <button type="submit" class="btn btn-success btn-lg w-100" id="exportButton" disabled>
                            <span class="icon-download"></span>
                            <?php echo Text::_('COM_HEALTHCHECKER_EXPORT_DOWNLOAD'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
