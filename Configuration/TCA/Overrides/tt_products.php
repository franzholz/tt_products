<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(function ($extensionKey, $table): void {
    $configuration = GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);
    $whereTaxCategory = '';
    $bSelectTaxMode = false;
    $extensionKeyStaticTaxes = 'static_info_tables_taxes';
    $languageSubpath = '/Resources/Private/Language/';
    $languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

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
                        ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.0', '0'],
                        ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.1', '1'],
                        ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.2', '2'],
                        ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.3', '3'],
                        ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.4', '4'],
                        ['LLL:EXT:' . $extensionKeyStaticTaxes . $languageSubpath . 'locallang_db.xlf:static_taxes.tx_rate_id.I.5', '5'],
                    ],
                    'default' => 0,
                ],
            ];

            $addFields[] = 'tax_id';
            if ($firstField == '') {
                $firstField = 'taxcat_id';
            }
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
