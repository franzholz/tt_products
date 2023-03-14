<?php
defined('TYPO3') || die('Access denied.');

$accountField = 'ac_number';

if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['sepa']) {
    $accountField = 'iban';
}

// ******************************************************************
// These are the bank account data used for orders
// ******************************************************************
$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_accounts',
        'label' => $accountField,
        'label_userFunc' => 'tx_ttproducts_table_label->getLabel',
        'default_sortby' => 'ORDER BY ' . $accountField,
        'tstamp' => 'tstamp',
        'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
        'crdate' => 'crdate',
        'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'sys_products_accounts.gif',
        'searchFields' => 'owner_name,' . $accountField,
    ],
    'columns' => [
        'iban' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_accounts.iban',
            'config' => [
                'type' => 'input',
                'size' => '24',
                'max' => '24',
                'eval' => 'required,trim',
                'default' => ''
            ]
        ],
        'ac_number' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_accounts.ac_number',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
                'default' => ''
            ]
        ],
        'owner_name' => [
            'exclude' => 0,
            'label' => DIV2007_LANGUAGE_LGL . 'name',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'default' => ''
            ]
        ],
        'bic' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_accounts.bic',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'required,trim',
                'default' => ''
            ]
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'iban, ac_number, owner_name, bic']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];



return $result;

