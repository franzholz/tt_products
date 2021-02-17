<?php
defined('TYPO3_MODE') || die('Access denied.');


// *****************************************************************
// These are the orders
// ******************************************************************

$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders',
		'label' => 'name',
		'label_alt' => 'last_name',
		'default_sortby' => 'ORDER BY name',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
		'crdate' => 'crdate',
		'mainpalette' => 1,
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'sys_products_orders.gif',
		'dividers2tabs' => '1',
		'searchFields' => 'uid,name,first_name,last_name,vat_id,address,zip,city,telephone,email,giftcode,bill_no,tracking_code',
	),
	'interface' => array (
		'showRecordFieldList' => 'hidden,sys_language_uid,name,first_name,last_name,company,vat_id,salutation,address,house_no,,zip,city,country,telephone,email,fax,business_partner,organisation_form,payment,shipping,amount,tax_mode,pay_mode,email_notify,tracking_code,status,agb,feusers_id,creditpoints,creditpoints_spended,creditpoints_saved,creditpoints_gifts,desired_date,desired_time,client_ip,note,giftservice,cc_uid,ac_uid,date_of_birth,date_of_payment,date_of_delivery,bill_no,radio1,ordered_products'
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
		'tstamp' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => 0
			)
		),
		'crdate' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => 0
			)
		),
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'language',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array(DIV2007_LANGUAGE_LGL . 'allLanguages', -1),
					array(DIV2007_LANGUAGE_LGL . 'default_value', 0)
				),
				'default' => 0
			)
		),
		'name' => array (
			'exclude' => 0,
			'label' => DIV2007_LANGUAGE_LGL . 'name',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'required,trim',
				'default' => ''
			)
		),
		'first_name' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.first_name',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'trim',
				'default' => ''
			)
		),
		'last_name' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.last_name',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'trim',
				'default' => ''
			)
		),
        'slug' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.slug',
            'config' => array (
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => array (
                    'fields' => array ('name', 'crdate'),
                    'fieldSeparator' => '_',
                    'prefixParentPageSlug' => false,
                    'replacements' => array (
                        '/' => '-',
                    ),
                ),
                'fallbackCharacter' => '-',
                'default' => ''
            )
        ),
		'company' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'company',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'eval' => 'trim',
				'default' => ''
			)
		),
		'vat_id' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.vat_id',
			'config' => Array (
				'type' => 'input',
				'size' => '15',
				'max' => '15',
				'default' => ''
			)
		),
		'salutation' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.salutation',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array (
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.salutation.I.0', '0'),
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.salutation.I.1', '1'),
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.salutation.I.2', '2'),
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.salutation.I.3', '3'),
				),
				'size' => 1,
				'maxitems' => 1,
				'default' => 0
			)
		),
		'address' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'address',
			'config' => array (
				'type' => 'input',
				'size' => '50',
				'max' => '256',
				'default' => ''
			)
		),
		'house_no' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.house_no',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'size' => '20',
				'max' => '20',
				'default' => ''
			)
		),
		'zip' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'zip',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim',
				'default' => ''
			)
		),
		'city' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'city',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'eval' => 'trim',
				'default' => ''
			)
		),
		'country' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'country',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '60',
				'eval' => 'trim',
				'default' => ''
			)
		),
		'telephone' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'phone',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'max' => '20',
				'default' => ''
			)
		),
		'email' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'email',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'max' => '80',
				'default' => ''
			)
		),
		'fax' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'fax',
			'config' => array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'default' => ''
			)
		),
		'business_partner' => array (
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
		'organisation_form' => array (
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
		'payment' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.payment',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'default' => ''
			)
		),
		'shipping' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.shipping',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '80',
				'default' => ''
			)
		),
		'amount' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.amount',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'max' => '20',
				'eval' => 'trim,double2',
				'default' => 0
			)
		),
		'tax_mode' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.tax_mode',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.tax_mode.I.0', '0'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.tax_mode.I.1', '1'),
				),
				'default' => 0
			)
		),
		'pay_mode' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.0', '0'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.1', '1'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.2', '2'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.3', '3'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.4', '4'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.5', '5'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.6', '6'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.7', '7'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.8', '8'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.9', '9'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.10', '10'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.11', '11'),
                    array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.12', '12'),
                    array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.13', '13'),
                    array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.14', '14'),
                    array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.15', '15'),
                    array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.16', '16'),
                    array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.pay_mode.I.17', '17')
				),
				'default' => 0
			)
		),
		'email_notify' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.email_notify',
			'config' => array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'default' => ''
			)
		),
		'tracking_code' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.tracking_code',
			'config' => array (
				'type' => 'input',
				'size' => '32',
				'max' => '64',
				'default' => ''
			)
		),
		'status' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.status',
			'config' => array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'default' => ''
			)
		),
		'status_log' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.status_log',
			'config' => array (
				'type' => 'text',
				'cols' => '80',
				'rows' => '4',
				'eval' => 'null',
				'default' => ''
			)
		),
		'orderData' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.orderData',
			'config' => array (
				'type' => 'text',
				'cols' => '160',
				'rows' => '160',
				'wrap' => 'off',
				'eval' => 'null',
				'default' => ''
			)
		),
        'orderHtml' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:sys_products_orders.orderHtml',
            'config' => [
                'type' => 'user',
                'size' => '30',
                'renderType' => 'orderHtmlElement',
                'parameters' => [
                    'format' => 'html'
                ],
                'db' => 'passthrough',
                'default' => ''
            ],
        ],
		'agb' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.agb',
			'config' => array (
				'type' => 'input',
				'size' => '2',
				'max' => '2',
				'readOnly' => '1',
			)
		),
		'feusers_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.feusers_uid',
			'config' => array (
				'type' => 'input',
				'size' => '11',
				'max' => '11',
				'default' => 0
			)
		),
		'creditpoints' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.creditpoints',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
				'default' => 0
			)
		),
		'creditpoints_spended' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.creditpoints_spended',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
				'default' => ''
			)
		),
		'creditpoints_saved' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.creditpoints_saved',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
				'default' => ''
			)
		),
		'creditpoints_gifts' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.creditpoints_gifts',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
				'default' => ''
			)
		),
		'desired_date' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.desired_date',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
				'default' => ''
			)
		),
		'desired_time' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.desired_time',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
				'default' => ''
			)
		),
		'client_ip' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.client_ip',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '50',
				'default' => ''
			)
		),
		'note' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'note',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'eval' => 'null',
				'default' => ''
			)
		),
		'giftservice' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.giftservice',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'eval' => 'null',
				'default' => ''
			)
		),
		'cc_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_cards.cc_number',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'sys_products_cards',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 0
			)
		),
		'ac_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_accounts.iban',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'sys_products_accounts',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 0
			)
		),
		'foundby' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array (
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.0', '0'),
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.1', '1'),
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.2', '2'),
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.3', '3'),
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.4', '4'),
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.5', '5'),
					array('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.foundby.I.6', '6'),
				),
				'size' => 1,
				'maxitems' => 1,
				'default' => 0
			)
		),
		'giftcode' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.order_code',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max' => '80',
				'default' => ''
			)
		),
		'date_of_birth' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.date_of_birth',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => 0
			)
		),
		'date_of_payment' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.date_of_payment',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => 0
			)
		),
		'date_of_delivery' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.date_of_delivery',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => 0
			)
		),
		'bill_no' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.bill_no',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max' => '80',
				'default' => ''
			)
		),
		'radio1' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.radio1',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.radio1.I.0', '0'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.radio1.I.1', '1'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.radio1.I.2', '2'),
				),
				'default' => 0
			)
		),
        'ordered_products' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.ordered_products',
            'config' => [
                'type' => 'user',
                'renderType' => 'orderedProductsElement',
                'parameters' => [
                    'mode' => 1
                ],
                'db' => 'passthrough',
                'default' => ''
            ],
        ],
	),
	'types' => array (
		'1' =>
			array(
                'columnsOverrides' => array(
                        'note' => array(
                            'config' => array(
                                'enableRichtext' => '1'
                            )
                        )
                    ),
                'showitem' =>
                    'hidden,--palette--;;1, name, sys_language_uid,first_name,last_name,slug,company,vat_id,salutation,address,house_no,zip,city,country,telephone,email,payment,shipping,amount,tax_mode,pay_mode,email_notify,tracking_code,status,fax,business_partner,organisation_form,agb,feusers_uid,creditpoints,creditpoints_spended,creditpoints_saved,creditpoints_gifts,desired_date,desired_time,client_ip,note,giftservice,foundby,giftcode,cc_uid,ac_uid,date_of_birth,date_of_payment,date_of_delivery,bill_no,radio1,ordered_products,' .
				'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_orders.orderHtmlDiv,orderHtml,'
            )
	),
	'palettes' => array (
		'1' => array('showitem' => 'tstamp, crdate'),
	)
);


if (
    version_compare(TYPO3_version, '8.5.0', '<')
) {
    $result['types']['1']['showitem'] =
        preg_replace(
            '/(^|,)\s*note\s*(,|$)/', '$1 note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/] $2',
            $result['types']['1']['showitem']
        );
}


if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['sepa']) {
    $result['columns']['ac_uid']['label'] = 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_products_accounts.ac_number';
}


return $result;

