<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {

    $table = 'tt_content';

    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist']['5'] = 'layout';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist']['5'] = 'pi_flexform';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('5', 'FILE:EXT:' . TT_PRODUCTS_EXT . '/pi1/flexform_ds_pi1.xml');

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('searchbox')) {

        $listType = TT_PRODUCTS_EXT . '_pi_search';
        $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout';
        $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($listType, 'FILE:EXT:' . TT_PRODUCTS_EXT . '/pi_search/flexform_ds_pi_search.xml');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
            [
                'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH .
                'PiSearch/locallang_db.xlf:tt_content.list_type_pi_search',
                $listType
            ],
            'list_type',
            TT_PRODUCTS_EXT
        );
    }

    $listType = TT_PRODUCTS_EXT . '_pi_int';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($listType, 'FILE:EXT:' . TT_PRODUCTS_EXT . '/pi_int/flexform_ds_pi_int.xml');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH .
            'PiInt/locallang_db.xlf:tt_content.list_type_pi_int',
            $listType
        ],
        'list_type',
        TT_PRODUCTS_EXT
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH .
            'locallang_db.xlf:tt_content.list_type_pi1',
            '5'
        ],
        'list_type',
        TT_PRODUCTS_EXT
    );
});

