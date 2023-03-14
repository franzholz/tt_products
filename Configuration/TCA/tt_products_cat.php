<?php
defined('TYPO3') || die('Access denied.');

$imageFolder = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'];
if (!$imageFolder) {
	$imageFolder = 'uploads/pics';
}

// ******************************************************************
// This is the standard TypoScript products category table, tt_products_cat
// ******************************************************************
$result = [
	'ctrl' => [
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_cat',
		'label' => 'title',
		'label_alt' => 'subtitle',
		'default_sortby' =>' ORDER BY title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'enablecolumns' => [
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		],
		'thumbnail' => 'image',
		'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => true,
		'origUid' => 't3_origuid',
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_cat.gif',
		'searchFields' => 'uid,title,subtitle,catid,keyword,note,note2',
	],
	'columns' => [
		'sorting' => [
			'config' => [
				'type' => 'passthrough',
				'default' => 0
			]
		],
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tstamp',
			'config' => [
				'type' => 'input',
				'size' => '8',
				'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
				'default' => 0
			]
		],
		'crdate' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:crdate',
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
				'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
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
			'label' => DIV2007_LANGUAGE_LGL . 'title',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.subtitle',
			'config' => [
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'max' => '512',
				'default' => '',
			]
		],
        'slug' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['title', 'catid'],
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
		'parent_category' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_cat.parent_category',
			'config' => [
				'minitems' => 0,
				'maxitems' => 1,
				'type' => 'select',
                'renderType' => 'selectTree',
				'foreign_table' => 'tt_products_cat',
				'foreign_table_where' => ' ORDER BY tt_products_cat.title',
				'treeConfig' => [
					'parentField' => 'parent_category',
					'appearance' => [
						'expandAll' => 1,
						'showHeader' => true,
						'maxLevels' => 99,
						'width' => 500,
					]
				],
				'exclude' => 1,
				'default' => 0
			]
		],
		'catid' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_cat.catid',
			'config' => [
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '40',
				'default' => ''
			]
		],
		'keyword' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.keyword',
			'config' => [
				'type' => 'text',
				'rows' => '5',
				'cols' => '20',
				'max' => '512',
				'eval' => 'null',
				'default' => '',
			]
		],
		'note' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.note',
			'config' => [
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'default' => '',
			]
		],
		'note2' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.note2',
			'config' => [
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'default' => '',
			]
		],
		'image' => [
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'image',
			'config' => [
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
				'default' => '',
			]
		],
		'sliderimage' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_cat.sliderimage',
			'config' => [
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
				'default' => '',
			]
		],
		'discount' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_cat.discount',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_cat.discount_disable',
			'config' => [
				'type' => 'check',
				'default' => 0
			]
		],
		'email_uid' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_cat.email_uid',
			'config' => [
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products_emails',
				'foreign_table' => 'tt_products_emails',
				'foreign_table_where' => ' ORDER BY tt_products_emails.name',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 0
			]
		],
		'highlight' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_cat.highlight',
			'config' => [
				'type' => 'check',
				'default' => 0
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

                'showitem' => 'title, subtitle, slug, parent_category, catid, keyword, note, note2, email_uid, image, sliderimage, discount,discount_disable,highlight,tstamp,crdate,hidden,--palette--;;1'
            ]
    ],
    'palettes' => [
        '1' => ['showitem' => 'starttime, endtime, fe_group']
    ]
];

return $result;
