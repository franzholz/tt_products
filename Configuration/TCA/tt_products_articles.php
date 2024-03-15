<?php

defined('TYPO3') || die('Access denied.');

$extensionKey = 'tt_products';
$imageFolder = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['imageFolder'];
if (!$imageFolder) {
    $imageFolder = 'uploads/pics';
}

// ******************************************************************
// This is the standard TypoScript products article table, tt_products_articles
// ******************************************************************

$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles',
        'label' => 'title',
        'label_alt' => 'subtitle',
        'default_sortby' => 'ORDER BY title',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'thumbnail' => 'image_uid', // supported until TYPO3 10: breaking #92118
        'prependAtCopy' => $languageLglPath . 'prependAtCopy',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/tt_products_articles.gif',
        'searchFields' => 'title,subtitle,itemnumber,keyword,note,note2',
    ],
    'columns' => [
        'tstamp' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tstamp',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
                'default' => 0,
            ],
        ],
        'crdate' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:crdate',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
                'default' => 0,
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'starttime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'endtime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['endtimeYear']),
                    'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y')),
                ],
            ],
        ],
        'fe_group' => [
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
        ],
        'title' => [
            'exclude' => 0,
            'label' => $languageLglPath . 'title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'eval' => 'trim',
                'max' => '80',
                'default' => null,
            ],
        ],
        'subtitle' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.subtitle',
            'config' => [
                'type' => 'text',
                'rows' => '3',
                'cols' => '20',
                'eval' => null,
                'max' => '512',
                'default' => null,
            ],
        ],
        'slug' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['title', 'itemnumber'],
                    'fieldSeparator' => '_',
                    'prefixParentPageSlug' => false,
                    'replacements' => [
                        '/' => '-',
                    ],
                ],
                'fallbackCharacter' => '-',
                'default' => null,
            ],
        ],
        'keyword' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.keyword',
            'config' => [
                'type' => 'text',
                'rows' => '5',
                'cols' => '20',
                'max' => '512',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'itemnumber' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.itemnumber',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'eval' => 'trim',
                'max' => '120',
                'default' => null,
            ],
        ],
        'price' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.price',
            'config' => [
                'type' => 'input',
                'size' => '12',
                'eval' => 'trim,double2',
                'max' => '20',
                'default' => 0,
            ],
        ],
        'price2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.price2',
            'config' => [
                'type' => 'input',
                'size' => '12',
                'eval' => 'trim,double2',
                'max' => '20',
                'default' => 0,
            ],
        ],
        'graduated_config_type' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.graduated_config_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', ''],
                ],
                'default' => null,
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] ?? 'explicitAllow',
                'default' => '',
            ],
        ],
        'graduated_config' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.graduated_config',
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => 'graduated_config_type',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <ROOT>
                                <type>array</type>
                                <el>
                                <addParentProductCount>
                                    <TCEforms>
                                        <label>LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.graduated_config.addParentProductCount</label>
                                        <config>
                                            <type>check</type>
                                            <default>1</default>
                                        </config>
                                    </TCEforms>
                                </addParentProductCount>
                                </el>
                            </ROOT>
                            <meta>
                                <langDisable>1</langDisable>
                            </meta>
                        </T3DataStructure>
                        ',
                ],
                'eval' => 'null',
            ],
        ],
        'graduated_price_enable' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.graduated_price_enable',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'graduated_price_round' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.graduated_price_round',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '20',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'graduated_price_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.graduated_price_uid',
            'config' => [
                'type' => 'inline',
                'appearance' => [
                        'collapseAll' => true,
                        'newRecordLinkAddTitle' => true,
                        'useCombination' => true,
                    ],
                'foreign_table' => 'tt_products_attribute_mm_graduated_price',
                'foreign_field' => 'uid_local',
                'foreign_table_field' => 'tablenames',
                'foreign_sortby' => 'sorting_foreign',
                'foreign_label' => 'uid_foreign',
                'foreign_selector' => 'uid_foreign',
                'foreign_unique' => 'uid_foreign',
                'maxitems' => 12,
                'default' => 0,
            ],
        ],
        'note' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.note',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'default' => null,
            ],
            'l10n_mode' => 'prefixLangTitle',
        ],
        'note2' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.note2',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'default' => null,
            ],
            'l10n_mode' => 'prefixLangTitle',
        ],
        'inStock' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.inStock',
            'config' => [
                'type' => 'input',
                'size' => '6',
                'max' => '6',
                'eval' => 'int',
                'default' => 1,
            ],
        ],
        'basketminquantity' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.basketminquantity',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'trim,double2',
                'max' => '10',
                'default' => 0,
            ],
        ],
        'basketmaxquantity' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.basketmaxquantity',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'trim,double2',
                'max' => '10',
                'default' => 0,
            ],
        ],
        'weight' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.weight',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '20',
                'eval' => 'trim,JambageCom\\Div2007\\Hooks\\Evaluation\\Double6',
                'default' => 0,
            ],
        ],
        'color' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.color',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'color2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.color2',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'color3' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.color3',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'size' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.size',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'size2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.size2',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'size3' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.size3',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.description',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'gradings' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.gradings',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'material' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.material',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'quality' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.quality',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => null,
            ],
        ],
        'config_type' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.config_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', ''],
                ],
                'default' => '',
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] ?? 'explicitAllow',
            ],
        ],
        'config' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.config',
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => 'config_type',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <ROOT>
                                <type>array</type>
                                <el>
                                <isAddedPrice>
                                    <TCEforms>
                                        <label>LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.config.isaddedprice</label>
                                        <config>
                                            <type>check</type>
                                        </config>
                                    </TCEforms>
                                </isAddedPrice>
                                </el>
                            </ROOT>
                            <meta>
                                <langDisable>1</langDisable>
                            </meta>
                        </T3DataStructure>
                        ',
                ],
                'eval' => 'null',
            ],
        ],
        'image_uid' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'image',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'image_uid',
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
                            --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath .
                            'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette',
                        ],
                    ],
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            ),
        ],
        'smallimage_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.smallimage',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
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
                            --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath .
                            'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette',
                        ],
                    ],
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            ),
        ],
    ],
    'types' => [
        '1' => [
                'columnsOverrides' => [
                    'note' => [
                        'config' => [
                            'enableRichtext' => '1',
                        ],
                    ],
                    'note2' => [
                        'config' => [
                            'enableRichtext' => '1',
                        ],
                    ],
                ],
                'showitem' => 'tstamp, crdate, title,--palette--;;3, itemnumber, slug, inStock, basketminquantity,basketmaxquantity, price, --palette--;;2, weight, note,note2,image,smallimage, hidden,
                    --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                --palette--;;access, ' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.variants,color,color2,--palette--;;9,size,size2,--palette--;;10,description,gradings,material,quality,' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.graduated,graduated_config,graduated_price_enable,graduated_price_round,graduated_price_uid,',
            ],
    ],
    'palettes' => [
        '2' => ['showitem' => 'price2, config'],
        '3' => ['showitem' => 'subtitle, keyword'],
        '9' => ['showitem' => 'color3'],
        '10' => ['showitem' => 'size3'],
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access',
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.starttime_formlabel, endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.endtime_formlabel, --linebreak--, fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.fe_group_formlabel, --linebreak--',
        ],
    ],
];

return $result;
