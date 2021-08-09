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
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles',
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
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_articles.gif',
		'dividers2tabs' => '1',
		'searchFields' => 'title,subtitle,itemnumber,keyword,note,note2',
	),
	'interface' => array (
		'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title,subtitle,keyword,itemnumber,price,price2,weight,inStock,basketminquantity,color,color2,color3,size,size2,size3,description,gradings,material,quality,note,note2,image,smallimage'
	),
	'columns' => array (
		't3ver_label' => array (
			'label'  => DIV2007_LANGUAGE_PATH . 'locallang_general.xlf:LGL.versionLabel',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '30',
				'default' => ''
			)
		),
		'tstamp' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
				'default' => 0
			)
		),
		'sorting' => Array (
			'config' => Array (
				'type' => 'passthrough',
				'default' => 0
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
                'enableMultiSelectFilterTextfield' => true,
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.subtitle',
			'config' => array (
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'eval' => 'trim',
				'max' => '512',
				'default' => ''
			)
		),
        'slug' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.slug',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.keyword',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.itemnumber',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.price',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.price2',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20',
				'default' => 0
			)
		),
		'note' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'default' => ''
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'note2' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note2',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.inStock',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.basketminquantity',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.weight',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color2',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color3',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size2',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size3',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.description',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.gradings',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.material',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.quality',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => ''
			)
		),
        'config_type' => array (
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles.config_type',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles.config',
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
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles.config.isaddedprice</label>
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
			)
		),
		'image' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'image',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => $imageFolder,
				'size' => '3',
				'maxitems' => '10',
				'minitems' => '0',
				'eval' => 'null',
				'default' => ''
			)
		),
		'smallimage' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.smallimage',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
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

                'showitem' => 'hidden, tstamp, crdate,--palette--;;1, title,--palette--;;3, itemnumber, slug, inStock, basketminquantity, price,--palette--;;2;;, weight, color, color2, color3, size, size2, size3, description, gradings, material, quality, note,note2,image,smallimage'
            )
    ),
    'palettes' => array (
        '1' => array('showitem' => 'starttime, endtime, fe_group'),
        '2' => array('showitem' => 'price2, config'),
        '3' => array('showitem' => 'subtitle, keyword'),
    )
);

if (
    version_compare(TYPO3_version, '8.5.0', '<')
) {
    $result['types']['1']['showitem'] =
        preg_replace(
            '/(^|,)\s*note\s*(,|$)/', '$1 note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/] $2',
            $result['types']['1']['showitem']
        );

    $result['types']['1']['showitem'] =
        preg_replace(
            '/(^|,)\s*note2\s*(,|$)/', '$1 note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/] $2',
            $result['types']['1']['showitem']
        );
}

return $result;

