<?php
defined('TYPO3') || die('Access denied.');

$extensionKey = 'tt_products';
$imageFolder = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['imageFolder'];
if (!$imageFolder) {
	$imageFolder = 'uploads/pics';
}

// ******************************************************************
// This is the language overlay for the products table, tt_products
// ******************************************************************
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.';

$result = [
	'ctrl' => [
		'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_language',
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
		'prependAtCopy' => $languageLglPath . 'prependAtCopy',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => true,
		'origUid' => 't3_origuid',
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_language.gif',
		'languageField' => 'sys_language_uid',
		'mainpalette' => 1,
		'searchFields' => 'title,subtitle,itemnumber,ean,note,note2,www',
	],
	'columns' => [
		'sys_language_uid' => [
			'exclude' => 0,
			'label' => $languageLglPath . 'language',
			'config' => [
                'type' => 'language',
				'default' => 0
			]
		],
		'hidden' => [
			'exclude' => 1,
			'label' => $languageLglPath . 'hidden',
			'config' => [
				'type' => 'check',
				'default' => 0
			]
		],
		'tstamp' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tstamp',
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
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:crdate',
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
			'label' => $languageLglPath . 'starttime',
			'config' => [
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => 0
			]
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
					'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
				]
			]
		],
        'fe_group' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label'  => $languageLglPath . 'fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 7,
                'maxitems' => 20,
                'items' => [
                    [
                        $languageLglPath . 'hide_at_login',
                        -1
                    ],
                    [
                        $languageLglPath . 'any_login',
                        -2
                    ],
                    [
                        $languageLglPath . 'usergroups',
                        '--div--'
                    ]
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'default' => 0,
            ]
        ],
		'prod_uid' => [
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_language.prod_uid',
			'config' => [
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => 'AND tt_products.pid IN (###CURRENT_PID###) ORDER BY tt_products.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 0
			],
		],
		'title' => [
			'exclude' => 0,
			'label' => $languageLglPath . 'title',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '256',
				'default' => '',
			],
			'l10n_mode' => 'prefixLangTitle',
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
				'default' => '',
			],
			'l10n_mode' => 'prefixLangTitle',
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
                'default' => ''
            ]
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
				'default' => '',
			]
		],
		'itemnumber' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.itemnumber',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '120',
				'default' => '',
			]
		],
		'unit' => [
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.unit',
			'config' => [
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '20',
				'default' => '',
			]
		],
		'note' => [
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.note',
			'config' => [
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'default' => '',
			],
			'l10n_mode' => 'prefixLangTitle',
		],
		'note2' => [
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.note2',
			'config' => [
				'type' => 'text',
				'cols' => '48',
				'rows' => '2',
				'default' => '',
			],
			'l10n_mode' => 'prefixLangTitle',
		],
		'datasheet' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_language.datasheet',
			'config' => [
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'doc,htm,html,pdf,sxw,txt,xls,gif,jpg,png',
                'max_size' => 
                isset($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize']) ? $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] : 0,
				'uploadfolder' => 'uploads/tx_ttproducts/datasheet',
				'size' => '5',
				'maxitems' => '20',
				'minitems' => '0',
				'eval' => 'null',
				'default' => '',
			]
		],
		'www' => [
			'exclude' => 1,
			'label' => $languageLglPath . 'www',
			'config' => [
				'type' => 'input',
				'eval' => 'trim',
				'size' => '30',
				'max' => '160',
				'default' => '',
			],
			'l10n_mode' => 'prefixLangTitle',
		],
		'image' => [
			'exclude' => 1,
			'label' => $languageLglPath . 'image',
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
		'smallimage' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.smallimage',
			'config' => [
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'max_size' => 
                isset($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize']) ? $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] : 0,
				'uploadfolder' => $imageFolder,
				'size' => '5',
				'maxitems' => '10',
				'minitems' => '0',
				'eval' => 'null',
				'default' => '',
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

                'showitem' => 'sys_language_uid, l18n_diffsource, tstamp, crdate, hidden,--palette--;;1, prod_uid,title,--palette--;;2, slug, unit, note, note2, image, smallimage, datasheet'
            ]
	],
	'palettes' => [
		'1' => ['showitem' => 'starttime,endtime,fe_group'],
		'2' => ['showitem' => 'subtitle, keyword, itemnumber, www'],
	]
];

return $result;
