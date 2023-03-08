<?php
defined('TYPO3_MODE') || die('Access denied.');

$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_emails',
		'label' => 'name',
		'default_sortby' => 'ORDER BY name',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => true,
		'origUid' => 't3_origuid',
		'mainpalette' => 1,
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_emails.gif',
		'searchFields' => 'name,email',
	),
	'columns' => array (
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
		'name' => array (
			'label' => DIV2007_LANGUAGE_LGL . 'name',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '80',
				'default' => '',
			)
		),
		'email' => array (
			'label' => DIV2007_LANGUAGE_LGL . 'email',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '80',
				'default' => '',
			)
		),
		'suffix' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_emails.suffix',
			'config' => array (
				'type' => 'input',
				'size' => '24',
				'eval' => 'trim',
				'max' => '24',
				'default' => '',
			)
		),
	),
	'types' => array (
		'1' => array('showitem' => 'hidden,--palette--;;1, name, email, suffix')
	),
	'palettes' => array (
		'1' => array('showitem' => 'starttime, endtime, fe_group')
	)

);

return $result;

