<?php
defined('TYPO3_MODE') || die('Access denied.');

// ******************************************************************
// products to download relation table, tt_products_products_mm_downloads
// ******************************************************************

$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_products_mm_downloads',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_products_mm_downloads.uid_local',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tt_products',
				'maxitems' => 1,
				'default' => 0
			)
		),
		'uid_foreign' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_products_mm_downloads.uid_foreign',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tt_products_downloads',
				'maxitems' => 1,
				'default' => 0
			)
		),
		'localsort' => array (
			'config' => array (
				'type' => 'passthrough',
				'default' => 0
			)
		),
		'foreignsort' => array (
			'config' => array (
				'type' => 'passthrough',
				'default' => 0
			)
		),
	),
	'types' => array(
		'0' => array(
			'showitem' => ''
		)
	)
);

return $result;
