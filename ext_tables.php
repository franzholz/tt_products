<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    $emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';
    $divClass = '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility';

    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products');
    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_language');
    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_articles');
    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_articles_language');
    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_cat');
    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_cat_language');
    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_graduated_price');
    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_emails');
    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_mm_graduated_price');
    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_texts');
    call_user_func($emClass . '::allowTableOnStandardPages', 'tt_products_texts_language');
    call_user_func($emClass . '::allowTableOnStandardPages', 'sys_products_accounts');
    call_user_func($emClass . '::allowTableOnStandardPages', 'sys_products_cards');
    call_user_func($emClass . '::allowTableOnStandardPages', 'sys_products_orders');

    call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprod.xlf');
    call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products_cat', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprodc.xlf');
    call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products_articles', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttproda.xlf');
    call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products_emails', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprode.xlf');
    call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products_texts', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprodt.xlf');
    call_user_func($emClass . '::addLLrefForTCAdescr', 'tt_products_downloads', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttproddl.xlf');
    call_user_func($emClass . '::addLLrefForTCAdescr', 'sys_products_accounts', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprodac.xlf');
    call_user_func($emClass . '::addLLrefForTCAdescr', 'sys_products_cards', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprodca.xlf');
    call_user_func($emClass . '::addLLrefForTCAdescr', 'sys_products_orders', 'EXT:' . TT_PRODUCTS_EXT . '/locallang_csh_ttprodo.xlf');

    if (TYPO3_MODE == 'BE') {

        $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']['JambageCom\\TtProducts\\Controller\\Plugin\\WizardIcon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(TT_PRODUCTS_EXT) . 'Classes/Controller/Plugin/WizardIcon.php';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'web_func',
            \JambageCom\TtProducts\Controller\Module\MoveItemsWizardModuleFunctionController::class,
            null,
            'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xlf:moduleFunction.tx_ttproducts_modfunc1'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
            '_MOD_web_func',
            'EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'Modfunc/locallang_modfunc1_csh.xlf'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'web_func',
            \JambageCom\TtProducts\Controller\Module\CreateLanguagesWizardModuleFunctionController::class,
            null,
            'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xlf:moduleFunction.tx_ttproducts_modfunc2'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
            '_MOD_web_func',
            'EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'Modfunc/locallang_modfunc2_csh.xlf'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
            'web_func',
            \JambageCom\TtProducts\Controller\Module\ImportFalWizardModuleFunctionController::class,
            null,
            'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xlf:moduleFunction.tx_ttproducts_modfunc3'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
            '_MOD_web_func',
            'EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'Modfunc/locallang_modfunc3_csh.xlf'
        );
    }
});

