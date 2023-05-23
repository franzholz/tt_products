<?php
defined('TYPO3') || die('Access denied.');

$extensionKey = 'tt_products';
$result = null;
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.';


if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['articleMode'] >= '1') {
    $result = [
        'ctrl' => [
            'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_products_mm_articles',
            'label' => 'uid_local',
            'tstamp' => 'tstamp',
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden'
            ],
            'prependAtCopy' => $languageLglPath . 'prependAtCopy',
            'crdate' => 'crdate',
            'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products_relations.gif',
            'hideTable' => true,
        ],
        'columns' => [
            'uid_local' => [
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_products_mm_articles.uid_local',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tt_products',
                    'maxitems' => 1,
                    'default' => 0
                ]
            ],
            'uid_foreign' => [
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_products_mm_articles.uid_foreign',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tt_products_articles',
                    'maxitems' => 1,
                    'default' => 0
                ]
            ],
            'sorting' => [
                'config' => [
                    'type' => 'passthrough',
                    'default' => 0
                ]
            ],
            'sorting_foreign' => [
                'config' => [
                    'type' => 'passthrough',
                    'default' => 0
                ]
            ],
        ],
        'types' => [
            '0' => [
                'showitem' => ''
            ]
        ]
    ];
}

return $result;
