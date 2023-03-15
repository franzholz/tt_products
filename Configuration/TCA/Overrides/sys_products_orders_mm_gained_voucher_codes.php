<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function () {
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('voucher')) {

        $temporaryColumns = [
            'uid_local' => [
                'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_orders_mm_gained_voucher_codes.uid_local',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'sys_products_orders',
                    'maxitems' => 1,
                    'default' => 0
                ]
            ],
            'uid_foreign' => [
                'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_orders_mm_gained_voucher_codes.uid_foreign',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_voucher_codes',
                    'maxitems' => 1,
                    'default' => 0
                ]
            ],
            'sorting' => [
                'config' => [
                    'type' => 'passthrough',
                    'default' => 0
                ]
            ],
            'sorting_foreign' => [
                'config' => [
                    'type' => 'passthrough',
                    'default' => 0
                ]
            ],
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            'sys_products_orders_mm_gained_voucher_codes',
            $temporaryColumns
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'sys_products_orders_mm_gained_voucher_codes',
            'uid_local,uid_foreign',
            ''
        );
    }
});
