<?php
defined('TYPO3_MODE') || die('Access denied.');

// ******************************************************************
// products to graduated price relation table, tt_products_attribute_mm_graduated_price
// ******************************************************************
$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_attribute_mm_graduated_price',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden'
		),
		'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
		'crdate' => 'crdate',
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_cat.gif',
		'hideTable' => true,
	),
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'hidden',
			'config' => array (
				'type' => 'check',
				'default' => 0
			)
		),
		'uid_local' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_attribute_mm_graduated_price.uid_local',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tt_products',
				'maxitems' => 1,
				'default' => 0
			)
		),
		'uid_foreign' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_attribute_mm_graduated_price.uid_foreign',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tt_products_graduated_price',
				'maxitems' => 1,
				'default' => 0
			)
		),
		'sorting' => array (
			'config' => array (
				'type' => 'passthrough',
				'default' => 0
			)
		),
		'sorting_foreign' => array (
			'config' => array (
				'type' => 'passthrough',
				'default' => 0
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'hidden,--palette--;;1, uid_local, uid_foreign')
	)
);


return $result;
