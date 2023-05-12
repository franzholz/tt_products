<?php
defined('TYPO3') || die('Access denied.');

// ******************************************************************
// product to related products table, tt_products_related_products_products_mm
// ******************************************************************

$languageSubpath = '/Resources/Private/Language/';

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products_related_products_products_mm',
        'label' => 'uid_local',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
        'crdate' => 'crdate',
        'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_relations.gif',
        'hideTable' => true,
    ],
    'columns' => [
        'uid_local' => [
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products_related_products_products_mm.uid_local',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tt_products',
                'maxitems' => 1,
                'default' => 0
            ]
        ],
        'uid_foreign' => [
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products_related_products_products_mm.uid_foreign',
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
        '0' => [
            'showitem' => ''
        ]
    ]
];

return $result;

