<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(function ($extensionKey, $table): void {
    $accountField = 'ac_number';

    if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['sepa'])) {

        unset($GLOBALS['TCA'][$table]['columns']['ac_number']);
        $accountField = 'iban';
        $GLOBALS['TCA'][$table]['ctrl']['label'] = $accountField;
        $GLOBALS['TCA'][$table]['ctrl']['default_sortby'] = 'ORDER BY ' . $accountField;
        $GLOBALS['TCA'][$table]['ctrl']['searchFields'] = 'owner_name,' . $accountField;

        if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['bic']) {
            unset($GLOBALS['TCA'][$table]['columns']['bic']);
        }
    } else {
        unset($GLOBALS['TCA'][$table]['columns']['iban']);
    }

    $orderBySortingTablesArray = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['orderBySortingTables']);
    if (
        !empty($orderBySortingTablesArray) &&
        in_array($table, $orderBySortingTablesArray)
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
        $GLOBALS['TCA'][$table]['columns']['sorting'] =
            [
                'config' => [
                    'type' => 'passthrough',
                    'default' => 0,
                ],
            ];
    }
}, 'tt_products', basename(__FILE__, '.php'));
