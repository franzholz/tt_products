<?php
defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(function($extensionKey, $table)
{
    $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);
    $languageSubpath = '/Resources/Private/Language/';
    $languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

    $orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['orderBySortingTables']);
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

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('voucher')) {

        $temporaryColumns = [
            'gained_voucher' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_products_orders.gained_voucher',
                'config' => [
                    'type' => 'inline',
                    'appearance' => [
                        'collapseAll' => true,
                        'newRecordLinkAddTitle' => true,
                        'useCombination' => true
                    ],
                    'foreign_table' => 'sys_products_orders_mm_gained_voucher_codes',
                    'foreign_field' => 'uid_local',
                    'foreign_sortby' => 'sorting',
                    'foreign_label' => 'uid_foreign',
                    'foreign_selector' => 'uid_foreign',
                    'foreign_unique' => 'uid_foreign',
                    'maxitems' => 100
                ],
            ],
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            'sys_products_orders',
            $temporaryColumns
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'sys_products_orders',
            'gained_voucher',
            '',
            'after:gained_uid'
        );
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
        
    $GLOBALS['TCA'][$table]['columns']['slug']['config']['eval'] = $configuration->getSlugBehaviour();

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
}, 'tt_products', basename(__FILE__, '.php'));
