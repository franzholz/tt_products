<?php
defined('TYPO3') || die('Access denied.');

// ******************************************************************
// products to download relation table, tt_products_products_mm_downloads
// ******************************************************************

$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.';

$result = [
	'ctrl' => [
		'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_products_mm_downloads',
		'label' => 'uid_local',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'enablecolumns' => [
			'disabled' => 'hidden'
		],
		'prependAtCopy' => $languageLglPath . 'prependAtCopy',
		'crdate' => 'crdate',
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_relations.gif',
		'hideTable' => true,
	],
	'columns' => [
		'uid_local' => [
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_products_mm_downloads.uid_local',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tt_products',
				'maxitems' => 1,
				'default' => 0
			]
		],
		'uid_foreign' => [
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_products_mm_downloads.uid_foreign',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tt_products_downloads',
				'maxitems' => 1,
				'default' => 0
			]
		],
		'localsort' => [
			'config' => [
				'type' => 'passthrough',
				'default' => 0
			]
		],
		'foreignsort' => [
			'config' => [
				'type' => 'passthrough',
				'default' => 0
			]
		],
	],
	'types' => [
		'0' => [
			'showitem' => ''
		]
	]
];

return $result;
