<?php
defined('TYPO3') || die('Access denied.');

// ******************************************************************
// graduated price calculation table, tt_products_graduated_price
// ******************************************************************

$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.';

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_graduated_price',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'prependAtCopy' => $languageLglPath . 'prependAtCopy',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_cat.gif',
        'searchFields' => 'title,note',
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
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_graduated_price.starttime',
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
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_graduated_price.endtime',
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
        'formula' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_graduated_price.formula',
            'config' => [
                'type' => 'text',
                'cols' => '48',
				'eval' => 'trim',
                'rows' => '1',
                'default' => ''
            ]
        ],
        'startamount' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_graduated_price.startamount',
            'config' => [
                'type' => 'input',
                'size' => '12',
                'eval' => 'trim,double2',
                'max' => '20',
                'default' => ''
            ]
        ],
        'note' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'note',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '2',
                'default' => ''
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
                'showitem' => 'hidden,--palette--;;1, title, formula, startamount, note'
            ]
    ],
    'palettes' => [
        '1' => ['showitem' => 'starttime, endtime, fe_group']
    ]
];

$table = 'tt_products_graduated_price';

$orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['orderBySortingTables']);

if (
    !empty($orderBySortingTablesArray) &&
    in_array($table, $orderBySortingTablesArray)
) {
    $result['ctrl']['sortby'] = 'sorting';
}

return $result;
