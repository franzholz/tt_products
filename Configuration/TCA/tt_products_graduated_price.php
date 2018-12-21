<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// ******************************************************************
// graduated price calculation table, tt_products_graduated_price
// ******************************************************************
$result = array (
	'ctrl' => array (
		'title' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_graduated_price',
		'label' => 'title',
		'default_sortby' => 'ORDER BY title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
		'crdate' => 'crdate',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_cat.gif',
		'searchFields' => 'title,note',
	),
	'interface' => array (
		'showRecordFieldList' => 'title,formula,startamount,note,parentid,items'
	),
	'columns' => array (
		'tstamp' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => '0'
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
				'default' => '0'
			)
		),
		'sorting' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'hidden' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'hidden',
			'config' => array (
				'type' => 'check'
			)
		),
		'starttime' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_graduated_price.starttime',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => '0'
			)
		),
		'endtime' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_graduated_price.endtime',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => '0',
				'range' => array (
					'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['endtimeYear']),
					'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
				)
			)
		),
		'fe_group' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'fe_group',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array (
					array('', 0),
					array(DIV2007_LANGUAGE_LGL . 'hide_at_login', -1),
					array(DIV2007_LANGUAGE_LGL . 'any_login', -2),
					array(DIV2007_LANGUAGE_LGL . 'usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => DIV2007_LANGUAGE_LGL . 'title',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '256',
				'default' => ' ',
			)
		),
		'formula' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_graduated_price.formula',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '1',
				'default' => NULL,
			)
		),
		'startamount' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:'.TT_PRODUCTS_EXT.'/locallang_db.xml:tt_products_graduated_price.startamount',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'note' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'note',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '2',
				'default' => NULL,
			)
		),
		'items' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products_graduated_price.items',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array (
					array('', 0),
				),
				'foreign_table' => 'tt_products_mm_graduated_price',
				'foreign_field' => 'graduated_price_uid',
				'foreign_sortby' => 'graduatedsort',
				'foreign_label' => 'product_uid',
				'size' => 6,
				'minitems' => 0,
				'maxitems' => 100,
			)
		),
	),
	'types' => array (
        '0' =>
            array (
                'columnsOverrides' => array(
                    'note' => array(
                        'config' => array(
                            'enableRichtext' => '1'
                        )
                    ),
                ),
                'showitem' => 'hidden,--palette--;;1, title, formula, startamount, note, items'
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

