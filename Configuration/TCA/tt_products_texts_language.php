<?php
defined('TYPO3_MODE') || die('Access denied.');

// ******************************************************************
// This is the language overlay for  products texts table, tt_products_texts
// ******************************************************************

$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:tt_products_texts_language',
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
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_texts_language.gif',
		'languageField' => 'sys_language_uid',
		'searchFields' => 'title,note',
	),
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,hidden,starttime,endtime,fe_group,title,note'
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
		'tstamp' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:tstamp',
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
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:crdate',
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
		'note' => array (
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:tt_products.note',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'eval' => null,
				'rows' => '5',
				'default' => ''
			),
			'l10n_mode' => 'prefixLangTitle',
		),
		'text_uid' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:tt_products_texts_language.text_uid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products_texts',
				'foreign_table' => 'tt_products_texts',
				'foreign_table_where' => 'AND tt_products_texts.pid IN (###CURRENT_PID###) ORDER BY tt_products_texts.uid',
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
                'showitem' => 'sys_language_uid, l18n_diffsource, crdate, tstamp, hidden,--palette--;;1, text_uid, title, note, parenttable'
            )
    ),
    'palettes' => array (
        '1' => array('showitem' => 'starttime, endtime, fe_group')
    )
);

if (
    version_compare(TYPO3_version, '8.5.0', '<')
) {
    $result['types']['0']['showitem'] =
        preg_replace(
            '/(^|,)\s*note\s*(,|$)/', '$1 note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/] $2',
            $result['types']['0']['showitem']
        );
}

return $result;
