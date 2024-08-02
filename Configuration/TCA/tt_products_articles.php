<?php

defined('TYPO3') || die('Access denied.');

$extensionKey = 'tt_products';

// ******************************************************************
// This is the standard TypoScript products article table, tt_products_articles
// ******************************************************************

$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

return [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles',
        'label' => 'title',
        'hideTable' => true,
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
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/tt_products_articles.gif',
        'searchFields' => 'title,subtitle,itemnumber,keyword,note,note2',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tt_products_articles',
                'foreign_table_where' =>
                    'AND {#tt_products_articles}.{#pid}=###CURRENT_PID###'
                    . ' AND {#tt_products_articles}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
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
                'type' => 'datetime',
                'size' => '8',
                'default' => 0,
                'format' => 'date',
            ],
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'endtime',
            'config' => [
                'type' => 'datetime',
                'size' => '8',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['endtimeYear']),
                    'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y')),
                ],
                'format' => 'date',
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
                        'label' => $languageLglPath . 'hide_at_login',
                        'value' => -1,
                    ],
                    [
                        'label' => $languageLglPath . 'any_login',
                        'value' => -2,
                    ],
                    [
                        'label' => $languageLglPath . 'usergroups',
                        'value' => '--div--',
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
                'max' => '512',
                'default' => null,
                'nullable' => true,
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
                'default' => null,
                'nullable' => true,
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
                'type' => 'number',
                'size' => '12',
                'eval' => 'trim',
                'max' => '20',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'price2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.price2',
            'config' => [
                'type' => 'number',
                'size' => '12',
                'eval' => 'trim',
                'max' => '20',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'graduated_config_type' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.graduated_config_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => ''],
                ],
                'default' => null,
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] ?? 'explicitAllow',
            ],
        ],
        'graduated_config' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.graduated_config.addParentProductCount',
            'config' => [
                'type' => 'check',
                'default' => 1,
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
                'default' => null,
                'nullable' => true,
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
                    'showSynchronizationLink' => true,
                    'showAllLocalizationLink' => true,
                    'showPossibleLocalizationRecords' => true,
                ],
                'foreign_table' => 'tt_products_graduated_price',
                'foreign_field' => 'parentuid',
                'foreign_table_field' => 'parenttable',
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
                'type' => 'number',
                'size' => '6',
                'max' => '6',
                'default' => 1,
            ],
        ],
        'basketminquantity' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.basketminquantity',
            'config' => [
                'type' => 'number',
                'size' => '10',
                'eval' => 'trim',
                'max' => '10',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'basketmaxquantity' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.basketmaxquantity',
            'config' => [
                'type' => 'number',
                'size' => '10',
                'eval' => 'trim',
                'max' => '10',
                'default' => 0,
                'format' => 'decimal',
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
                'default' => null,
                'nullable' => true,
            ],
        ],
        'color2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.color2',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'color3' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.color3',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'size' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.size',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'size2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.size2',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'size3' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.size3',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.description',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'gradings' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.gradings',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'material' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.material',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'quality' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.quality',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'config_type' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.config_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => ''],
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
                'nullable' => true,
            ],
        ],
        'image_uid' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'image',
            'config' => [
                //## !!! Watch out for fieldName different from columnName
                'type' => 'file',
                'allowed' => 'common-image-types',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                    'collapseAll' => true,
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'smallimage_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.smallimage',
            'config' => [
                //## !!! Watch out for fieldName different from columnName
                'type' => 'file',
                'allowed' => 'common-image-types',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                    'collapseAll' => true,
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
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
            'showitem' => 'title,--palette--;;3, itemnumber, slug, inStock, basketminquantity,basketmaxquantity, price, --palette--;;2, weight, note,note2,image,smallimage, hidden,
                    --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                --palette--;;access, ' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.variants,color,color2,--palette--;;9,size,size2,--palette--;;10,description,gradings,material,quality,' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.graduated,graduated_config,graduated_price_enable,graduated_price_round,graduated_price_uid,',
            '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,--palette--;;language,'
        ],
    ],
    'palettes' => [
        '2' => ['showitem' => 'price2, config'],
        '3' => ['showitem' => 'subtitle, keyword'],
        '9' => ['showitem' => 'color3'],
        '10' => ['showitem' => 'size3'],
        'language' => [
            'showitem' => '
                sys_language_uid,l10n_parent,
            ',
        ],
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access',
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.starttime_formlabel, endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.endtime_formlabel, --linebreak--, fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.fe_group_formlabel, --linebreak--',
        ],
    ],
];
