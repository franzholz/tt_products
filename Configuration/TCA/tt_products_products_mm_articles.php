<?php
defined('TYPO3_MODE') || die('Access denied.');

$result = NULL;

if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode'] >= '1') {
	$result = array (
		'ctrl' => array (
			'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_products_mm_articles',
			'label' => 'uid_local',
			'tstamp' => 'tstamp',
			'delete' => 'deleted',
			'enablecolumns' => array (
				'disabled' => 'hidden'
			),
			'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
			'crdate' => 'crdate',
			'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_articles.gif',
			'hideTable' => true,
		),
		'interface' => array (
			'showRecordFieldList' => 'uid_local,uid_foreign'
		),
		'columns' => array (
			'uid_local' => array (
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_products_mm_articles.uid_local',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'foreign_table' => 'tt_products',
					'maxitems' => 1,
					'default' => 0
				)
			),
			'uid_foreign' => array (
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_products_mm_articles.uid_foreign',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'foreign_table' => 'tt_products_articles',
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
			'0' => array(
				'showitem' => ''
			)
		)
	);
}

return $result;
