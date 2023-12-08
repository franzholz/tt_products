<?php
defined('TYPO3') || die('Access denied.');

$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

// ******************************************************************
// These are the credit cards data used for orders
// ******************************************************************
$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_cards',
        'label' => 'cc_number',
        'default_sortby' => 'ORDER BY cc_number',
        'tstamp' => 'tstamp',
        'prependAtCopy' => $languageLglPath . 'prependAtCopy',
        'crdate' => 'crdate',
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/' . 'sys_products_cards.gif',
        'searchFields' => 'owner_name,cc_number',
    ],
    'columns' => [
        'endtime' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'endtime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['endtimeYear']),
                    'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
                ]
            ]
        ],
        'cc_number' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_cards.cc_number',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
                'default' => null
            ]
        ],
        'owner_name' => [
            'exclude' => 0,
            'label' => $languageLglPath . 'name',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'default' => null
            ]
        ],
        'cc_type' => [
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
                'default' => null
            ]
        ],
        'cvv2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_cards.cvv2',
            'config' => [
                'type' => 'input',
                'size' => '4',
                'eval' => 'int',
                'max' => '4',
                'default' => 0
            ]
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'cc_number, owner_name, cc_type, cvv2, endtime']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];

return $result;
