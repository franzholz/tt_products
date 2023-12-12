<?php

defined('TYPO3') || die('Access denied.');

call_user_func(function ($extensionKey) {
    $tables = [
        'tt_products',
        'tt_products_language',
        'tt_products_articles',
        'tt_products_articles_language',
        'tt_products_cat',
        'tt_products_cat_language',
        'tt_products_emails',
        'tt_products_downloads',
        'tt_products_downloads_language',
        'tt_products_graduated_price',
        'tt_products_mm_graduated_price',
        'tt_products_texts',
        'tt_products_texts_language',
        'sys_products_accounts',
        'sys_products_cards',
        'sys_products_orders',
        'sys_file_reference',
    ];

    foreach ($tables as $table) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($table);
    }

    $tables = [
        'tt_products',
        'tt_products_articles',
        'tt_products_cat',
        'tt_products_emails',
        'tt_products_downloads',
        'tt_products_texts',
        'sys_products_accounts',
        'sys_products_cards',
        'sys_products_orders',
    ];
    $languageSubpath = '/Resources/Private/Language/';

    foreach ($tables as $table) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr($table, 'EXT:' . $extensionKey . $languageSubpath . 'Csh/locallang_csh_' . $table . '.xlf');
    }

    if (
        defined('TYPO3_MODE') &&
        TYPO3_MODE == 'BE'
    ) {
        $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']['JambageCom\\TtProducts\\Controller\\Plugin\\WizardIcon'] = PATH_BE_TTPRODUCTS . 'Classes/Controller/Plugin/WizardIcon.php';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'web_func',
            \JambageCom\TtProducts\Controller\Module\MoveItemsWizardModuleFunctionController::class,
            null,
            'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang.xlf:moduleFunction.tx_ttproducts_modfunc1'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'web_func',
            \JambageCom\TtProducts\Controller\Module\CreateLanguagesWizardModuleFunctionController::class,
            null,
            'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang.xlf:moduleFunction.tx_ttproducts_modfunc2'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'web_func',
            \JambageCom\TtProducts\Controller\Module\ImportFalWizardModuleFunctionController::class,
            null,
            'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang.xlf:moduleFunction.tx_ttproducts_modfunc3'
        );
    }
}, 'tt_products');
