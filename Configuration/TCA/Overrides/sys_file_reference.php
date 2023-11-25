<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function($extensionKey, $table)
{
    $languageSubpath = '/Resources/Private/Language/';

    $temporaryColumns = [
        'tx_ttproducts_author' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_file_reference.tx_ttproducts_author',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '256',
                'default' => null
            ]
        ],
        'tx_ttproducts_startpoint' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_file_reference.tx_ttproducts_startpoint',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '256',
                'default' => null
            ]
        ],
        'tx_ttproducts_endpoint' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_file_reference.tx_ttproducts_endpoint',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '256',
                'default' => null
            ]
        ],
        'tx_ttproducts_price_enable' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_file_reference.tx_ttproducts_price_enable',
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'tx_ttproducts_price' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_file_reference.tx_ttproducts_price',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim,double2',
                'max' => '20',
                'default' => 0
            ]
        ],
    ];

    $fieldArray = array_keys($temporaryColumns);
    $fieldList = implode(',', $fieldArray);


    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $temporaryColumns);

    $GLOBALS['TCA'][$table]['palettes']['tt_productsPalette']['showitem'] = $fieldList;
}, 'tt_products', basename(__FILE__, '.php'));
