<?php
defined('TYPO3_MODE') || die('Access denied.');

$table = 'sys_file_reference';

$temporaryColumns = array (
    'tx_ttproducts_author' => array (
        'exclude' => 0,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_file_reference.tx_ttproducts_author',
        'config' => array (
            'type' => 'input',
            'size' => '40',
            'max' => '256',
            'default' => ''
        )
    ),
    'tx_ttproducts_startpoint' => array (
        'exclude' => 0,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_file_reference.tx_ttproducts_startpoint',
        'config' => array (
            'type' => 'input',
            'size' => '40',
            'max' => '256',
            'default' => ''
        )
    ),
    'tx_ttproducts_endpoint' => array (
        'exclude' => 0,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_file_reference.tx_ttproducts_endpoint',
        'config' => array (
            'type' => 'input',
            'size' => '40',
            'max' => '256',
            'default' => ''
        )
    ),
    'tx_ttproducts_price_enable' => array (
        'exclude' => 1,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_file_reference.tx_ttproducts_price_enable',
        'config' => array (
            'type' => 'check',
            'default' => 0
        )
    ),
    'tx_ttproducts_price' => array (
        'exclude' => 1,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_file_reference.tx_ttproducts_price',
        'config' => array (
            'type' => 'input',
            'size' => '20',
            'eval' => 'trim,double2',
            'max' => '20',
            'default' => 0
        )
    ),
);

$fieldArray = array_keys($temporaryColumns);
$fieldList = implode(',', $fieldArray);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $temporaryColumns);

$GLOBALS['TCA'][$table]['palettes']['tt_productsPalette']['showitem'] = $fieldList;

