<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function($extensionKey, $table)
{
    $orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['orderBySortingTables']);
    if (
        !empty($orderBySortingTablesArray) &&
        in_array($table, $orderBySortingTablesArray)
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
        $GLOBALS['TCA'][$table]['columns']['sorting'] = 
            [
                'config' => [
                    'type' => 'passthrough',
                    'default' => 0
                ]
            ];
    }
}, 'tt_products', basename(__FILE__, '.php'));
