<?php

defined('TYPO3') || die('Access denied.');

// ******************************************************************
// This is the language overlay for  products texts table, tt_products_texts
// ******************************************************************
$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_texts_language',
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
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/tt_products_texts_language.gif',
        'languageField' => 'sys_language_uid',
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
                'default' => 0,
            ],
        ],
        'crdate' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:crdate',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'datetime,int',
                'renderType' => 'inputDateTime',
                'default' => 0,
            ],
        ],
        'sys_language_uid' => [
            'exclude' => 0,
            'label' => $languageLglPath . 'language',
            'config' => [
                'type' => 'language',
                'default' => 0,
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'starttime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'date',
                'renderType' => 'inputDateTime',
                'default' => 0,
            ],
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
                    'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y')),
                ],
            ],
        ],
        'fe_group' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => $languageLglPath . 'fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 7,
                'maxitems' => 20,
                'items' => [
                    [
                        $languageLglPath . 'hide_at_login',
                        -1,
                    ],
                    [
                        $languageLglPath . 'any_login',
                        -2,
                    ],
                    [
                        $languageLglPath . 'usergroups',
                        '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'default' => 0,
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => $languageLglPath . 'title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '256',
                'default' => null,
            ],
            'l10n_mode' => 'prefixLangTitle',
        ],
        'note' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.note',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'default' => null,
            ],
            'l10n_mode' => 'prefixLangTitle',
        ],
        'text_uid' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_texts_language.text_uid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_products_texts',
                'foreign_table' => 'tt_products_texts',
                'foreign_table_where' => 'AND tt_products_texts.pid IN (###CURRENT_PID###) ORDER BY tt_products_texts.uid',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'columnsOverrides' => [
                'note' => [
                    'config' => [
                        'enableRichtext' => '1',
                    ],
                ],
            ],
            'showitem' => 'sys_language_uid, l18n_diffsource, tstamp, crdate, text_uid, title, note, parenttable, hidden,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                    --palette--;;access',
        ],
    ],
    'palettes' => [
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access',
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.starttime_formlabel, endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.endtime_formlabel, --linebreak--, fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.fe_group_formlabel, --linebreak--',
        ],
    ],
];

return $result;
