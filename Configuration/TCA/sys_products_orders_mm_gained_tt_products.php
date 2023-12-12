<?php

defined('TYPO3') || die('Access denied.');

// ******************************************************************
// order to gained products table,  sys_products_orders_mm_gained_tt_products
// ******************************************************************
$extensionKey = 'tt_products';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$result = [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders_mm_gained_tt_products',
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
    'types' => [
        '0' => [
            'showitem' => '',
        ],
    ],
    'columns' => [],
];

return $result;
