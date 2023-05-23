<?php
defined('TYPO3') || die('Access denied.');

$whereCategory =
    (
        isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where']['category'])
        ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where']['category']
        : ''
    );

$imageFolder = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'];
if (!$imageFolder) {
	$imageFolder = 'uploads/pics';
}

$palleteAddition = '';
$languageSubpath = '/Resources/Private/Language/';


$result = [
	'ctrl' => [
		'title' =>'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products',
		'label' => 'title',
		'label_alt' => 'subtitle',
		'default_sortby' => 'ORDER BY title',
		'tstamp' => 'tstamp',
		'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => true,
		'origUid' => 't3_origuid',
		'delete' => 'deleted',
		'enablecolumns' => [
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		],
		'thumbnail' => 'image',
		'useColumnsForDefaultValues' => 'category',
		'mainpalette' => 1,
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products.gif',
		'dividers2tabs' => '1',
		'searchFields' => 'uid,title,subtitle,itemnumber,ean,note,note2,www',
	],
	'columns' => [
		'hidden' => [
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'hidden',
			'config' => [
				'type' => 'check',
				'default' => 0
			]
		],
		'tstamp' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tstamp',
			'config' => [
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => 0
			]
		],
		'crdate' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:crdate',
			'config' => [
				'type' => 'input',
				'size' => '8',
				'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
				'default' => 0
			]
		],
		'starttime' => [
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'starttime',
			'config' => [
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'checkbox' => '0',
				'default' => 0
			]
		],
		'endtime' => [
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'endtime',
			'config' => [
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'checkbox' => '0',
				'default' => 0,
				'range' => [
					'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['endtimeYear']),
					'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
				]
			]
		],
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
		'title' => [
			'exclude' => 0,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.title',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '256',
				'default' => '',
			]
		],
		'subtitle' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.subtitle',
			'config' => [
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'eval' => null,
				'max' => '512',
				'default' => '',
			]
		],
        'slug' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.slug',
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
                'default' => ''
            ]
        ],
		'keyword' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.keyword',
			'config' => [
				'type' => 'text',
				'rows' => '5',
				'cols' => '20',
				'max' => '512',
				'eval' => 'null',
				'default' => '',
			]
		],
		'prod_uid' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.prod_uid',
			'config' => [
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => ' ORDER BY tt_products.uid',
				'size' => 3,
				'minitems' => 0,
				'maxitems' => 3,
				'default' => 0
			]
		],
		'itemnumber' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.itemnumber',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '120',
				'default' => ''
			]
		],
		'ean' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.ean',
			'config' => [
				'type' => 'input',
				'size' => '48',
				'eval' => 'trim',
				'max' => '48',
				'default' => ''
			]
		],
		'shipping_point' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.shipping_point',
			'config' => [
				'type' => 'input',
				'size' => '24',
				'eval' => 'trim',
				'max' => '24',
				'default' => ''
			]
		],
		'price' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.price',
			'config' => [
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim,double2',
				'max' => '20',
				'default' => 0
			]
		],
		'price2' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.price2',
			'config' => [
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim,double2',
				'max' => '20',
				'default' => 0
			]
		],
		'discount' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.discount',
			'config' => [
				'type' => 'input',
				'size' => '4',
				'max' => '8',
				'eval' => 'trim,double2',
				'range' => [
					'upper' => '1000',
					'lower' => '0'
				],
				'default' => 0
			]
		],
		'discount_disable' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.discount_disable',
			'config' => [
				'type' => 'check',
				'default' => 0
			]
		],
		'tax' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.tax',
			'config' => [
				'type' => 'input',
				'size' => '12',
				'max' => '19',
				'eval' => 'trim,double2',
				'default' => 0
			]
		],
		'creditpoints' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.creditpoints',
			'config' => [
				'type' => 'input',
				'size' => '12',
				'eval' => 'int',
				'max' => '12',
				'default' => 0
			]
		],
		'deposit' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.deposit',
			'config' => [
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim,double2',
				'max' => '20',
				'default' => 0
			]
		],
		'graduated_price_enable' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.graduated_price_enable',
			'config' => [
				'type' => 'check',
				'default' => '1'
			]
		],
		'graduated_price_round' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.graduated_price_round',
			'config' => [
				'type' => 'input',
				'size' => '20',
				'max' => '20',
				'eval' => 'null',
				'default' => '',
			]
		],
        'graduated_price_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.graduated_price_uid',
            'config' => [
                'type' => 'inline',
                'appearance' =>
                    [
                        'collapseAll' => true,
                        'newRecordLinkAddTitle' => true,
                        'useCombination' => true
                    ],
                'MM' => 'tt_products_mm_graduated_price',
                'foreign_table' => 'tt_products_graduated_price',
                'foreign_sortby' => 'sorting',
                'maxitems' => 12,
                'default' => 0
            ],
        ],
		'article_uid' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.article_uid',
			'config' => [
				'type' => 'inline',
				'appearance' =>
					[
						'collapseAll' => true,
						'newRecordLinkAddTitle' => true,
						'useCombination' => true
					],
				'foreign_table' => 'tt_products_products_mm_articles',
				'foreign_field' => 'uid_local',
				'foreign_sortby' => 'sorting',
				'foreign_label' => 'uid_foreign',
				'foreign_selector' => 'uid_foreign',
				'foreign_unique' => 'uid_foreign',
				'maxitems' => 1000,
				'default' => 0
			],
		],
		'note' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.note',
			'config' => [
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'default' => '',
			]
		],
		'note2' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.note2',
			'config' => [
				'type' => 'text',
				'cols' => '48',
				'rows' => '2',
				'default' => '',
			]
		],
		'note_uid' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.note_uid',
			'config' => [
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'MM' => 'tt_products_products_note_pages_mm',
				'size' => '2',
				'autoSizeMax' => '12',
				'minitems' => '0',
				'maxitems' => '30',
				'default' => 0
			],
		],
		'text_uid' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.text_uid',
			'config' => [
				'type' => 'inline',
				'foreign_table' => 'tt_products_texts',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 20,
				'default' => 0
			],
		],
		'download_type' => [
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.download_type',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => [
					['', '']
				],
				'default' => '',
				'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
			]
		],
		'download_info' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.download_info',
			'config' => [
				'type' => 'flex',
				'ds_pointerField' => 'download_type',
				'ds' => [
					'default' => '
						<T3DataStructure>
							<ROOT>
								<type>array</type>
								<el>
								<limitedToDomain>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.download_info.limitedToDomain</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</limitedToDomain>
								</el>
							</ROOT>
							<meta>
								<langDisable>1</langDisable>
							</meta>
						</T3DataStructure>
						',
				],
				'eval' => 'null',
			]
		],
		'download_uid' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.download_uid',
			'config' => [
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products_downloads',
				'MM' => 'tt_products_products_mm_downloads',
				'foreign_table' => 'tt_products_downloads',
				'foreign_table_where' => ' ORDER BY tt_products_downloads.title',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 1000,
				'default' => 0
			],
		],
		'unit_factor' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.unit_factor',
			'config' => [
				'type' => 'input',
				'size' => '10',
				'eval' => 'double',
				'default' => 1,
				'max' => '6'
			]
		],
		'unit' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.unit',
			'config' => [
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '20',
				'default' => ''
			]
		],
		'www' => [
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'www',
			'config' => [
				'type' => 'input',
				'eval' => 'trim',
				'size' => '30',
				'max' => '160',
				'default' => ''
			]
		],
		'category' => [
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'category',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => [
					['', 0]
				],
				'foreign_table' => 'tt_products_cat',
				'foreign_table_where' => $whereCategory,
				'default' => 0
			]
		],
		'inStock' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.inStock',
			'config' => [
				'type' => 'input',
				'size' => '6',
				'max' => '6',
				'eval' => 'int',
				'default' => 1
			]
		],
		'basketminquantity' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.basketminquantity',
			'config' => [
				'type' => 'input',
				'size' => '10',
				'eval' => 'trim,double2',
				'max' => '10',
				'default' => 0
			]
		],
		'basketmaxquantity' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.basketmaxquantity',
			'config' => [
				'type' => 'input',
				'size' => '10',
				'eval' => 'trim,double2',
				'max' => '10',
				'default' => 0
			]
		],
        'image_uid' => [
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
        ],
        'smallimage_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.smallimage',
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
        ],
        'datasheet_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.datasheet',
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
        ],
		'weight' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.weight',
			'config' => [
				'type' => 'input',
				'size' => '10',
				'max' => '20',
                'eval' => 'trim,JambageCom\\Div2007\\Hooks\\Evaluation\\Double6',
                'default' => 0
			]
		],
		'usebydate' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.usebydate',
			'config' => [
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => 0
			]
		],
		'bulkily' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.bulkily',
			'config' => [
				'type' => 'check',
				'default' => 0
			]
		],
		'offer' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.offer',
			'config' => [
				'type' => 'check',
				'default' => 0
			]
		],
		'highlight' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.highlight',
			'config' => [
				'type' => 'check',
				'default' => 0
			]
		],
		'bargain' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.bargain',
			'config' => [
				'type' => 'check',
				'default' => 0
			]
		],
		'directcost' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.directcost',
			'config' => [
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20',
				'default' => 0
			]
		],
		'accessory_uid' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.accessory_uid',
			'config' => [
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'MM' => 'tt_products_accessory_products_products_mm',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => ' ORDER BY tt_products.uid',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 12,
				'default' => 0
			],
		],
		'related_uid' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.related_uid',
			'config' => [
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'MM' => 'tt_products_related_products_products_mm',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => ' ORDER BY tt_products.uid',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 50,
				'default' => 0
			],
		],
		'color' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.color',
			'config' => [
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			]
		],
		'color2' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.color2',
			'config' => [
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			]
		],
		'color3' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.color3',
			'config' => [
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			]
		],
		'size' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.size',
			'config' => [
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			]
		],
		'size2' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.size2',
			'config' => [
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			]
		],
		'size3' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.size3',
			'config' => [
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			]
		],
		'description' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.description',
			'config' => [
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			]
		],
		'gradings' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.gradings',
			'config' => [
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			]
		],
		'material' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.material',
			'config' => [
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			]
		],
		'quality' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.quality',
			'config' => [
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			]
		],
		'additional_type' => [
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.additional_type',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => [
					['', '']
				],
				'default' => '',
				'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
			]
		],
		'additional' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.additional',
			'config' => [
				'type' => 'flex',
				'ds_pointerField' => 'additional_type',
				'ds' => [
					'default' => '
						<T3DataStructure>
							<ROOT>
								<type>array</type>
								<el>
								<isSingle>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.additional.isSingle</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</isSingle>
								<isImage>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.additional.isImage</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</isImage>
								<alwaysInStock>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.additional.alwaysInStock</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</alwaysInStock>
								<noMinPrice>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.additional.noMinPrice</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</noMinPrice>
								<noMaxPrice>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.additional.noMaxPrice</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</noMaxPrice>
								<noGiftService>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.additional.noGiftService</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</noGiftService>
								</el>
							</ROOT>
							<meta>
								<langDisable>1</langDisable>
							</meta>
						</T3DataStructure>
						',
				],
				'eval' => 'null',
			]
		],
		'special_preparation' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.special_preparation',
			'config' => [
				'type' => 'check',
				'default' => 0
			]
		],
		'shipping' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.shipping',
			'config' => [
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,double2',
				'default' => 0
			]
		],
		'shipping2' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.shipping2',
			'config' => [
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,double2',
				'default' => 0
			]
		],
		'handling' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.handling',
			'config' => [
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,double2',
				'default' => 0
			]
		],
		'delivery' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.delivery',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => [
					['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.delivery.availableNot', '-1'],
					['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.delivery.availableDemand', '0'],
					['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.delivery.availableImmediate', '1'],
					['LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.delivery.availableShort', '2']
				],
				'size' => '6',
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 0
			]
		],
		'sellstarttime' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.sellstarttime',
			'config' => [
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => 0
			]
		],
		'sellendtime' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.sellendtime',
			'config' => [
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => 0,
				'range' => [
					'upper' => mktime(0, 0, 0, 12, 31, 2300),
					'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
				]
			]
		],
	],
	'types' => [
		'0' =>
            [
                'columnsOverrides' => [
                    'note' => [
                        'config' => [
                            'enableRichtext' => '1'
                        ]
                    ],
                    'note2' => [
                        'config' => [
                            'enableRichtext' => '1'
                        ]
                    ]
                ],

                'showitem' => 'title,--palette--;;7, itemnumber,--palette--;;2, slug, price,--palette--;;3, tax,--palette--;;4, deposit,--palette--;;5,offer,--palette--;;6,weight,--palette--;;8,tstamp, crdate, hidden,--palette--;;1,' .
                    '--div--;LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.descriptions,note, note2,note_uid,text_uid,image_uid,smallimage_uid,datasheet_uid,'.
                    '--div--;LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.categorydiv, category, syscat,' .
                    '--div--;LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.variants,color,color2,--palette--;;9,size,size2,--palette--;;10,description,gradings,material,quality,--palette--;;,additional,--palette--;;11,'.
                    '--div--;LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.graduated,graduated_price_enable,graduated_price_round,graduated_price_uid,'.
                    '--div--;LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.relations,article_uid,related_uid,accessory_uid,download_info,download_uid,'.
                    '--div--;LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf:tt_products.shippingdiv,shipping_point,shipping,shipping2,handling,delivery,'

            ]
	],
	'palettes' => [
		'1' =>
			['showitem' => 'sellstarttime,sellendtime,starttime,endtime,fe_group'],
		'2' =>
			['showitem' => 'inStock,basketminquantity,basketmaxquantity,ean'],
		'3' =>
			['showitem' => 'price2,discount,discount_disable,directcost'],
		'4' =>
			['showitem' => 'tax_dummy'],
		'5' =>
			['showitem' => 'creditpoints'],
		'6' =>
			['showitem' => 'highlight,bargain'],
		'7' =>
			['showitem' => 'subtitle,keyword,www'],
		'8' =>
			['showitem' => 'bulkily,special_preparation,unit,unit_factor'],
		'9' =>
			['showitem' => 'color3'],
		'10' =>
			['showitem' => 'size3'],
		'11' =>
			['showitem' => 'usebydate']
	]
];


return $result;

