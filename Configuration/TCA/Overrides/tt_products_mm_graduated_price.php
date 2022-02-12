<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    $table = 'tt_products_mm_graduated_price';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
});

