<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

(function ($extensionKey): void {
    ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript/PluginSetup/Main/', 'Shop System');
    ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript/PluginSetup/Int/', 'Shop System Variable Content');

    if (ExtensionManagementUtility::isLoaded('searchbox')) {
        ExtensionManagementUtility::addStaticFile($extensionKey, 'Configuration/TypoScript/PluginSetup/Searchbox/', 'Shop System Search Box');
    }
})('tt_products');
