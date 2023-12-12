<?php

defined('TYPO3') || die('Access denied.');

call_user_func(function ($extensionKey, $table): void {
    // add folder icon
    $pageType = 'ttpproduct';
    $languageSubpath = '/Resources/Private/Language/';

    $addToModuleSelection = true;
    foreach ($GLOBALS['TCA']['pages']['columns']['module']['config']['items'] as $item) {
        if ($item['1'] == $pageType) {
            $addToModuleSelection = false;
            continue;
        }
    }

    if ($addToModuleSelection) {
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
            0 => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang.xlf:pageModule.plugin',
            1 => $pageType,
            2 => 'apps-pagetree-folder-contains-tt_products',
        ];
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
        $pageType,
        'Configuration/TSconfig/Page/folder_tables.txt',
        'EXT:' . $extensionKey . ' :: Restrict pages to tt_products records'
    );
}, 'tt_products', basename(__FILE__, '.php'));
