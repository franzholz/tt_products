<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function($extensionKey, $table)
{
    $extensionKeyStaticTaxes = 'static_info_tables_taxes';
    $imagePath = 'EXT:' . $extensionKeyStaticTaxes . '/Resources/Public/Icons/';
    $languageSubpath = '/Resources/Private/Language/';

    if (
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKeyStaticTaxes)
    ) {

        $GLOBALS['TCA'][$tablename]['ctrl']['iconfile'] = $imagePath . 'Extension.gif';

        unset ($GLOBALS['TCA'][$tablename]['columns']['uid_local']);

        $temporaryColumns = [
            'uid_local' => [
                'label' => 'LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:' . $tablename . '.uid_local',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'static_tax_rates',
                    'maxitems' => 1
                ]
            ],
            'uid_foreign' => [
                'label' => 'LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:' . $tablename . '.uid_foreign',
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
}, 'tt_products', basename(__FILE__, '.php'));
