<?php
defined('TYPO3_MODE') || die('Access denied.');

$table = 'fe_users';

$temporaryColumns = array (
    'cnum' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:' . $table . '.cnum',
        'config' => array(
            'type' => 'input',
            'size' => '20',
            'max' => '50',
            'eval' => 'trim',
            'default' => ''
        )
    ),
    'static_info_country' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:' . $table . '.static_info_country',
        'config' => array(
            'type' => 'input',
            'size' => '5',
            'max' => '3',
            'eval' => '',
            'default' => ''
        )
    ),
    'zone' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:' . $table . '.zone',
        'config' => array(
            'type' => 'input',
            'size' => '20',
            'max' => '40',
            'eval' => 'trim',
            'default' => ''
        )
    ),
	'tt_products_memoItems' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_memoItems',
		'config' => array (
			'type' => 'input',
			'size' => '50',
			'max' => '256',
			'eval' => 'null',
			'default' => ''
		)
	),
	'tt_products_discount' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_discount',
		'config' => array (
			'type' => 'input',
			'size' => '4',
			'max' => '8',
			'eval' => 'trim,double2',
			'range' => array (
				'upper' => '100',
				'lower' => '0'
			),
			'default' => 0
		)
	),
	'tt_products_creditpoints' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_creditpoints',
		'config' => array (
			'type' => 'input',
			'size' => '5',
			'max' => '20',
			'eval' => 'trim,integer',
			'default' => 0
		)
	),
	'tt_products_vouchercode' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_vouchercode',
		'config' => array (
			'type' => 'input',
			'size' => '20',
			'max' => '256',
			'default' => ''
		)
	),
	'tt_products_vat' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_vat',
		'config' => array (
			'type' => 'input',
			'size' => '15',
			'max' => '15',
			'default' => ''
		)
	),
	'tt_products_business_partner' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_business_partner',
		'config' => array (
			'type' => 'select',
			'renderType' => 'selectSingle',
			'items' => array (
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_business_partner.I.0', '0'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_business_partner.I.1', '1'),
			),
			'size' => 1,
			'maxitems' => 1,
			'default' => 0
		)
	),
	'tt_products_organisation_form' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form',
		'config' => array (
			'type' => 'select',
			'renderType' => 'selectSingle',
			'items' => array (
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.A1', 'A1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.A2', 'A2'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.A3', 'A3'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.BH', 'BH'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.E1', 'E1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.E2', 'E2'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.E3', 'E3'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.E4', 'E4'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G1', 'G1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G2', 'G2'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G3', 'G3'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G4', 'G4'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G5', 'G5'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G6', 'G6'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.G7', 'G7'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.K2', 'K2'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.K3', 'K3'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.KG', 'KG'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.KO', 'KO'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.O1', 'O1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.P', 'P'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.S1', 'S1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.S2', 'S2'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.S3', 'S3'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.U', 'U'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.V1', 'V1'),
				array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:fe_users.tt_products_organisation_form.Z1', 'Z1'),
			),
			'size' => 1,
			'maxitems' => 1,
			'default' => 'U'
		)
	),
);

$columns = array_keys($temporaryColumns);

foreach ($columns as $column) {
    if (isset($GLOBALS['TCA'][$table]['columns'][$column])) {
        unset($temporaryColumns[$column]);
    }
}

$columns = array_keys($temporaryColumns);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $temporaryColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    $table,
    implode(',', $columns)
);


$searchFields = explode(',', $GLOBALS['TCA']['fe_users']['ctrl']['searchFields'] . ',tt_products_vouchercode,comments,tt_products_organisation_form');
$searchFields = array_unique($searchFields);
$GLOBALS['TCA']['fe_users']['ctrl']['searchFields'] = implode(',', $searchFields);


