<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(function ($extensionKey, $table): void {
    $configuration = GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);
    $languageSubpath = '/Resources/Private/Language/';
    $languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

    $orderBySortingTablesArray = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['orderBySortingTables']);
    if (
        !empty($orderBySortingTablesArray) &&
        in_array($table, $orderBySortingTablesArray)
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
        $GLOBALS['TCA'][$table]['columns']['sorting'] =
            [
                'config' => [
                    'type' => 'passthrough',
                    'default' => 0,
                ],
            ];
    }

    $GLOBALS['TCA'][$table]['columns']['slug']['config']['eval'] = $configuration->getSlugBehaviour();

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
    $excludeArray =
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['exclude'];

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
}, 'tt_products', basename(__FILE__, '.php'));
