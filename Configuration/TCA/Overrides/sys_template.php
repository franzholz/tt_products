<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile( TT_PRODUCTS_EXT, 'Configuration/TypoScript/PluginSetup/Main/', 'Shop System');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(TT_PRODUCTS_EXT, 'Configuration/TypoScript/PluginSetup/Int/',  'Shop System Variable Content');

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded( 'searchbox')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(TT_PRODUCTS_EXT, 'Configuration/TypoScript/PluginSetup/Searchbox/', 'Shop System Search Box');
    }    
});
