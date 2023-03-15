<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function () {


    $tablename = 'static_tax_rates_mm_categories';

    if (
        defined('STATIC_INFO_TABLES_TAXES_EXT') &&
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(STATIC_INFO_TABLES_TAXES_EXT)
    ) {

        $GLOBALS['TCA'][$tablename]['ctrl']['iconfile'] = PATH_BE_STATICINFOTABLESTAXES_REL . 'ext_icon.gif';

        unset ($GLOBALS['TCA'][$tablename]['columns']['uid_local']);

        $temporaryColumns = [
            'uid_local' => [
                'label' => 'LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xml:' . $tablename . '.uid_local',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'static_tax_rates',
                    'maxitems' => 1
                ]
            ],
            'uid_foreign' => [
                'label' => 'LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:' . $tablename . '.uid_foreign',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'static_tax_categories',
                    'maxitems' => 5
                ]
            ],
            'sorting' => [
                'config' => [
                    'type' => 'passthrough',
                ]
            ],
            'sorting_foreign' => [
                'config' => [
                    'type' => 'passthrough',
                ]
            ],
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            $tablename,
            $temporaryColumns
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            $tablename,
            'uid_local,uid_foreign',
            ''
        );
    }
});
