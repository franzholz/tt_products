<?php
defined('TYPO3_MODE') || die('Access denied.');

$imageFolder = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'];
if (!$imageFolder) {
    $imageFolder = 'uploads/pics';
}


// ******************************************************************
// This is the standard TypoScript products article table, tt_products_articles
// ******************************************************************

$result = array (
    'ctrl' => array (
        'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_articles',
        'label' => 'title',
        'label_alt' => 'subtitle',
        'default_sortby' => 'ORDER BY title',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'enablecolumns' => array (
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ),
        'thumbnail' => 'image',
        'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_articles.gif',
        'dividers2tabs' => '1',
        'searchFields' => 'title,subtitle,itemnumber,keyword,note,note2',
    ),
    'columns' => array (
        't3ver_label' => array (
            'label'  => DIV2007_LANGUAGE_PATH . 'locallang_general.xml:LGL.versionLabel',
            'config' => array (
                'type' => 'input',
                'size' => '30',
                'max'  => '30',
                'default' => ''
            )
        ),
        'tstamp' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tstamp',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
                'default' => 0
            )
        ),
        'crdate' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:crdate',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
                'default' => 0
            )
        ),
        'sorting' => array (
            'config' => array (
                'type' => 'passthrough',
            )
        ),
        'hidden' => array (
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'hidden',
            'config' => array (
                'type' => 'check',
                'default' => 0
            )
        ),
        'starttime' => array (
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'starttime',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0
            )
        ),
        'endtime' => array (
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'endtime',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
                'range' => array (
                    'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['endtimeYear']),
                    'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
                )
            )
        ),
        'fe_group' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label'  => DIV2007_LANGUAGE_LGL . 'fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 7,
                'maxitems' => 20,
                'items' => [
                    [
                        DIV2007_LANGUAGE_LGL . 'hide_at_login',
                        -1
                    ],
                    [
                        DIV2007_LANGUAGE_LGL . 'any_login',
                        -2
                    ],
                    [
                        DIV2007_LANGUAGE_LGL . 'usergroups',
                        '--div--'
                    ]
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'default' => 0,
            ]
        ],
        'title' => array (
            'exclude' => 0,
            'label' => DIV2007_LANGUAGE_LGL . 'title',
            'config' => array (
                'type' => 'input',
                'size' => '40',
				'eval' => 'trim',
                'max' => '80',
                'default' => ''
            )
        ),
        'subtitle' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.subtitle',
            'config' => array (
                'type' => 'text',
                'rows' => '3',
                'cols' => '20',
				'eval' => null,
                'max' => '512',
                'default' => ''
            )
        ),
        'slug' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.slug',
            'config' => array (
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => array (
                    'fields' => array ('title', 'itemnumber'),
                    'fieldSeparator' => '_',
                    'prefixParentPageSlug' => false,
                    'replacements' => array (
                        '/' => '-',
                    ),
                ),
                'fallbackCharacter' => '-',
                'default' => ''
            )
        ),
        'keyword' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.keyword',
            'config' => array (
                'type' => 'text',
                'rows' => '5',
                'cols' => '20',
                'max' => '512',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'itemnumber' => array (
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.itemnumber',
            'config' => array (
                'type' => 'input',
                'size' => '40',
                'eval' => 'trim',
                'max' => '120',
                'default' => ''
            )
        ),
        'price' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.price',
            'config' => array (
                'type' => 'input',
                'size' => '12',
                'eval' => 'trim,double2',
                'max' => '20',
                'default' => 0
            )
        ),
        'price2' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.price2',
            'config' => array (
                'type' => 'input',
                'size' => '12',
                'eval' => 'trim,double2',
                'max' => '20',
                'default' => 0
            )
        ),
        'graduated_config_type' => array (
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_articles.graduated_config_type',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array (
                    array('', '')
                ),
                'default' => '',
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
                'default' => ''
            )
        ),
        'graduated_config' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_articles.graduated_config',
            'config' => array (
                'type' => 'flex',
                'ds_pointerField' => 'graduated_config_type',
                'ds' => array (
                    'default' => '
                        <T3DataStructure>
                            <ROOT>
                                <type>array</type>
                                <el>
                                <addParentProductCount>
                                    <TCEforms>
                                        <label>LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_articles.graduated_config.addParentProductCount</label>
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
                ),
            ),
        ),
        'graduated_price_enable' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.graduated_price_enable',
            'config' => array (
                'type' => 'check',
                'default' => 1
            )
        ),
        'graduated_price_round' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.graduated_price_round',
            'config' => array (
                'type' => 'input',
                'size' => '20',
                'max' => '20',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'graduated_price_uid' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.graduated_price_uid',
            'config' => array (
                'type' => 'inline',
                'appearance' =>
                    array (
                        'collapseAll' => true,
                        'newRecordLinkAddTitle' => true,
                        'useCombination' => true,
                    ),
                'foreign_table' => 'tt_products_attribute_mm_graduated_price',
                'foreign_field' => 'uid_local',
                'foreign_table_field' => 'tablenames',
                'foreign_sortby' => 'sorting_foreign',
                'foreign_label' => 'uid_foreign',
                'foreign_selector' => 'uid_foreign',
                'foreign_unique' => 'uid_foreign',
                'maxitems' => 12,
                'default' => 0
            ),
        ),
        'note' => array (
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.note',
            'config' => array (
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'default' => ''
            ),
            'l10n_mode' => 'prefixLangTitle',
        ),
        'note2' => array (
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.note2',
            'config' => array (
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'default' => ''
            ),
            'l10n_mode' => 'prefixLangTitle',
        ),
        'inStock' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.inStock',
            'config' => array (
                'type' => 'input',
                'size' => '6',
                'max' => '6',
                'eval' => 'int',
                'default' => 1
            )
        ),
        'basketminquantity' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.basketminquantity',
            'config' => array (
                'type' => 'input',
                'size' => '10',
                'eval' => 'trim,double2',
                'max' => '10',
                'default' => 0
            )
        ),
        'basketmaxquantity' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.basketmaxquantity',
            'config' => array (
                'type' => 'input',
                'size' => '10',
                'eval' => 'trim,double2',
                'max' => '10',
                'default' => 0
            )
        ),
        'weight' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.weight',
            'config' => array (
                'type' => 'input',
                'size' => '10',
                'max' => '20',
                'eval' => 'trim,JambageCom\\Div2007\\Hooks\\Evaluation\\Double6',
                'default' => 0
            )
        ),
        'color' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.color',
            'config' => array (
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => '',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'color2' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.color2',
            'config' => array (
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'color3' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.color3',
            'config' => array (
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'size' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.size',
            'config' => array (
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'size2' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.size2',
            'config' => array (
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'size3' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.size3',
            'config' => array (
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'description' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.description',
            'config' => array (
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'gradings' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.gradings',
            'config' => array (
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'material' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.material',
            'config' => array (
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'quality' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.quality',
            'config' => array (
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'config_type' => array (
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_articles.config_type',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array (
                    array('', '')
                ),
                'default' => '',
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
            )
        ),
        'config' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_articles.config',
            'config' => array (
                'type' => 'flex',
                'ds_pointerField' => 'config_type',
                'ds' => array (
                    'default' => '
                        <T3DataStructure>
                            <ROOT>
                                <type>array</type>
                                <el>
                                <isAddedPrice>
                                    <TCEforms>
                                        <label>LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_articles.config.isaddedprice</label>
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
                ),
                'eval' => 'null',
                'default' => ''
            ),
        ),
        'image' => array (
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'image',
            'config' => array (
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => 
                isset($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize']) ? $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] : 0,
                'uploadfolder' => $imageFolder,
                'size' => '3',
                'maxitems' => '10',
                'minitems' => '0',
                'eval' => 'null',
                'default' => ''
            )
        ),
        'smallimage' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.smallimage',
            'config' => array (
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => 
                isset($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize']) ? $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] : 0,
                'uploadfolder' => $imageFolder,
                'size' => '3',
                'maxitems' => '10',
                'minitems' => '0',
                'eval' => 'null',
                'default' => ''
            )
        ),
    ),
    'types' => array (
        '1' =>
            array(
                'columnsOverrides' => array(
                    'note' => array(
                        'config' => array(
                            'enableRichtext' => '1'
                        )
                    ),
                    'note2' => array(
                        'config' => array(
                            'enableRichtext' => '1'
                        )
                    )
                ),
                'showitem' => 'tstamp, crdate, hidden,--palette--;;1, title,--palette--;;3, itemnumber, slug, inStock, basketminquantity,basketmaxquantity, price,--palette--;;2, weight, note,note2,image,smallimage,' .
                '--div--;LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.variants,color,color2,--palette--;;9,size,size2,--palette--;;10,description,gradings,material,quality,' .
                '--div--;LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.graduated,graduated_config,graduated_price_enable,graduated_price_round,graduated_price_uid,'
            )
    ),
    'palettes' => array (
        '1' =>
            array('showitem' => 'starttime, endtime, fe_group'),
        '2' =>
            array('showitem' => 'price2, config'),
        '3' =>
            array('showitem' => 'subtitle, keyword'),
        '9' =>
            array('showitem' => 'color3'),
        '10' =>
            array('showitem' => 'size3'),
    )
);

if (
    defined('TYPO3_version') &&
    version_compare(TYPO3_version, '10.0.0', '<')
) {
    $result['interface'] = [];
    $result['interface']['showRecordFieldList'] =   
        'hidden,starttime,endtime,fe_group,title,subtitle,keyword,itemnumber,price,price2,graduated_price_enable,graduated_price_round,graduated_price_uid,weight,inStock,basketminquantity,basketmaxquantity,color,color2,color3,size,size2,size3,description,gradings,material,quality,note,note2,image,smallimage';
}

return $result;
