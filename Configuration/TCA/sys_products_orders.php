<?php

defined('TYPO3') || die('Access denied.');

$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders',
        'label' => 'name',
        'label_alt' => 'last_name',
        'default_sortby' => 'ORDER BY name',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'prependAtCopy' => $languageLglPath . 'prependAtCopy',
        'crdate' => 'crdate',
        'mainpalette' => 1,
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/sys_products_orders.gif',
        'searchFields' => 'uid,name,first_name,last_name,vat_id,address,zip,city,telephone,email,giftcode,bill_no,tracking_code',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ]
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'tstamp' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tstamp',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'readOnly' => 1,
            ],
        ],
        'crdate' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.crdate',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'readOnly' => 1,
            ],
        ],
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'language',
            'config' => [
                'type' => 'language',
                'default' => 0,
            ],
        ],
        'name' => [
            'exclude' => 0,
            'label' => $languageLglPath . 'name',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'required,trim',
                'default' => null,
            ],
        ],
        'first_name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.first_name',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '50',
                'eval' => 'trim',
                'default' => null,
            ],
        ],
        'last_name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.last_name',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '50',
                'eval' => 'trim',
                'default' => null,
            ],
        ],
        'slug' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['name', 'crdate'],
                    'fieldSeparator' => '_',
                    'replacements' => [
                        '/' => '-',
                    ],
                ],
                'fallbackCharacter' => '-',
                'default' => null,
            ],
        ],
        'company' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'company',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'eval' => 'trim',
                'default' => null,
            ],
        ],
        'vat_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.vat_id',
            'config' => [
                'type' => 'input',
                'size' => '15',
                'max' => '15',
                'default' => null,
            ],
        ],
        'salutation' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.salutation',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.salutation.I.0',
                        'value' => '0'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.salutation.I.1',
                        'value' => '1'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.salutation.I.2',
                        'value' => '2'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.salutation.I.3',
                        'value' => '3'],
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'address' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'address',
            'config' => [
                'type' => 'input',
                'size' => '50',
                'max' => '256',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'house_no' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.house_no',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => '20',
                'max' => '20',
                'default' => null,
            ],
        ],
        'zip' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'zip',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '20',
                'eval' => 'trim',
                'default' => null,
            ],
        ],
        'city' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'city',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '50',
                'eval' => 'trim',
                'default' => null,
            ],
        ],
        'country' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'country',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '60',
                'eval' => 'trim',
                'default' => null,
            ],
        ],
        'telephone' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'phone',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '20',
                'default' => null,
            ],
        ],
        'email' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'email',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '80',
                'default' => null,
            ],
        ],
        'fax' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'fax',
            'config' => [
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'default' => null,
            ],
        ],
        'business_partner' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_business_partner',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_business_partner.I.0',
                        'value' => '0'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_business_partner.I.1',
                        'value' => '1'],
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'organisation_form' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.A1',
                        'value' => 'A1'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.A2',
                        'value' => 'A2'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.A3',
                        'value' => 'A3'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.BH',
                        'value' => 'BH'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.E1',
                        'value' => 'E1'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.E2',
                        'value' => 'E2'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.E3',
                        'value' => 'E3'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.E4',
                        'value' => 'E4'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G1',
                        'value' => 'G1'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G2',#
                        'value' => 'G2'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G3',
                        'value' => 'G3'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G4',
                        'value' => 'G4'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G5',
                        'value' => 'G5'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G6',
                        'value' => 'G6'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G7',
                        'value' => 'G7'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.K2',
                        'value' => 'K2'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.K3',
                        'value' => 'K3'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.KG',
                        'value' => 'KG'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.KO',
                        'value' => 'KO'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.O1',
                        'value' => 'O1'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.P',
                        'value' => 'P'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.S1',
                        'value' => 'S1'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.S2',
                        'value' => 'S2'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.S3',
                        'value' => 'S3'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.U',
                        'value' => 'U'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.V1',
                        'value' => 'V1'],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.Z1',
                        'value' => 'Z1'],
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 'U',
            ],
        ],
        'payment' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.payment',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'default' => null,
            ],
        ],
        'shipping' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.shipping',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '80',
                'default' => null,
            ],
        ],
        'amount' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.amount',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '20',
                'eval' => 'trim,double2',
                'default' => 0,
            ],
        ],
        'tax_mode' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.tax_mode',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.tax_mode.I.0', '0'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.tax_mode.I.1', '1'],
                ],
                'default' => 0,
            ],
        ],
        'pay_mode' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.0', '0'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.1', '1'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.2', '2'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.3', '3'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.4', '4'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.5', '5'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.6', '6'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.7', '7'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.8', '8'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.9', '9'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.10', '10'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.11', '11'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.12', '12'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.13', '13'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.14', '14'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.15', '15'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.16', '16'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.pay_mode.I.17', '17'],
                ],
                'default' => 0,
            ],
        ],
        'email_notify' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.email_notify',
            'config' => [
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'default' => null,
            ],
        ],
        'tracking_code' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.tracking_code',
            'config' => [
                'type' => 'input',
                'size' => '32',
                'max' => '64',
                'default' => null,
            ],
        ],
        'status' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.status',
            'config' => [
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'default' => null,
            ],
        ],
        'status_log' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.status_log',
            'config' => [
                'type' => 'text',
                'cols' => '80',
                'rows' => '4',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'orderData' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.orderData',
            'config' => [
                'type' => 'text',
                'cols' => '160',
                'rows' => '160',
                'wrap' => 'off',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'orderHtml' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.orderHtml',
            'config' => [
                'type' => 'user',
                'size' => '30',
                'renderType' => 'orderHtmlElement',
                'parameters' => [
                    'format' => 'html',
                ],
                'db' => 'passthrough',
                'default' => null,
            ],
        ],
        'agb' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.agb',
            'config' => [
                'type' => 'input',
                'size' => '2',
                'max' => '2',
                'readOnly' => '1',
            ],
        ],
        'feusers_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.feusers_uid',
            'config' => [
                'type' => 'input',
                'size' => '11',
                'max' => '11',
                'default' => 0,
            ],
        ],
        'creditpoints' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.creditpoints',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'default' => 0,
            ],
        ],
        'creditpoints_spended' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.creditpoints_spended',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'default' => 0,
            ],
        ],
        'creditpoints_saved' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.creditpoints_saved',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'default' => 0,
            ],
        ],
        'creditpoints_gifts' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.creditpoints_gifts',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'default' => 0,
            ],
        ],
        'desired_date' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.desired_date',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'default' => null,
            ],
        ],
        'desired_time' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.desired_time',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'default' => null,
            ],
        ],
        'client_ip' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.client_ip',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '50',
                'default' => null,
            ],
        ],
        'note' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'note',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'giftservice' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.giftservice',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'cc_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_cards.cc_number',
            'config' => [
                'type' => 'group',
                'allowed' => 'sys_products_cards',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'ac_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_accounts.iban',
            'config' => [
                'type' => 'group',
                'allowed' => 'sys_products_accounts',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'foundby' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.0',
                        'value' => '0'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.1',
                        'value' => '1'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.2',
                        'value' => '2'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.3',
                        'value' => '3'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.4',
                        'value' => '4'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.5',
                        'value' => '5'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.6',
                        'value' => '6'
                    ],
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'giftcode' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.order_code',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 80,
                'default' => null,
            ],
        ],
        'date_of_birth' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.date_of_birth',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
            ],
        ],
        'date_of_payment' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.date_of_payment',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
            ],
        ],
        'date_of_delivery' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.date_of_delivery',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
            ],
        ],
        'bill_no' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.bill_no',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '80',
                'default' => null,
            ],
        ],
        'radio1' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.radio1',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.radio1.I.0', '0'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.radio1.I.1', '1'],
                    ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.radio1.I.2', '2'],
                ],
                'default' => '0',
            ],
        ],
        'ordered_products' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.ordered_products',
            'config' => [
                'type' => 'user',
                'renderType' => 'orderedProductsElement',
                'parameters' => [
                    'mode' => 1,
                ],
                'db' => 'passthrough',
                'default' => null,
            ],
        ],
        'fal_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.fal_uid',
            'config' => [
                'type' => 'file',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                    'collapseAll' => true,
                ],
            ],
            'allowed' => 'common-image-types',
        ],
        'gained_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.gained_uid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_products',
                'MM' => 'sys_products_orders_mm_gained_tt_products',
                'foreign_table' => 'tt_products',
                'foreign_table_where' => ' ORDER BY tt_products.title',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 10,
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '1' => [
                'columnsOverrides' => [
                    'note' => [
                        'config' => [
                            'enableRichtext' => '1',
                        ],
                    ],
                ],
                'showitem' => 'hidden,--palette--;;1, name, sys_language_uid,first_name,last_name,slug,company,vat_id,salutation,address,house_no,zip,city,country,telephone,email,payment,shipping,amount,tax_mode,pay_mode,email_notify,tracking_code,status,fax,business_partner,organisation_form,agb,feusers_uid,creditpoints,creditpoints_spended,creditpoints_saved,creditpoints_gifts,desired_date,desired_time,client_ip,note,giftservice,foundby,giftcode,cc_uid,ac_uid,date_of_birth,date_of_payment,date_of_delivery,bill_no,radio1,ordered_products,fal_uid,gained_uid,' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.orderHtmlDiv,orderHtml,',
            ],
    ],
    'palettes' => [
        '1' => ['showitem' => 'tstamp, crdate'],
    ],
];

if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['sepa']) {
    $result['columns']['ac_uid']['label'] = 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_accounts.ac_number';
}

return $result;
