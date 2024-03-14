<?php

defined('TYPO3') || die('Access denied.');

// ******************************************************************
// products to download relation table, tt_products_products_note_pages_mm
// ******************************************************************

$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_products_note_pages_mm',
        'label' => 'uid_local',
        'tstamp' => 'tstamp',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'prependAtCopy' => $languageLglPath . 'prependAtCopy',
        'crdate' => 'crdate',
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/tt_products_relations.gif',
        'hideTable' => true,
    ],
    'columns' => [
        'uid_local' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_products_note_pages_mm.uid_local',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tt_products',
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'uid_foreign' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_products_note_pages_mm.uid_foreign',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'pages',
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'localsort' => [
            'config' => [
                'type' => 'passthrough',
                'default' => 0,
            ],
        ],
        'foreignsort' => [
            'config' => [
                'type' => 'passthrough',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '',
        ],
    ],
];

return $result;
