<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(function ($extensionKey, $table): void {

    $languageSubpath = '/Resources/Private/Language/';
    $languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

    if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['articleMode'])) {
        $GLOBALS['TCA'][$table] = [
            'ctrl' => [
                'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_products_mm_articles',
            'label' => 'uid_local',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'type' => 'uid_local:type',
            'hideTable' => true,
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
            'crdate' => 'crdate',
            'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/tt_products_relations.gif',
            'hideTable' => true,
            ],
            // Article palette, hidden but needs to be included all the time
            'articlePalette' => [
                'showitem' => 'uid_local',
            'isHiddenPalette' => true,
            ],
        ];
    }

    ExtensionManagementUtility::addToInsertRecords($table);
}, 'tt_products', basename(__FILE__, '.php'));
