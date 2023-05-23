<?php
defined('TYPO3') || die('Access denied.');

	// ******************************************************************
	// This is the standard TypoScript products texts table, tt_products_texts
	// ******************************************************************
$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.';

$result = [
	'ctrl' => [
		'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_texts',
		'label' => 'title',
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
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_texts.gif',
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
		'marker' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_texts.marker',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '256',
				'default' => ''
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
		'parentid' => [
			'exclude' => 0,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_texts.parentid',
			'config' => [
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'prepend_tname' => false,
				'foreign_table_where' => 'AND tt_products.pid IN (###CURRENT_PID###) ORDER BY tt_products.title',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 0
			]
		],
		'parenttable' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_texts.parenttable',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => [
					['tt_products', 'tt_products']
				],
				'default' => 'tt_products'
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
                ],
                'showitem' => 'tstamp, crdate, hidden,--palette--;;1, title, marker, note, parentid, parenttable'
            ]
	],
	'palettes' => [
		'1' => ['showitem' => 'starttime,endtime,fe_group'],
	]
];

return $result;

