<?php
defined('TYPO3') || die('Access denied.');

$imageFile = PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products.gif';

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
        0 => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang.xlf:pageModule.plugin',
        1 => $pageType,
        2 => 'apps-pagetree-folder-contains-tt_products'
    );
}


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
    $pageType,
    'Configuration/TSconfig/Page/folder_tables.txt',
    'EXT:' . TT_PRODUCTS_EXT . ' :: Restrict pages to tt_products records'
);

