<?php
defined('TYPO3') || die('Access denied.');

$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$result = [
	'ctrl' => [
		'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_emails',
		'label' => 'name',
		'default_sortby' => 'ORDER BY name',
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
		'mainpalette' => 1,
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/' . 'tt_products_emails.gif',
		'searchFields' => 'name,email',
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
		'name' => [
			'label' => $languageLglPath . 'name',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '80',
				'default' => '',
			]
		],
		'email' => [
			'label' => $languageLglPath . 'email',
			'config' => [
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '80',
				'default' => '',
			]
		],
		'suffix' => [
			'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_emails.suffix',
			'config' => [
				'type' => 'input',
				'size' => '24',
				'eval' => 'trim',
				'max' => '24',
				'default' => '',
			]
		],
	],
	'types' => [
		'1' => ['showitem' => 'hidden,--palette--;;1, name, email, suffix']
	],
	'palettes' => [
		'1' => ['showitem' => 'starttime, endtime, fe_group']
	]

];

return $result;

