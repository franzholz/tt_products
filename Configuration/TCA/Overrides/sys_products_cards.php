<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

call_user_func(function ($extensionKey, $table): void {

    $languageSubpath = '/Resources/Private/Language/';
    $languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';
    $version = VersionNumberUtility::getCurrentTypo3Version();
    if (version_compare($version, '12.0.0', '<')) {
        $temporaryColumns = [];
        $temporaryColumns['cc_type'] = [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_cards.cc_type',
            'config' => [
                'type' => 'select',
               'renderType' => 'selectSingle',
               'items' => [
                   ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_cards.cc_type.I.0', '0'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_cards.cc_type.I.1', '1'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_cards.cc_type.I.2', '2'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_cards.cc_type.I.3', '3'],
                    ],
               'size' => 1,
               'maxitems' => 1,
               'default' => null,
            ],
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            $table,
            $temporaryColumns
        );
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
