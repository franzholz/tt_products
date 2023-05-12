<?php
defined('TYPO3') || die('Access denied.');

$languageSubpath = '/Resources/Private/Language/';

// ******************************************************************
// order to products table, sys_products_orders_mm_tt_products
// ******************************************************************
$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:sys_products_orders_mm_tt_products',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
        'crdate' => 'crdate',
        'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_cat.gif',
        'hideTable' => true,
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'hidden',
            'config' => [
                'type' => 'check'
            ],
            'default' => 0
        ],
        'uid_local' => [
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:sys_products_orders_mm_tt_products.uid_local',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_products_orders',
                'maxitems' => 1,
                'default' => 0
            ]
        ],
        'uid_foreign' => [
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:sys_products_orders_mm_tt_products.uid_foreign',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tt_products',
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
    ],
    'types' => [
        '0' => ['showitem' => 'hidden,--palette--;;1, uid_local, uid_foreign']
    ]
];

return $result;

