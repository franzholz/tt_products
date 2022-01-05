<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    $table = 'tt_products_cat';
    $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);

    $orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
    if (
        !empty($orderBySortingTablesArray) &&
        in_array($table, $orderBySortingTablesArray)
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
    }

    $excludeArray =  
        (version_compare(TYPO3_version, '10.0.0', '>=') ? 
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude'] :
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.']
        );

    $GLOBALS['TCA'][$table]['columns']['slug']['config']['eval'] = $configuration->getSlugBehaviour();

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
        defined('TYPO3_version') &&
        version_compare(TYPO3_version, '10.0.0', '<')
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['interface']['showRecordFieldList'] .= ',image_uid';
    }


    $GLOBALS['TCA'][$table]['ctrl']['interface']['showRecordFieldList'] .= ',image_uid,sliderimage_uid';

    $fieldArray = ['image', 'sliderimage'];
    foreach ($fieldArray as $field) {
        $uidField = $field . '_uid';
        $GLOBALS['TCA'][$table]['columns'][$uidField] = [
            'exclude' => 1,
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                $uidField,
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference',
                        'collapseAll' => true,
                    ],
                    'foreign_types' => [
                        '0' => [
                            'showitem' => '
                                --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                --palette--;;filePalette'
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '
                                --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                --palette--;;filePalette'
                        ],
                    ]
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            )
        ];

        if ($field == 'image') {
            $GLOBALS['TCA'][$table]['columns'][$uidField]['label'] = DIV2007_LANGUAGE_LGL . $field;
        } else {
            $GLOBALS['TCA'][$table]['columns'][$uidField]['label'] = 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_cat.' . $field;
        }

        if (
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fal'] ||
            version_compare(TYPO3_version, '10.4.0', '>=')
        ) {
            $GLOBALS['TCA'][$table]['ctrl']['thumbnail'] = 'image_uid';    

            $search = ', ' . $field . ',';
            $replace = ', ' . $uidField . ',';
            $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace($search, $replace, $GLOBALS['TCA'][$table]['types']['0']['showitem']);

            unset($GLOBALS['TCA'][$table]['columns'][$field]);
        } else {
            $search = ', ' . $field . ',';
            $replace = $search . ' ' . $uidField . ',';

            $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace($search, $replace, $GLOBALS['TCA'][$table]['types']['0']['showitem']);
        }        
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);

    if (version_compare(TYPO3_version, '10.4.0', '<')) {
        $GLOBALS['TCA'][$table]['columns']['fe_group']['config']['enableMultiSelectFilterTextfield'] = true;
    }
});

