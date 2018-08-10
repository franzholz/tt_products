<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$result = NULL;

if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode'] == '2') {
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
			'hideTable' => TRUE,
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
					'maxitems' => 1
				)
			),
			'uid_foreign' => array (
				'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_products_mm_articles.uid_foreign',
				'config' => array (
					'type' => 'select',
					'renderType' => 'selectSingle',
					'foreign_table' => 'tt_products_articles',
					'maxitems' => 1
				)
			),
			'sorting' => array (
				'config' => array (
					'type' => 'passthrough',
				)
			),
			'articlesort' => array (
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
}


return $result;
