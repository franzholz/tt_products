<?php
defined('TYPO3_MODE') || die('Access denied.');

// ******************************************************************
// This is the language overlay for the articles table, tt_products_articles
// ******************************************************************
$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles_language',
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
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_articles_language.gif',
		'languageField' => 'sys_language_uid',
		'mainpalette' => 1,
		'searchFields' => 'title,subtitle,itemnumber,keyword,note,note2',
	),
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,hidden,starttime,endtime,fe_group,article_uid,title,subtitle,keyword,note,note2'
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
		'sys_language_uid' => array (
			'exclude' => 0,
			'label' => DIV2007_LANGUAGE_LGL . 'language',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array(DIV2007_LANGUAGE_LGL . 'allLanguages', -1),
					array(DIV2007_LANGUAGE_LGL . 'default_value', 0)
				),
				'default' => 0
			)
		),
		'tstamp' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
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
				'eval' => 'date',
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
		'article_uid' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_articles_language.article_uid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products_articles',
				'foreign_table' => 'tt_products_articles',
				'foreign_table_where' => 'AND tt_products_articles.pid IN (###CURRENT_PID###,###STORAGE_PID###) ORDER BY tt_products_articles.title',
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
				'default' => ''
			),
			'l10n_mode' => 'prefixLangTitle',
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
			),
			'l10n_mode' => 'prefixLangTitle',
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
        'slug' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.slug',
            'config' => array (
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => array (
                    'fields' => array ('title', 'article_uid'),
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

                'showitem' => 'sys_language_uid, l18n_diffsource, hidden,--palette--;;1, article_uid,title,--palette--;;2, slug, note, note2'
            )
    ),
    'palettes' => array (
        '1' => array('showitem' => 'starttime,endtime,fe_group'),
        '2' => array('showitem' => 'subtitle,keyword'),
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

