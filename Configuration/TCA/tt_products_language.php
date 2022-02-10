<?php
defined('TYPO3_MODE') || die('Access denied.');

$imageFolder = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'];
if (!$imageFolder) {
	$imageFolder = 'uploads/pics';
}

// ******************************************************************
// This is the language overlay for the products table, tt_products
// ******************************************************************
$result = array (

	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_language',
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
		'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => true,
		'origUid' => 't3_origuid',
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_language.gif',
		'languageField' => 'sys_language_uid',
		'mainpalette' => 1,
		'searchFields' => 'title,subtitle,itemnumber,ean,note,note2,www',
	),
	'columns' => array (
		't3ver_label' => array (
            'label'  => DIV2007_LANGUAGE_LGL . 'versionLabel',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '30',
				'default' => '',
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
		'sorting' => array (
			'config' => array (
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
		'prod_uid' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_language.prod_uid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => 'AND tt_products.pid IN (###CURRENT_PID###) ORDER BY tt_products.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 0
			),
		),
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
		'subtitle' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.subtitle',
			'config' => array (
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'eval' => null,
				'max' => '512',
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
				'default' => '',
			)
		),
		'itemnumber' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.itemnumber',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '120',
				'default' => '',
			)
		),
		'unit' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.unit',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '20',
				'default' => '',
			)
		),
		'note' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.note',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'default' => '',
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'note2' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.note2',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '2',
				'default' => '',
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'datasheet' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products_language.datasheet',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'doc,htm,html,pdf,sxw,txt,xls,gif,jpg,png',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/tx_ttproducts/datasheet',
				'size' => '5',
				'maxitems' => '20',
				'minitems' => '0',
				'eval' => 'null',
				'default' => '',
			)
		),
		'www' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'www',
			'config' => array (
				'type' => 'input',
				'eval' => 'trim',
				'size' => '30',
				'max' => '160',
				'default' => '',
			),
			'l10n_mode' => 'prefixLangTitle',
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
				'default' => '',
			)
		),
		'smallimage' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:tt_products.smallimage',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => $imageFolder,
				'size' => '5',
				'maxitems' => '10',
				'minitems' => '0',
				'eval' => 'null',
				'default' => '',
			)
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
                    ),
                    'note2' => array(
                        'config' => array(
                            'enableRichtext' => '1'
                        )
                    )
                ),

                'showitem' => 'sys_language_uid, l18n_diffsource, tstamp, crdate, hidden,--palette--;;1, prod_uid,title,--palette--;;2, slug, unit, note, note2, image, smallimage, datasheet'
            )
	),
	'palettes' => array (
		'1' => array('showitem' => 'starttime,endtime,fe_group'),
		'2' => array('showitem' => 'subtitle, keyword, itemnumber, www'),
	)
);

if (
    defined('TYPO3_version') &&
    version_compare(TYPO3_version, '10.0.0', '<')
) {
    $result['interface'] = [];
    $result['interface']['showRecordFieldList'] =   
        'sys_language_uid,hidden,starttime,endtime,prod_uid,title,subtitle,keyword,unit,note,note2,datasheet,www,image,smallimage';
}

return $result;
