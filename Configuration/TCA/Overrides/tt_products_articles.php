<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(function ($extensionKey, $table): void {
    $configuration =  GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);
    $languageSubpath = '/Resources/Private/Language/';
    $languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';
    $fieldArray = ['tstamp', 'crdate', 'starttime', 'endtime'];

    foreach ($fieldArray as $field) {
        unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
    }

    switch ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['articleMode']) {
        case '0':
            $GLOBALS['TCA'][$table]['columns']['uid_product'] = [
                'exclude' => 1,
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_articles.uid_product',
                'config' => [
                    'type' => 'group',
                    'allowed' => 'tt_products',
                    'size' => 1,
                    'minitems' => 0,
                    'maxitems' => 1,
                ],
            ];

            $GLOBALS['TCA'][$table]['types']['1']['showitem'] =
                str_replace(
                    'title,',
                    'uid_product,title,',
                    $GLOBALS['TCA'][$table]['types']['1']['showitem']
                );

            break;
    }

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

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);

    $GLOBALS['TCA'][$table]['columns']['slug']['config']['eval'] = $configuration->getSlugBehaviour();

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
