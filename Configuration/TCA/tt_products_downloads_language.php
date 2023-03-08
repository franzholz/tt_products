<?php
defined('TYPO3_MODE') || die('Access denied.');

// ******************************************************************
	// This is the language overlay for products downloads table, tt_products_downloads
// ******************************************************************

$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_downloads_language',
		'label' => 'title',
		'default_sortby' => 'ORDER BY title',
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
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_downloads_language.gif',
		'dynamicConfigFile' => PATH_BE_TTPRODUCTS . 'tca.php',
		'languageField' => 'sys_language_uid',
		'searchFields' => 'title,note',
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
		'sys_language_uid' => array (
			'exclude' => 0,
			'label' => DIV2007_LANGUAGE_LGL . 'language',
			'config' => array (
                'type' => 'language',
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
					'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
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
				'max' => '256',
				'default' => '',
			),
			'l10n_mode' => 'prefixLangTitle',
		),
        'slug' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.slug',
            'config' => array (
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => array (
                    'fields' => array ('title', 'parent_uid'),
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
		'note' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.note',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'eval' => 'null',
				'default' => '',
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'parent_uid' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_downloads_language.parent_uid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products_downloads',
				'foreign_table' => 'tt_products_downloads',
				'foreign_table_where' => 'AND tt_products_downloads.pid IN (###CURRENT_PID###) ORDER BY tt_products_downloads.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 0
			),
		),
	),
	'types' => array (
       '0' =>
            array(
                'columnsOverrides' => array(
                    'note' => array(
                        'config' => array(
                            'enableRichtext' => '1'
                        )
                    )
                ),
                'showitem' => 'sys_language_uid, l18n_diffsource, tstamp, crdate, hidden,--palette--;;1, parent_uid, title, slug, note, parenttable'
            )
    ),
    'palettes' => array (
        '1' => array('showitem' => 'starttime, endtime, fe_group')
    )
);

return $result;
