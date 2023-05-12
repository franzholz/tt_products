<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function () {
    $table = 'fe_users';
    $languageSubpath = '/Resources/Private/Language/';

    $temporaryColumns = [
        'cnum' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.cnum',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '50',
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'static_info_country' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.static_info_country',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'max' => '3',
                'eval' => '',
                'default' => ''
            ]
        ],
        'zone' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.zone',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '40',
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'tt_products_memoItems' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_memoItems',
            'config' => [
                'type' => 'input',
                'size' => '50',
                'max' => '256',
                'eval' => 'null',
                'default' => '',
            ]
        ],
        'tt_products_memodam' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_memodam',
            'config' => [
                'type' => 'input',
                'size' => '50',
                'max' => '256',
                'eval' => 'null',
                'default' => '',
            ]
        ],
        'tt_products_discount' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_discount',
            'config' => [
                'type' => 'input',
                'size' => '4',
                'max' => '8',
                'eval' => 'trim,double2',
                'range' => [
                    'upper' => '100',
                    'lower' => '0'
                ],
                'default' => 0
            ]
        ],
        'tt_products_creditpoints' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_creditpoints',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'max' => '20',
                'eval' => 'trim,integer',
                'default' => 0
            ]
        ],
        'tt_products_vouchercode' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_vouchercode',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '256',
                'default' => ''
            ]
        ],
        'tt_products_vat' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_vat',
            'config' => [
                'type' => 'input',
                'size' => '15',
                'max' => '15',
                'default' => ''
            ]
        ],
        'tt_products_payment_bill' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_payment_bill',
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'tt_products_business_partner' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_business_partner',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_business_partner.I.0', '0'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_business_partner.I.1', '1'],
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 0
            ]
        ],
        'tt_products_organisation_form' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.A1', 'A1'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.A2', 'A2'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.A3', 'A3'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.BH', 'BH'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.E1', 'E1'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.E2', 'E2'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.E3', 'E3'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.E4', 'E4'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G1', 'G1'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G2', 'G2'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G3', 'G3'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G4', 'G4'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G5', 'G5'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G6', 'G6'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G7', 'G7'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.K2', 'K2'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.K3', 'K3'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.KG', 'KG'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.KO', 'KO'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.O1', 'O1'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.P',  'P'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.S1', 'S1'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.S2', 'S2'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.S3', 'S3'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.U',  'U'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.V1', 'V1'],
                    ['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.Z1', 'Z1'],
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 'U'
            ]
        ],
    ];

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

    $searchFields = explode(',', $GLOBALS['TCA'][$table]['ctrl']['searchFields'] . ',tt_products_vouchercode,comments,tt_products_organisation_form');
    $searchFields = array_unique($searchFields);
    $GLOBALS['TCA'][$table]['ctrl']['searchFields'] = implode(',', $searchFields);

});
