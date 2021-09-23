<?php
defined('TYPO3_MODE') || die('Access denied.');

$imageFile = PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products.gif';

if (
    version_compare(TYPO3_version, '7.5.0', '>')
) {
    // add folder icon
    $pageType = 'ttpproduct';

    $addToModuleSelection = true;
    foreach ($GLOBALS['TCA']['pages']['columns']['module']['config']['items'] as $item) {
        if ($item['1'] == $pageType) {
            $addToModuleSelection = false;
            continue;
        }
    }

    if ($addToModuleSelection) {
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
            0 => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xml:pageModule.plugin',
            1 => $pageType,
            2 => 'apps-pagetree-folder-contains-tt_products'
        );
    }


    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
        $pageType,
        'Configuration/TSconfig/Page/folder_tables.txt',
        'EXT:' . TT_PRODUCTS_EXT . ' :: Restrict pages to tt_products records'
    );
} else {
    // add folder icon
    $pageType = 'ttpproduct';

    $callingClassName = '\\TYPO3\\CMS\\Backend\\Sprite\\SpriteManager';
    if (
        class_exists($callingClassName) &&
        method_exists($callingClassName, 'addTcaTypeIcon')
    ) {
        call_user_func(
            $callingClassName . '::addTcaTypeIcon',
            'pages',
            'contains-' . $pageType,
            $imageFile
        );
    }

    $addToModuleSelection = true;
    foreach ($GLOBALS['TCA']['pages']['columns']['module']['config']['items'] as $item) {
        if ($item['1'] == $pageType) {
            $addToModuleSelection = false;
            break;
        }
    }

    if ($addToModuleSelection) {
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
            0 => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xml:pageModule.plugin',
            1 => $pageType,
            2 => 'EXT:' . TT_PRODUCTS_EXT . '/res/icons/table/tt_products.gif'
        );
    }
}

