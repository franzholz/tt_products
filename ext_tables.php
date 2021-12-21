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

        if (version_compare(TYPO3_version, '7.0', '>=')) {
            $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']['JambageCom\\TtProducts\\Controller\\Plugin\\WizardIcon'] = PATH_BE_TTPRODUCTS . 'Classes/Controller/Plugin/WizardIcon.php';
        } else {
            $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']['tx_ttproducts_wizicon'] = PATH_BE_TTPRODUCTS . 'class.tx_ttproducts_wizicon.php';
        }

        call_user_func(
            $emClass . '::insertModuleFunction',
            'web_func',
            'tx_ttproducts_modfunc1',
            PATH_BE_TTPRODUCTS . 'modfunc1/class.tx_ttproducts_modfunc1.php',
            'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xlf:moduleFunction.tx_ttproducts_modfunc1',
            'wiz'
        );

        call_user_func(
            $emClass . '::insertModuleFunction',
            'web_func',
            'tx_ttproducts_modfunc2',
            PATH_BE_TTPRODUCTS . 'modfunc2/class.tx_ttproducts_modfunc2.php',
            'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xlf:moduleFunction.tx_ttproducts_modfunc2',
            'wiz'
        );
    }
});

