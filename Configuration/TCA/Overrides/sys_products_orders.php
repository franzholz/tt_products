<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

use JambageCom\Div2007\Utility\TcaUtility;

call_user_func(function ($extensionKey, $table): void {
    $languageSubpath = '/Resources/Private/Language/';
    $languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';
    $version = VersionNumberUtility::getCurrentTypo3Version();
    $configuration = GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);

    $GLOBALS['TCA'][$table]['columns']['slug']['config']['eval'] = $configuration->getSlugBehaviour();

    $orderBySortingTablesArray = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['orderBySortingTables']);

    if (
        !empty($orderBySortingTablesArray) &&
        in_array($table, $orderBySortingTablesArray)
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
        $GLOBALS['TCA'][$table]['columns']['sorting'] =
            [
                'config' => [
                    'type' => 'passthrough',
                    'default' => 0,
                ],
            ];
    }

    if (version_compare($version, '12.0.0', '<')) {
        $GLOBALS['TCA'][$table]['columns']['fal_uid'] =
        [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.fal_uid',
            'config' => ExtensionManagementUtility::getFileFieldTCAConfig('fal_uid'),
        ];

        $temporaryColumns = [
            'salutation' => [
                'exclude' => 1,
               'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.salutation',
               'config' => [
                   'type' => 'select',
               'renderType' => 'selectSingle',
               'items' => [
                   ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.salutation.I.0', '0'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.salutation.I.1', '1'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.salutation.I.2', '2'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.salutation.I.3', '3'],
               ],
               'size' => 1,
               'maxitems' => 1,
               'default' => 0,
               ],
            ],
            'business_partner' => [
                'exclude' => 1,
               'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_business_partner',
               'config' => [
                   'type' => 'select',
               'renderType' => 'selectSingle',
               'items' => [
                   ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_business_partner.I.0', '0'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_business_partner.I.1', '1'],
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
                   ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.A1', 'A1'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.A2', 'A2'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.A3', 'A3'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.BH', 'BH'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.E1', 'E1'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.E2', 'E2'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.E3', 'E3'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.E4', 'E4'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G1', 'G1'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G2', 'G2'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G3', 'G3'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G4', 'G4'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G5', 'G5'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G6', 'G6'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.G7', 'G7'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.K2', 'K2'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.K3', 'K3'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.KG', 'KG'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.KO', 'KO'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.O1', 'O1'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.P', 'P'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.S1', 'S1'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.S2', 'S2'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.S3', 'S3'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.U', 'U'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.V1', 'V1'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:fe_users.tt_products_organisation_form.Z1', 'Z1'],
               ],
               'size' => 1,
               'maxitems' => 1,
               'default' => 'U',
               ],
            ],
            'foundby' => [
                'exclude' => 1,
               'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby',
               'config' => [
                   'type' => 'select',
               'renderType' => 'selectSingle',
               'items' => [
                   ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.0', '0'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.1', '1'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.2', '2'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.3', '3'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.4', '4'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.5', '5'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.foundby.I.6', '6'],
               ],
               'size' => 1,
               'maxitems' => 1,
               'default' => 0,
               ],
            ],
        ];
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            'sys_products_orders',
            $temporaryColumns
        );
    }

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('voucher')) {
        $temporaryColumns = [
            'gained_voucher' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.gained_voucher',
                'config' => [
                    'type' => 'inline',
                    'appearance' => [
                        'collapseAll' => true,
                        'newRecordLinkAddTitle' => true,
                        'useCombination' => true,
                    ],
                    'foreign_table' => 'sys_products_orders_mm_gained_voucher_codes',
                    'foreign_field' => 'uid_local',
                    'foreign_sortby' => 'sorting',
                    'foreign_label' => 'uid_foreign',
                    'foreign_selector' => 'uid_foreign',
                    'foreign_unique' => 'uid_foreign',
                    'maxitems' => 100,
                ],
            ],
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            'sys_products_orders',
            $temporaryColumns
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'sys_products_orders',
            'gained_voucher',
            '',
            'after:gained_uid'
        );
    }

    $excludeArray =
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['exclude'];

    if (
        isset($excludeArray) &&
        is_array($excludeArray) &&
        isset($excludeArray[$table]) &&
        is_array($excludeArray[$table])
    ) {
        \JambageCom\Div2007\Utility\TcaUtility::removeField(
            $GLOBALS['TCA'][$table],
            $excludeArray[$table]
        );
    }
}, 'tt_products', basename(__FILE__, '.php'));
