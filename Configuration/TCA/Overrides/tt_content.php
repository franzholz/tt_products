<?php
defined('TYPO3_MODE') || die('Access denied.');

$table = 'tt_content';

$listType = '5';
$GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout,select_key';
$GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $listType,
    'FILE:EXT:' . TT_PRODUCTS_EXT . '/pi1/flexform_ds_pi1.xml'
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array(
        'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:tt_content.list_type_pi1',
        $listType,
        'EXT:' . TT_PRODUCTS_EXT . '/ext_icon.gif'
    ),
    'list_type',
    TT_PRODUCTS_EXT
);

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('searchbox')) {

    $listType = TT_PRODUCTS_EXT . '_pi_search';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout,select_key';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        $listType,
        'FILE:EXT:' . TT_PRODUCTS_EXT . '/pi_search/flexform_ds_pi_search.xml'
    );
    
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        array(
            'LLL:EXT:' . TT_PRODUCTS_EXT . '/pi_search/locallang_db.xlf:tt_content.list_type_pi_search',
            $listType,
            'EXT:' . TT_PRODUCTS_EXT . '/ext_icon.gif'
        ),
        'list_type',
        TT_PRODUCTS_EXT
    );
}

$listType = TT_PRODUCTS_EXT . '_pi_int';
$GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout,select_key';
$GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $listType, 
    'FILE:EXT:' . TT_PRODUCTS_EXT . '/pi_int/flexform_ds_pi_int.xml'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array(
        'LLL:EXT:' . TT_PRODUCTS_EXT . '/pi_int/locallang_db.xlf:tt_content.list_type_pi_int', $listType,
        'EXT:' . TT_PRODUCTS_EXT . '/ext_icon.gif'
    ),
    'list_type',
    TT_PRODUCTS_EXT
);


