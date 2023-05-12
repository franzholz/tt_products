<?php
defined('TYPO3') || die('Access denied.');

// ******************************************************************
// order to voucher codes table, sys_products_orders_mm_gained_voucher_codes
// ******************************************************************

$languageSubpath = '/Resources/Private/Language/';

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:sys_products_orders_mm_gained_voucher_codes',
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
    'types' => [
        '0' => [
            'showitem' => ''
        ]
    ],
    'columns' => []
];


return $result;

