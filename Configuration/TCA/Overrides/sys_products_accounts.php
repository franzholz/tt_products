<?php

defined('TYPO3') || die('Access denied.');

call_user_func(function ($extensionKey, $table): void {
    if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['sepa']) {
        unset($GLOBALS['TCA'][$table]['columns']['ac_number']);
        if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['bic']) {
            unset($GLOBALS['TCA'][$table]['columns']['bic']);
        }
    } else {
        unset($GLOBALS['TCA'][$table]['columns']['iban']);
    }

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
                    'default' => 0,
                ],
            ];
    }
}, 'tt_products', basename(__FILE__, '.php'));
