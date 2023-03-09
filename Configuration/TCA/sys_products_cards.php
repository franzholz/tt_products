<?php
defined('TYPO3') || die('Access denied.');

// ******************************************************************
// These are the credit cards data used for orders
// ******************************************************************
$result = array (
    'ctrl' => array (
        'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_cards',
        'label' => 'cc_number',
        'default_sortby' => 'ORDER BY cc_number',
        'tstamp' => 'tstamp',
        'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
        'crdate' => 'crdate',
        'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'sys_products_cards.gif',
        'searchFields' => 'owner_name,cc_number',
    ),
    'columns' => array (
        'endtime' => array (
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'endtime',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'range' => array (
                    'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['endtimeYear']),
                    'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
                )
            )
        ),
        'cc_number' => array (
            'exclude' => 0,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_cards.cc_number',
            'config' => array (
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
                'default' => ''
            )
        ),
        'owner_name' => array (
            'exclude' => 0,
            'label' => DIV2007_LANGUAGE_LGL . 'name',
            'config' => array (
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'default' => ''
            )
        ),
        'cc_type' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_cards.cc_type',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array (
                    array('LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_cards.cc_type.I.0', '0'),
                    array('LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_cards.cc_type.I.1', '1'),
                    array('LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_cards.cc_type.I.2', '2'),
                    array('LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_cards.cc_type.I.3', '3'),
                ),
                'size' => 1,
                'maxitems' => 1,
                'default' => ''
            )
        ),
        'cvv2' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_cards.cvv2',
            'config' => array (
                'type' => 'input',
                'size' => '4',
                'eval' => 'int',
                'max' => '4',
                'default' => 0
            )
        ),
    ),
    'types' => array (
        '1' => array('showitem' => 'cc_number, owner_name, cc_type, cvv2, endtime')
    ),
    'palettes' => array (
        '1' => array('showitem' => '')
    )
);

return $result;
