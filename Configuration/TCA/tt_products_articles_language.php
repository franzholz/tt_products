<?php

defined('TYPO3') || die('Access denied.');


// ******************************************************************
// This is the language overlay for the articles table, tt_products_articles
// ******************************************************************

$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles_language',
        'label' => 'title',
        'label_alt' => 'subtitle',
        'default_sortby' => 'ORDER BY title',
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
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/tt_products_articles_language.gif',
        'languageField' => 'sys_language_uid',
        'mainpalette' => 1,
        'searchFields' => 'title,subtitle,itemnumber,keyword,note,note2',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ]
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 0,
            'label' => $languageLglPath . 'language',
            'config' => [
                'type' => 'language',
                'default' => 0,
            ],
        ],
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
                        'label' => $languageLglPath . 'hide_at_login',
                        'value' => -1,
                    ],
                    [
                        'label' => $languageLglPath . 'any_login',
                        'value' => -2,
                    ],
                    [
                        'label' => $languageLglPath . 'usergroups',
                        'value' => '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'default' => 0,
            ],
        ],
        'article_uid' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles_language.article_uid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_products_articles',
                'foreign_table' => 'tt_products_articles',
                'foreign_table_where' => 'AND tt_products_articles.pid IN (###CURRENT_PID###) ORDER BY tt_products_articles.title',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => $languageLglPath . 'title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'eval' => 'trim',
                'max' => '256',
                'default' => null,
            ],
            'l10n_mode' => 'prefixLangTitle',
        ],
        'subtitle' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.subtitle',
            'config' => [
                'type' => 'text',
                'rows' => '3',
                'cols' => '20',
                'eval' => null,
                'max' => '512',
                'default' => null,
            ],
            'l10n_mode' => 'prefixLangTitle',
        ],
        'slug' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['title', 'article_uid'],
                    'fieldSeparator' => '_',
                    'replacements' => [
                        '/' => '-',
                    ],
                ],
                'fallbackCharacter' => '-',
                'eval' => $configuration->getSlugBehaviour(),
                'default' => null,
            ],
        ],
        'keyword' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.keyword',
            'config' => [
                'type' => 'text',
                'rows' => '5',
                'cols' => '20',
                'max' => '512',
                'eval' => 'null',
                'default' => null,
            ],
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
        'note2' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products.note2',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'default' => null,
            ],
            'l10n_mode' => 'prefixLangTitle',
        ],
    ],
    'types' => [
        '1' => [
                'columnsOverrides' => [
                    'note' => [
                        'config' => [
                            'enableRichtext' => '1',
                        ],
                    ],
                    'note2' => [
                        'config' => [
                            'enableRichtext' => '1',
                        ],
                    ],
                ],
                'showitem' => 'sys_language_uid, l18n_diffsource, tstamp, crdate, article_uid,title,--palette--;;2, slug, note, note2, hidden,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                --palette--;;access',
            ],
    ],
    'palettes' => [
        '2' => ['showitem' => 'subtitle,keyword'],
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access',
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.starttime_formlabel, endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.endtime_formlabel, --linebreak--, fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.fe_group_formlabel, --linebreak--',
        ],
    ],
];

return $result;
