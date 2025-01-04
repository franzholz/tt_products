<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(function ($extensionKey, $table): void {
    $languageSubpath = '/Resources/Private/Language/';

    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist']['5'] = 'layout';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist']['5'] = 'pi_flexform';

    ExtensionManagementUtility::addPiFlexFormValue('5', 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForms/flexform_ds_pi1.xml');

    ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:' . $extensionKey . $languageSubpath .
            'locallang_db.xlf:tt_content.list_type_pi1',
            '5',
        ],
        'list_type',
        $extensionKey
    );

    if (ExtensionManagementUtility::isLoaded('searchbox')) {
        $listType = $extensionKey . '_pi_search';
        $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout';
        $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';
        ExtensionManagementUtility::addPiFlexFormValue($listType, 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForms/flexform_ds_pi_search.xml');
        ExtensionManagementUtility::addPlugin(
            [
                'LLL:EXT:' . $extensionKey . $languageSubpath .
                'PiSearch/locallang_db.xlf:tt_content.list_type_pi_search',
                $listType,
            ],
            'list_type',
            $extensionKey
        );
    }

    $listType = $extensionKey . '_pi_int';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';
    ExtensionManagementUtility::addPiFlexFormValue($listType, 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForms/flexform_ds_pi_int.xml');
    ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:' . $extensionKey . $languageSubpath .
            'PiInt/locallang_db.xlf:tt_content.list_type_pi_int',
            $listType,
        ],
        'list_type',
        $extensionKey
    );

}, 'tt_products', basename(__FILE__, '.php'));
