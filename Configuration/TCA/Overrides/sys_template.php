<?php

defined('TYPO3') || die('Access denied.');

call_user_func(function ($extensionKey, $table): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript/PluginSetup/Main/', 'Shop System');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript/PluginSetup/Int/', 'Shop System Variable Content');

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('searchbox')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript/PluginSetup/Searchbox/', 'Shop System Search Box');
    }
}, 'tt_products', basename(__FILE__, '.php'));
