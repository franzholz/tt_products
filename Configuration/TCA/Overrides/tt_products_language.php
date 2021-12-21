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

    if (version_compare(TYPO3_version, '7.6.0', '>=')) {

        unset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']);
        unset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable']);
    }

    if (
        tx_div2007_core::compat_version('6.2') &&
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('filelist')
    ) {
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
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fal']
        ) {
            $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] .= ',image_uid,smallimage_uid,datasheet_uid';
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

    if (
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fal']
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['thumbnail'] = 'image_uid';    

        $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace('image,', 'image_uid,', $GLOBALS['TCA'][$table]['types']['0']['showitem']);

        unset($GLOBALS['TCA'][$table]['columns']['image']);
    } else {
        $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace('image,', 'image, image_uid,', $GLOBALS['TCA'][$table]['types']['0']['showitem']);
    }

    if (version_compare(TYPO3_version, '7.6.0', '>=')) {

        unset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']);
        unset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable']);
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
});
