<?php

defined('TYPO3') || die('Access denied.');

$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

// ******************************************************************
// order to products table, sys_products_orders_mm_tt_products
// ******************************************************************
$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders_mm_tt_products',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'prependAtCopy' => $languageLglPath . 'prependAtCopy',
        'crdate' => 'crdate',
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/tt_products_cat.gif',
        'hideTable' => true,
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'hidden',
            'config' => [
                'type' => 'check',
            ],
            'default' => 0,
        ],
        'uid_local' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders_mm_tt_products.uid_local',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_products_orders',
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'uid_foreign' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders_mm_tt_products.uid_foreign',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tt_products',
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'sorting' => [
            'config' => [
                'type' => 'passthrough',
                'default' => 0,
            ],
        ],
        'sorting_foreign' => [
            'config' => [
                'type' => 'passthrough',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden,--palette--;;1, uid_local, uid_foreign'],
    ],
];

return $result;
