<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {

   if (
            version_compare(TYPO3_version, '9.5.0', '<')
    ) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile( TT_PRODUCTS_EXT, 'Configuration/TypoScript/PluginSetup/Main/', 'Shop System');
    } else {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile( TT_PRODUCTS_EXT, 'Configuration/TypoScript/PluginSetup/Compatibility8.7/Main/', 'Shop System');
    }
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(TT_PRODUCTS_EXT, 'Configuration/TypoScript/PluginSetup/Int/',  'Shop System Variable Content');
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded( 'searchbox')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(TT_PRODUCTS_EXT, 'Configuration/TypoScript/PluginSetup/Searchbox/', 'Shop System Search Box');
    }

});
