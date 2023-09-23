<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function () {
    $table = 'tt_products_products_mm_articles';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);

});

