<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    $table = 'tt_products_articles';
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

    switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode']) {
        case '0':
            $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] = str_replace(',subtitle,', ',subtitle,uid_product,', $GLOBALS['TCA'][$table]['interface']['showRecordFieldList']);

            $GLOBALS['TCA'][$table]['columns']['uid_product'] = array (
                'exclude' => 1,
                'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles.uid_product',
                'config' => array (
                    'type' => 'group',
                    'internal_type' => 'db',
                    'allowed' => 'tt_products',
                    'size' => 1,
                    'minitems' => 0,
                    'maxitems' => 1,
                    'default' => 0
                )
            );

            $GLOBALS['TCA'][$table]['types']['1']['showitem'] =
                str_replace(
                    'title,',
                    'uid_product,title,',
                    $GLOBALS['TCA'][$table]['types']['1']['showitem']
                );

            break;
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

    $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'] .= ',image_uid,smallimage_uid';

    $GLOBALS['TCA'][$table]['columns']['image_uid'] = array (
        'exclude' => 1,
        'label' => DIV2007_LANGUAGE_LGL . 'image',
        'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
            'image_uid',
            array(
                'appearance' => array(
                    'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference',
                    'collapseAll' => true,
                ),
                'foreign_types' => array(
                    '0' => array(
                        'showitem' => '
                            --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => array(
                        'showitem' => '
                            --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ),
                )
            ),
            $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
        )
    );

    $GLOBALS['TCA'][$table]['columns']['smallimage_uid'] = array (
        'exclude' => 1,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.smallimage',
        'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
            'smallimage_uid',
            array(
                'appearance' => array(
                    'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference',
                    'collapseAll' => true,
                ),
                'foreign_types' => array(
                    '0' => array(
                        'showitem' => '
                            --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => array(
                        'showitem' => '
                            --palette--;' . DIV2007_LANGUAGE_PATH . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                    ),
                )
            ),
            $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
        )
    );

    if (
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fal'] ||
        version_compare(TYPO3_version, '10.4.0', '>=')
    ) {
        $GLOBALS['TCA'][$table]['types']['1']['showitem'] = str_replace(',image,', ',image_uid,', $GLOBALS['TCA'][$table]['types']['1']['showitem']);
        $GLOBALS['TCA'][$table]['types']['1']['showitem'] = str_replace(',smallimage', ',smallimage_uid', $GLOBALS['TCA'][$table]['types']['1']['showitem']);

        unset($GLOBALS['TCA'][$table]['columns']['image']);
        unset($GLOBALS['TCA'][$table]['columns']['smallimage']);
    } else {
        $GLOBALS['TCA'][$table]['types']['1']['showitem'] = str_replace(',image,', ',image,image_uid,', $GLOBALS['TCA'][$table]['types']['1']['showitem']);
        $GLOBALS['TCA'][$table]['types']['1']['showitem'] = str_replace(',smallimage,', ',smallimage,smallimage_uid,', $GLOBALS['TCA'][$table]['types']['1']['showitem']);
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
});
