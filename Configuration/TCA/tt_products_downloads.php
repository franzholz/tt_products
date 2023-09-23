<?php
defined('TYPO3') || die('Access denied.');

// ******************************************************************
// This is the standard TypoScript products downloads table, tt_products_downloads
// ******************************************************************

$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$result = [
	'ctrl' => [
		'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads',
		'label' => 'title',
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
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/' . 'tt_products_downloads.gif',
		'searchFields' => 'title,marker,note',
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
		'hidden' => [
			'exclude' => 1,
			'label' => $languageLglPath . 'hidden',
			'config' => [
				'type' => 'check',
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
		'title' => [
			'exclude' => 0,
			'label' => $languageLglPath . 'title',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '256',
				'default' => '',
			]
		],
        'slug' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['title', 'author'],
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
		'author' => [
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.author',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'max' => '256',
				'default' => '',
			]
		],
		'edition' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.edition',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => [
					['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.edition.complete', '0'],
					['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.edition.partial', '1'],
				],
				'size' => '2',
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 0,
			]
		],
		'marker' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.marker',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'max' => '256',
                'default' => '',
			]
		],
		'note' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.note',
			'config' => [
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'default' => '',
			]
		],
		'path' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.path',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'max' => '256',
				'default' => '',
			]
		],
		'price_enable' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.price_enable',
			'config' => [
				'type' => 'check',
				'default' => 0
			]
		],
		'price' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.price',
			'config' => [
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim,double2',
				'max' => '20',
				'default' => 0
			]
		],
        'file_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.file_uid',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('file_uid')
            
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
                    ]
                ],
                'showitem' => 'title, author, slug, edition, price_enable, price,  note, marker, path, file_uid, tstamp, crdate, hidden,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                --palette--;;access'
            ]
    ],
    'palettes' => [
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access',
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.starttime_formlabel, endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.endtime_formlabel, --linebreak--, fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.fe_group_formlabel, --linebreak--',
        ],
    ]
];

return $result;

