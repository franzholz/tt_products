<?php
defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(function($extensionKey, $table)
{
    $orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['orderBySortingTables']);
    $languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

    if (
        !empty($orderBySortingTablesArray) &&
        in_array($table, $orderBySortingTablesArray)
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
        $GLOBALS['TCA'][$table]['columns']['sorting'] = 
            [
                'config' => [
                    'type' => 'passthrough',
                    'default' => 0
                ]
            ];
    }

    $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
    $version = $typo3Version->getVersion();

    if (
        version_compare($version, '11.0.0', '<')
    ) {
        $GLOBALS['TCA'][$table]['columns']['sys_language_uid'] = [
            'exclude' => 1,
            'label' => $languageLglPath . 'language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    [$languageLglPath . 'allLanguages', -1],
                    [$languageLglPath . 'default_value', 0]
                ],
                'default' => 0
            ]
        ];
    }

    $excludeArray =  
        ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['exclude']);

    if (
        isset($excludeArray) &&
        is_array($excludeArray) &&
        isset($excludeArray[$table]) &&
        is_array($excludeArray[$table])
    ) {
        \JambageCom\Div2007\Utility\TcaUtility::removeField(
            $GLOBALS['TCA'][$table],
            $excludeArray[$table]
        );
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
}, 'tt_products', basename(__FILE__, '.php'));
