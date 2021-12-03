<?php
defined('TYPO3_MODE') || die('Access denied.');


// ******************************************************************
// order to voucher codes table, tt_products_accessory_products_products_mm
// ******************************************************************
$result = array (
    'ctrl' => array (
        'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_accessory_products_products_mm',
        'label' => 'uid_local',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'enablecolumns' => array (
            'disabled' => 'hidden'
        ),
        'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
        'crdate' => 'crdate',
        'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_relations.gif',
        'hideTable' => true,
    ),
    'columns' => array (
        'uid_local' => array (
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_accessory_products_products_mm.uid_local',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_products_orders',
                'maxitems' => 1,
                'default' => 0
            )
        ),
        'uid_foreign' => array (
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_accessory_products_products_mm.uid_foreign',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_voucher_codes',
                'maxitems' => 1,
                'default' => 0
            )
        ),
        'sorting' => array (
            'config' => array (
                'type' => 'passthrough',
            )
        ),
        'sorting_foreign' => array (
            'config' => array (
                'type' => 'passthrough',
            )
        ),
    ),
    'types' => array(
        '0' => array(
            'showitem' => ''
        )
    )
);

if (
    defined('TYPO3_version') &&
    version_compare(TYPO3_version, '10.0.0', '<')
) {
    $result['interface'] = [];
    $result['interface']['showRecordFieldList'] =   
        'uid_local,uid_foreign';
}

return $result;

