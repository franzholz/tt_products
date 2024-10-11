<?php

defined('TYPO3') || die('Access denied.');

call_user_func(function ($extensionKey, $table): void {
    $languageSubpath = '/Resources/Private/Language/';

    $temporaryColumns = [
        'cnum' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.cnum',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '50',
                'eval' => 'trim',
                'default' => null,
            ],
        ],
        'static_info_country' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.static_info_country',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'max' => '3',
                'eval' => '',
                'default' => null,
            ],
        ],
        'zone' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.zone',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '40',
                'eval' => 'trim',
                'default' => null,
            ],
        ],
        'tt_products_memoItems' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_memoItems',
            'config' => [
                'type' => 'input',
                'size' => '50',
                'max' => '256',
                'eval' => 'null',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'tt_products_memodam' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_memodam',
            'config' => [
                'type' => 'input',
                'size' => '50',
                'max' => '256',
                'eval' => 'null',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'tt_products_discount' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_discount',
            'config' => [
                'type' => 'number',
                'size' => '4',
                'max' => '8',
                'eval' => 'trim',
                'range' => [
                    'upper' => '100',
                    'lower' => '0',
                ],
                'default' => 0,
            ],
        ],
        'tt_products_creditpoints' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_creditpoints',
            'config' => [
                'type' => 'input',
                'size' => '5',
                'max' => '20',
                'eval' => 'trim,integer',
                'default' => 0,
            ],
        ],
        'tt_products_vouchercode' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_vouchercode',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '256',
                'default' => null,
            ],
        ],
        'tt_products_vat' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_vat',
            'config' => [
                'type' => 'input',
                'size' => '15',
                'max' => '15',
                'default' => null,
            ],
        ],
        'tt_products_payment_bill' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_payment_bill',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'tt_products_business_partner' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_business_partner',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' .
                            $table . '.tt_products_business_partner.I.0',
                        'value' => '0'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' .
                            $table . '.tt_products_business_partner.I.1',
                        'value' => '1'
                    ],
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'tt_products_organisation_form' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.A1',
                        'value' => 'A1'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.A2',
                        'value' => 'A2'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.A3',
                        'value' => 'A3'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.BH',
                        'value' => 'BH'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.E1',
                        'value' => 'E1'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.E2',
                        'value' => 'E2'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.E3',
                        'value' => 'E3'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.E4',
                        'value' => 'E4'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G1',
                        'value' => 'G1'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G2',
                        'value' => 'G2'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G3',
                        'value' => 'G3'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G4',
                        'value' => 'G4'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G5',
                        'value' => 'G5'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G6',
                        'value' => 'G6'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.G7',
                        'value' => 'G7'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.K2',
                        'value' => 'K2'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.K3',
                        'value' => 'K3'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.KG',
                        'value' => 'KG'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.KO',
                        'value' => 'KO'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.O1',
                        'value' => 'O1'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.P',
                        'value' => 'P'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.S1',
                        'value' => 'S1'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.S2', 'S2'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.S3', 'S3'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.U',
                        'value' => 'U'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.V1',
                        'value' => 'V1'
                    ],
                    [
                        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tt_products_organisation_form.Z1',
                        'value' => 'Z1'
                    ],
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 'U',
            ],
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
}, 'tt_products', basename(__FILE__, '.php'));
