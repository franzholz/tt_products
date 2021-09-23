<?php
defined('TYPO3_MODE') || die('Access denied.');

$table = 'sys_products_accounts';

if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['sepa']) {
    unset($GLOBALS['TCA'][$table]['columns']['ac_number']);
    if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['bic']) {
        unset($GLOBALS['TCA'][$table]['columns']['bic']);
    }
} else {
    unset($GLOBALS['TCA'][$table]['columns']['iban']);
}


$orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
if (
    !empty($orderBySortingTablesArray) &&
    in_array($table, $orderBySortingTablesArray)
) {
    $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
}

