<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function($extensionKey, $table)
{
    $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);
    $languageSubpath = '/Resources/Private/Language/';

    $fieldArray = ['tstamp', 'crdate', 'starttime', 'endtime'];

    foreach ($fieldArray as $field) {
        unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
    }

    switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['articleMode']) {
        case '0':
            $GLOBALS['TCA'][$table]['columns']['uid_product'] = [
                'exclude' => 1,
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.uid_product',
                'config' => [
                    'type' => 'group',
                    'internal_type' => 'db',
                    'allowed' => 'tt_products',
                    'size' => 1,
                    'minitems' => 0,
                    'maxitems' => 1,
                ]
            ];

            $GLOBALS['TCA'][$table]['types']['1']['showitem'] =
                str_replace(
                    'title,',
                    'uid_product,title,',
                    $GLOBALS['TCA'][$table]['types']['1']['showitem']
                );

            break;
    }


    $orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['orderBySortingTables']);
    if (
        !empty($orderBySortingTablesArray) &&
        in_array($table, $orderBySortingTablesArray)
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
        $GLOBALS['TCA'][$table]['columns']['sorting'] = 
            [
                'config' => [
                    'type' => 'passthrough',
                    'default' => 0
                ]
            ];
    }

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

    $GLOBALS['TCA'][$table]['columns']['smallimage_uid'] = [
        'exclude' => 1,
        'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.smallimage',
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

    $GLOBALS['TCA'][$table]['ctrl']['thumbnail'] = 'image_uid';    

    $GLOBALS['TCA'][$table]['types']['1']['showitem'] = str_replace(',image,', ',image_uid,', $GLOBALS['TCA'][$table]['types']['1']['showitem']);
    $GLOBALS['TCA'][$table]['types']['1']['showitem'] = str_replace(',smallimage,', ',smallimage_uid,', $GLOBALS['TCA'][$table]['types']['1']['showitem']);

    unset($GLOBALS['TCA'][$table]['columns']['image']);
    unset($GLOBALS['TCA'][$table]['columns']['smallimage']);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
    
    $GLOBALS['TCA'][$table]['columns']['slug']['config']['eval'] = $configuration->getSlugBehaviour();

    $excludeArray =  
        ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['exclude']);

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
