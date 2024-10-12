<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\VersionNumberUtility;

call_user_func(function (): void {
    $table = 'tt_products_graduated_price';

    $version = VersionNumberUtility::getCurrentTypo3Version();

    if (version_compare($version, '12.0.0', '<')) {

        $temporaryColumns = [];
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

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            $table,
            $temporaryColumns
        );
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
});
