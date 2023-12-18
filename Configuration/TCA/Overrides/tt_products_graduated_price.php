<?php

defined('TYPO3') || die('Access denied.');

call_user_func(function (): void {
    $table = 'tt_products_graduated_price';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
});
