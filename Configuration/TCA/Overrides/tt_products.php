<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

use JambageCom\Div2007\Utility\TcaUtility;

call_user_func(function ($extensionKey, $table): void {
    $whereTaxCategory = '';
    $bSelectTaxMode = false;
    $extensionKeyStaticTaxes = 'static_info_tables_taxes';
    $languageSubpath = '/Resources/Private/Language/';
    $languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';
    $version = VersionNumberUtility::getCurrentTypo3Version();
    $configuration = GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);
    if (version_compare($version, '12.0.0', '<')) {

        $palleteAddition = ',--palette--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_file_reference.shopAttributes;tt_productsPalette';

        $GLOBALS['TCA'][$table]['columns']['datasheet_uid'] =
        [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.datasheet',
            'config' => ExtensionManagementUtility::getFileFieldTCAConfig(
                'datasheet_uid',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                        'collapseAll' => true,
                    ],
                    'foreign_types' => [
                        '0' => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        File::FILETYPE_APPLICATION => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                    ],
                ],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
            ),
        ];

        $GLOBALS['TCA'][$table]['columns']['image_uid'] =
        [
            'exclude' => 1,
            'label' => $languageLglPath . 'image',
            'config' => ExtensionManagementUtility::getFileFieldTCAConfig(
                'image_uid',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                        'collapseAll' => true,
                    ],
                    'foreign_types' => [
                        '0' => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        File::FILETYPE_IMAGE => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                    ],
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            )
        ];

        $GLOBALS['TCA'][$table]['columns']['smallimage_uid'] =
        [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.smallimage',
            'config' => ExtensionManagementUtility::getFileFieldTCAConfig(
                'smallimage_uid',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                        'collapseAll' => true,
                    ],
                    'foreign_types' => [
                        '0' => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath .
                            'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        File::FILETYPE_IMAGE => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath .
                            'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                    ],
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            )
        ];
    }

    $taxArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['tax'];
    $taxFields = '';

    if (
        isset($taxArray) &&
        is_array($taxArray) &&
        isset($taxArray['fields'])
    ) {
        $taxFields = implode(',', GeneralUtility::trimExplode(',', $taxArray['fields']));
    }

    if (
        (
            GeneralUtility::inList($taxFields, 'tax_id') ||
            GeneralUtility::inList($taxFields, 'taxcat_id')
        ) &&
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKeyStaticTaxes)
    ) {
        $eInfo = \JambageCom\Div2007\Utility\ExtensionUtility::getExtensionInfo($extensionKeyStaticTaxes);

        if (is_array($eInfo)) {
            $sittVersion = $eInfo['version'];
            if (version_compare($sittVersion, '0.3.0', '>=')) {
                $bSelectTaxMode = true;
            }
        }
    }

    if ($bSelectTaxMode) {
        $whereTaxCategory = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('static_tax_categories');

        $temporaryColumns = [];
        $addFields = [];
        $newFields = '';
        $firstField = '';

        if (GeneralUtility::inList($taxFields, 'taxcat_id')) {
            $temporaryColumns['taxcat_id'] = [
                'exclude' => '0',
                'label' => 'LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_tax_categories',
                'config' => [
                    'size' => 30,
                    'minitems' => 0,
                    'maxitems' => 100,
                    'type' => 'select',
                    'renderType' => 'selectTree',
                    'foreign_table' => 'static_tax_categories',
                    'foreign_table_where' => $whereTaxCategory . ' ORDER BY static_tax_categories.uid',
                    'MM' => 'tt_products_products_mm_tax_categories',
                    'MM_hasUidField' => true,
                    'treeConfig' => [
                        'parentField' => 'parentid',
                        'appearance' => [
                            'expandAll' => 0,
                            'showHeader' => true,
                            'maxLevels' => 99,
                        ],
                    ],
                    'exclude' => 1,
                    'default' => 0,
                ],
            ];
            $addFields[] = 'taxcat_id';
            $firstField = 'taxcat_id';
        }

        if (GeneralUtility::inList($taxFields, 'tax_id')) {
            $temporaryColumns['tax_id'] = [
                'exclude' => 0,
                'label' => 'LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => 'LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.0',
                            'value' => '0'
                        ],
                        [
                            'label' => 'LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.1',
                            'value' => '1'
                        ],
                        [
                            'label' => 'LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.2',
                            'value' => '2'
                        ],
                        [
                            'label' => 'LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.3',
                            'value' => '3'
                        ],
                        [
                            'label' => 'LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.4',
                            'value' => '4'
                        ],
                        [
                            'label' => 'LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.5',
                            'value' => '5'
                        ],
                    ],
                    'default' => 0,
                ],
            ];

            if (version_compare($version, '12.0.0', '<')) {
                $temporaryColumns['tax_id']['config']['items'] = [
                    ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.0', '0'],
                    ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.1', '1'],
                    ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.2', '2'],
                    ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.3', '3'],
                    ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.4', '4'],
                    ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.5', '5'],
                ];
            }

            $addFields[] = 'tax_id';
            if ($firstField == '') {
                $firstField = 'taxcat_id';
            }
        }

        if (version_compare($version, '12.0.0', '<')) {
            $temporaryColumns['fe_group'] = [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'label' => $languageLglPath . 'fe_group',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'size' => 7,
                    'maxitems' => 20,
                    'items' => [
                        [
                            $languageLglPath . 'hide_at_login',
                            -1,
                        ],
                        [
                            $languageLglPath . 'any_login',
                            -2,
                        ],
                        [
                            $languageLglPath . 'usergroups',
                            '--div--',
                        ],
                    ],
                    'exclusiveKeys' => '-1,-2',
                    'foreign_table' => 'fe_groups',
                    'foreign_table_where' => 'ORDER BY fe_groups.title',
                    'default' => 0,
                ],
            ];

            $temporaryColumns['download_type'] = [
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.download_type',
               'config' => [
                   'type' => 'select',
               'renderType' => 'selectSingle',
               'items' => [
                   ['', ''],
               ],
               'default' => null,
               'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
               ],
            ];

            $temporaryColumns['category'] = [
                'exclude' => 1,
               'label' => $languageLglPath . 'category',
               'config' => [
                   'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['', 0],
                    ],
                    'foreign_table' => 'tt_products_cat',
                    'foreign_table_where' => $whereCategory,
                    'default' => 0,
               ],
            ];

            $temporaryColumns['additional_type'] = [
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.additional_type',
               'config' => [
                   'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['', ''],
                    ],
                    'default' => null,
                    'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
               ],
            ];

            $temporaryColumns['delivery'] = [
                'exclude' => 1,
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.delivery',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.delivery.availableNot', '-1'],
                        ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.delivery.availableDemand', '0'],
                        ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.delivery.availableImmediate', '1'],
                        ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.delivery.availableShort', '2'],
                    ],
                    'size' => '6',
                    'minitems' => 0,
                    'maxitems' => 1,
                    'default' => 0,
                ],
            ];

        }

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            $table,
            $temporaryColumns
        );

        if (GeneralUtility::inList($taxFields, 'tax')) {
            // nothing
        } else {
            $GLOBALS['TCA'][$table]['types']['0'] = str_replace(', tax;', ', ' . $firstField . ';', $GLOBALS['TCA'][$table]['types']['0']);
            $key = array_search($firstField, $addFields);
            unset($addFields[$key]);
        }
        $newFields = implode(',', $addFields);

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            $table,
            $newFields,
            '',
            'replace:tax_dummy'
        );
    }

    switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['articleMode']) {
        case '1':
            $GLOBALS['TCA'][$table]['columns']['article_uid'] = [
                'exclude' => 1,
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.article_uid',
                'config' => [
                    'type' => 'group',
                    'allowed' => 'tt_products_articles',
                    'MM' => 'tt_products_products_mm_articles',
                    'foreign_table' => 'tt_products_articles',
                    'foreign_table_where' => ' ORDER BY tt_products_articles.title',
                    'size' => 10,
                    'selectedListStyle' => 'width:450px',
                    'minitems' => 0,
                    'maxitems' => 1000,
                    'default' => 0,
                ],
            ];
            break;
        case '2':
            // leave the settings of article_uid
            break;
        case '0':
        default:
            unset($GLOBALS['TCA'][$table]['columns']['article_uid']);
            $GLOBALS['TCA'][$table]['types']['0'] = str_replace(',article_uid,', ',', $GLOBALS['TCA'][$table]['types']['0']);
            break;
    }

    $orderBySortingTablesArray = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['orderBySortingTables']);

    $GLOBALS['TCA'][$table]['columns']['syscat'] = [
        'config' => [
            'type' => 'category',
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        'categories'
    );

    $palleteAddition = ',--palette--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_file_reference.shopAttributes;tt_productsPalette';
    // TODO.
    $palleteAddition = '';

    // nothing. This is the default behaviour

    if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['addressTable'])) {
        $GLOBALS['TCA'][$table]['columns']['address'] = [
            'exclude' => 1,
            'label' => $languageLglPath . 'address',
            'config' => [
                'type' => 'group',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['addressTable'],
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ];

        $newFields = 'address';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            $table,
            $newFields,
            '',
            'before:price'
        );
    }

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

    $GLOBALS['TCA'][$table]['columns']['slug']['config']['eval'] = $configuration->getSlugBehaviour();

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

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
}, 'tt_products', basename(__FILE__, '.php'));
