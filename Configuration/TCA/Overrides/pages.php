<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(function ($extensionKey, $table): void {
    // add folder icon
    $pageType = 'ttpproduct';
    $languageSubpath = '/Resources/Private/Language/';

    $addToModuleSelection = true;
    foreach ($GLOBALS['TCA']['pages']['columns']['module']['config']['items'] as $item) {
        if (
            isset($item[1]) &&
            $item[1] == $pageType
        ) {
            $addToModuleSelection = false;
            break;
        }
    }

    if ($addToModuleSelection) {
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
            0 => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang.xlf:pageModule.plugin',
            1 => $pageType,
            2 => 'apps-pagetree-folder-contains-tt_products',
        ];
    }

    ExtensionManagementUtility::registerPageTSConfigFile(
        $extensionKey,
        'Configuration/TsConfig/Page/folder_tables.txt',
        'EXT:' . $extensionKey . ' :: Restrict pages to tt_products records'
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        $extensionKey,
        'Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig',
        'Shop System Content Element Wizard'
    );
}, 'tt_products', basename(__FILE__, '.php'));

