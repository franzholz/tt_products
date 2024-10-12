<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

call_user_func(function ($extensionKey, $table): void {
    $languageSubpath = '/Resources/Private/Language/';
    $version = VersionNumberUtility::getCurrentTypo3Version();
    $configuration = GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);
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

    if (version_compare($version, '12.0.0', '<')) {

        $palleteAddition = ',--palette--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:sys_file_reference.shopAttributes;tt_productsPalette';

        $GLOBALS['TCA'][$table]['columns']['file_uid'] = [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.file_uid',
            'config' => ExtensionManagementUtility::getFileFieldTCAConfig(
                'file_uid',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                        'collapseAll' => true,
                    ],
                    'foreign_types' => [
                        '0' => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        '1' => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        '2' => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        '3' => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        '4' => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        '5' => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.audioOverlayPalette;audioOverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                            'showitem' => '
                            --palette--;LLL:EXT:core' . $languageSubpath . 'locallang_tca.xlf:sys_file_reference.videoOverlayPalette;videoOverlayPalette,
                            --palette--;;filePalette' . $palleteAddition,
                        ],
                    ],
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            ),
        ];

        $temporaryColumns['fe_group'] = [
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
        ];

        $temporaryColumns['edition'] = [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.edition',
            'config' => [
                'type' => 'select',
               'renderType' => 'selectSingle',
               'items' => [
                   ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.edition.complete', '0'],
               ['LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products_downloads.edition.partial', '1'],
               ],
               'size' => '2',
               'minitems' => 0,
               'maxitems' => 1,
               'default' => 0,
            ],
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            $table,
            $temporaryColumns
        );
    }

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

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
}, 'tt_products', basename(__FILE__, '.php'));
