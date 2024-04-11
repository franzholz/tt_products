<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(function ($extensionKey, $table): void {
    // add folder icon
    $pageType = 'ttproducts'; // a maximum of 10 characters
    $iconReference = 'apps-pagetree-folder-contains-' . $pageType;
    $languageSubpath = '/Resources/Private/Language/';

    $addToModuleSelection = true;
    foreach ($GLOBALS['TCA'][$table]['columns']['module']['config']['items'] as $item) {
        if (
            isset($item[1]) &&
            $item[1] == $pageType
        ) {
            $addToModuleSelection = false;
            break;
        }
    }

    if ($addToModuleSelection) {
        $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['contains-' . $pageType] = $iconReference;
        $GLOBALS['TCA'][$table]['columns']['module']['config']['items'][] = [
            0 => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang.xlf:pageModule.plugin',
            1 => $pageType,
            2 => $iconReference,
        ];
    }

    ExtensionManagementUtility::registerPageTSConfigFile(
        $extensionKey,
        'Configuration/TsConfig/Page/folder_tables.txt',
        'EXT:' . $extensionKey . ' :: Restrict pages to ' . $extensionKey . ' records'
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        $extensionKey,
        'Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig',
        'Shop System Content Element Wizard'
    );
}, 'tt_products', basename(__FILE__, '.php'));

