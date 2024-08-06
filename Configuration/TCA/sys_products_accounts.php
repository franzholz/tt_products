<?php

defined('TYPO3') || die('Access denied.');

$extensionKey = 'tt_products';
$accountField = 'ac_number';


// ******************************************************************
// These are the bank account data used for orders
// ******************************************************************
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_accounts',
        'label' => $accountField,
        'label_userFunc' => 'tx_ttproducts_table_label->getLabel',
        'default_sortby' => 'ORDER BY ' . $accountField,
        'tstamp' => 'tstamp',
        'prependAtCopy' => $languageLglPath . 'prependAtCopy',
        'crdate' => 'crdate',
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/sys_products_accounts.gif',
        'searchFields' => 'owner_name,' . $accountField,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ]
    ],
    'columns' => [
        'iban' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_accounts.iban',
            'config' => [
                'type' => 'input',
                'size' => '24',
                'max' => '24',
                'eval' => 'required,trim',
                'default' => null,
            ],
        ],
        'ac_number' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_accounts.ac_number',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
                'default' => null,
            ],
        ],
        'owner_name' => [
            'exclude' => 0,
            'label' => $languageLglPath . 'name',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'default' => null,
            ],
        ],
        'bic' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_accounts.bic',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'eval' => 'required,trim',
                'default' => null,
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'iban, ac_number, owner_name, bic'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

return $result;
