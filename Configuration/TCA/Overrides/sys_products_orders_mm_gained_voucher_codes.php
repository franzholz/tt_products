<?php
defined('TYPO3_MODE') || die('Access denied.');

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('voucher')) {

	$temporaryColumns = array (
		'uid_local' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders_mm_gained_voucher_codes.uid_local',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'sys_products_orders',
				'maxitems' => 1,
				'default' => 0
			)
		),
		'uid_foreign' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders_mm_gained_voucher_codes.uid_foreign',
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
				'default' => 0
			)
		),
		'sorting_foreign' => array (
			'config' => array (
				'type' => 'passthrough',
				'default' => 0
			)
		),
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
		'sys_products_orders_mm_gained_voucher_codes',
		$temporaryColumns
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
		'sys_products_orders_mm_gained_voucher_codes',
		'uid_local,uid_foreign',
		''
	);
}

