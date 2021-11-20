<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    $table = 'tt_products_language';
    $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);

    if (
        version_compare(TYPO3_version, '8.7.0', '<')
    ) {
        $fieldArray = array('tstamp', 'crdate', 'starttime', 'endtime');

        foreach ($fieldArray as $field) {
            unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
            $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
        }
    }

    $orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
    if (
        !empty($orderBySortingTablesArray) &&
        in_array($table, $orderBySortingTablesArray)
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
    }

    if (
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('filelist')
    ) {
        $palleteAddition = ',--palette--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:sys_file_reference.shopAttributes;tt_productsPalette';

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
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.smallimage',
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
            version_compare(TYPO3_version, '10.4.0', '>=')
        ) {
            $GLOBALS['TCA'][$table]['ctrl']['thumbnail'] = 'image_uid';    

            $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace('image, smallimage, datasheet', 'image_uid, smallimage_uid, datasheet_uid,', $GLOBALS['TCA'][$table]['types']['0']['showitem']);

            unset($GLOBALS['TCA'][$table]['columns']['image']);
            unset($GLOBALS['TCA'][$table]['columns']['smallimage']);
            unset($GLOBALS['TCA'][$table]['columns']['datasheet']);
        } else {
            if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fal']) {
                $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] .= ',image_uid,smallimage_uid';
                $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace('image, smallimage,', 'image, image_uid, smallimage, smallimage_uid,', $GLOBALS['TCA'][$table]['types']['0']['showitem']);
            }

            if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['falDatasheet']) {
                $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] .= ',datasheet_uid';
                $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace('datasheet', 'datasheet_uid', $GLOBALS['TCA'][$table]['types']['0']['showitem']);                
            } else {
                unset($GLOBALS['TCA'][$table]['columns']['datasheet_uid']);
            }
        }
    }

    if (
        defined('TYPO3_version') &&
        version_compare(TYPO3_version, '11.0.0', '<')
    ) {
        $GLOBALS['TCA'][$table]['columns']['sys_language_uid'] = [
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    [DIV2007_LANGUAGE_LGL . 'allLanguages', -1],
                    [DIV2007_LANGUAGE_LGL . 'default_value', 0]
                ],
                'default' => 0
            ]
        ];
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
    
    if (version_compare(TYPO3_version, '10.4.0', '<')) {
        $GLOBALS['TCA'][$table]['columns']['fe_group']['config']['enableMultiSelectFilterTextfield'] = true;
    }
});
