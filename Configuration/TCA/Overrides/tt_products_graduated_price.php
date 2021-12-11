<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    $table = 'tt_products_graduated_price';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
    
    if (version_compare(TYPO3_version, '10.4.0', '<')) {
        $GLOBALS['TCA'][$table]['columns']['fe_group']['config']['enableMultiSelectFilterTextfield'] = true;
    }
});
