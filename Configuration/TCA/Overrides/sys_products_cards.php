<?php
defined('TYPO3_MODE') || die('Access denied.');

$table = 'sys_products_cards';

if (
    version_compare(TYPO3_version, '8.7.0', '<')
) {
    unset($GLOBALS['TCA'][$table]['columns']['endtime']['config']['renderType']);
    $GLOBALS['TCA'][$table]['columns']['endtime']['config']['max'] = '20';
}


$orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
if (
    !empty($orderBySortingTablesArray) &&
    in_array($table, $orderBySortingTablesArray)
) {
    $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
}
