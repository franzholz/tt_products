<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    $table = 'tt_products';
    $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);
    $bSelectTaxMode = false;

    if (
        version_compare(TYPO3_version, '8.7.0', '<')
    ) {
        $fieldArray = array('tstamp', 'crdate', 'starttime', 'endtime', 'usebydate', 'sellstarttime', 'sellendtime');

        foreach ($fieldArray as $field) {
            unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
            $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
        }
    }

    if (
        defined('STATIC_INFO_TABLES_TAXES_EXT') &&
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(STATIC_INFO_TABLES_TAXES_EXT)
    ) {
        $eInfo = tx_div2007_alpha5::getExtensionInfo_fh003(STATIC_INFO_TABLES_TAXES_EXT);

        if (is_array($eInfo)) {
            $sittVersion = $eInfo['version'];
            if (version_compare($sittVersion, '0.3.0', '>=')) {
                $bSelectTaxMode = true;
            }
        }
    }

    if ($bSelectTaxMode) {
        $whereTaxCategory = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('static_tax_categories');

        $temporaryColumns = array (
            'tax_id' => array (
                'exclude' => 0,
                'label' => 'LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xlf:static_taxes.tx_rate_id',
                'config' => array (
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => array (
                        array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xlf:static_taxes.tx_rate_id.I.0', '0'),
                        array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xlf:static_taxes.tx_rate_id.I.1', '1'),
                        array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xlf:static_taxes.tx_rate_id.I.2', '2'),
                        array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xlf:static_taxes.tx_rate_id.I.3', '3'),
                        array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xlf:static_taxes.tx_rate_id.I.4', '4'),
                        array('LLL:EXT:' . STATIC_INFO_TABLES_TAXES_EXT . '/locallang_db.xlf:static_taxes.tx_rate_id.I.5', '5'),
                    ),
                    'default' => 0
                )
            ),
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            $table,
            $temporaryColumns
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            $table,
            'tax_id',
            '',
            'replace:tax_dummy'
        );

        $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] = str_replace(',tax,', ',tax,tax_id,', $GLOBALS['TCA'][$table]['interface']['showRecordFieldList']);
    }

    switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode']) {
        case '1':
            $GLOBALS['TCA'][$table]['columns']['article_uid'] = array (
                'exclude' => 1,
                'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:tt_products.article_uid',
                'config' => array (
                    'type' => 'group',
                    'internal_type' => 'db',
                    'allowed' => 'tt_products_articles',
                    'MM' => 'tt_products_products_mm_articles',
                    'foreign_table' => 'tt_products_articles',
                    'foreign_table_where' => ' ORDER BY tt_products_articles.title',
                    'size' => 10,
                    'selectedListStyle' => 'width:450px',
                    'minitems' => 0,
                    'maxitems' => 1000,
                    'default' => 0
                )
            );
            break;
        case '2':
            // leave the settings of article_uid
            break;
        case '0':
        default:
            unset($GLOBALS['TCA'][$table]['columns']['article_uid']);
            // neu Anfang
            $result['types']['0'] = str_replace(',article_uid,', ',', $GLOBALS['TCA'][$table]['types']['0']);
            // neu Ende
            break;
    }

    $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] .= ',image_uid,smallimage_uid';

    $palleteAddition = ',--palette--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:sys_file_reference.shopAttributes;tt_productsPalette';

    $GLOBALS['TCA'][$table]['columns']['image_uid'] = [
        'exclude' => 1,
        'label' => DIV2007_LANGUAGE_LGL . 'image',
        'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
            'image_uid',
            [
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference',
                    'collapseAll' => true,
                ],
                'foreign_types' => [
                    '0' => [
                        'showitem' => '
                            --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                        'showitem' => '
                            --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition
                    ],
                ]
            ],
            $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
        )
    ];

    $GLOBALS['TCA'][$table]['columns']['smallimage_uid'] = [
        'exclude' => 1,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:tt_products.smallimage',
        'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
            'smallimage_uid',
            [
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference',
                    'collapseAll' => true,
                ],
                'foreign_types' => [
                    '0' => [
                        'showitem' => '
                            --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                        'showitem' => '
                            --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition
                    ],
                ]
            ],
            $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
        )
    ];

    if (
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['falDatasheet'] ||
        version_compare(TYPO3_version, '10.4.0', '>=')
    ) {
        $GLOBALS['TCA'][$table]['columns']['datasheet_uid'] = [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:tt_products.datasheet',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'datasheet_uid',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference',
                        'collapseAll' => true,
                    ],
                    'foreign_types' => [
                        '0' => [
                            'showitem' => '
                                --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                --palette--;;filePalette' . $palleteAddition
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                            'showitem' => '
                                --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                --palette--;;filePalette' . $palleteAddition
                        ],
                    ]
                ],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
            )
        ];
    }

    if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fal']) {
        $GLOBALS['TCA'][$table]['ctrl']['thumbnail'] = 'image_uid';

        $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace(',image,', ',image_uid,', $GLOBALS['TCA'][$table]['types']['0']['showitem']);
        $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace(',smallimage,', ',smallimage_uid,', $GLOBALS['TCA'][$table]['types']['0']['showitem']);

        unset($GLOBALS['TCA'][$table]['columns']['image']);
        unset($GLOBALS['TCA'][$table]['columns']['smallimage']);
    } else {
        $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace(',image,', ',image,image_uid,', $GLOBALS['TCA'][$table]['types']['0']['showitem']);
        $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace(',smallimage,', ',smallimage,smallimage_uid,', $GLOBALS['TCA'][$table]['types']['0']['showitem']);
    }

    if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['falDatasheet']) {
        unset($GLOBALS['TCA'][$table]['columns']['datasheet']);
        $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace(',datasheet,', ',datasheet_uid,', $GLOBALS['TCA'][$table]['types']['0']['showitem']);
        $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] .= ',datasheet_uid';
    }

    $addressTable = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addressTable'];

    if (!$addressTable) {
        $addressTable = 'fe_users';
    }

    $GLOBALS['TCA'][$table]['columns']['address'] = array (
        'exclude' => 1,
        'label' => DIV2007_LANGUAGE_LGL . 'address',
        'config' => array (
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => $addressTable,
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'default' => 0
        )
    );

    $newFields = 'address';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        $table,
        $newFields,
        '',
        'before:price'
    );

    $orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
    if (
        !empty($orderBySortingTablesArray) &&
        in_array($table, $orderBySortingTablesArray)
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
    }

    if (
        defined('TYPO3_version') &&
        version_compare(TYPO3_version, '7.0.0', '<')
    ) {
        $GLOBALS['TCA'][$table]['columns']['fe_group'] = array (
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'fe_group',
            'config' => array (
                'type' => 'select',
                'items' => array (
                    array('', 0),
                    array(DIV2007_LANGUAGE_LGL . 'hide_at_login', -1),
                    array(DIV2007_LANGUAGE_LGL . 'any_login', -2),
                    array(DIV2007_LANGUAGE_LGL . 'usergroups', '--div--')
                ),
                'foreign_table' => 'fe_groups',
                'default' => 0
            )
        );
    }

    $excludeArray =  
        (version_compare(TYPO3_version, '10.0.0', '>=') ? 
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude'] :
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.']
        );

    if (
        defined('TYPO3_version') &&
        version_compare(TYPO3_version, '9.0.0', '<')
    ) {
        $excludeArray[$table] .= ',slug';
    } else {
        $GLOBALS['TCA'][$table]['columns']['slug']['config']['eval'] = $configuration->getSlugBehaviour();    
    }

    if (
        isset($excludeArray) &&
        is_array($excludeArray) &&
        isset($excludeArray[$table])
    ) {
        \JambageCom\Div2007\Utility\TcaUtility::removeField(
            $GLOBALS['TCA'][$table],
            $excludeArray[$table]
        );
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
});

