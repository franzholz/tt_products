<?php
defined('TYPO3_MODE') || die('Access denied.');
defined('TYPO3_version') || die('The constant TYPO3_version is undefined in tt_products!');

// these constants shall be used in the future:
if (!defined ('TT_PRODUCTS_EXT')) {
    define('TT_PRODUCTS_EXT', 'tt_products');
}

call_user_func(function () {

    $extensionConfiguration = array();

    if (
        version_compare(TYPO3_version, '9.0.0', '>=')
    ) {
        $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get(TT_PRODUCTS_EXT);
    } else {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][TT_PRODUCTS_EXT]);
    }

    if (!defined ('PATH_BE_TTPRODUCTS')) {
        define('PATH_BE_TTPRODUCTS', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(TT_PRODUCTS_EXT));
    }

    if (version_compare(TYPO3_version, '7.0.0', '>=')) {
        if (!defined ('PATH_TTPRODUCTS_ICON_TABLE_REL')) {
            define('PATH_TTPRODUCTS_ICON_TABLE_REL', 'EXT:' . TT_PRODUCTS_EXT . '/res/icons/table/');
        }
    } else {
        if (!defined ('PATH_BE_TTPRODUCTS_REL')) {
            define('PATH_BE_TTPRODUCTS_REL', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath(TT_PRODUCTS_EXT));
        }

        if (!defined ('PATH_TTPRODUCTS_ICON_TABLE_REL')) {
            define('PATH_TTPRODUCTS_ICON_TABLE_REL', PATH_BE_TTPRODUCTS_REL . 'res/icons/table/');
        }
    }

    if (!defined ('PATH_FE_TTPRODUCTS_REL')) {
        if (
            version_compare(TYPO3_version, '9.0.0', '>=')
        ) {
            define(
                'PATH_FE_TTPRODUCTS_REL',
                \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
                    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(TT_PRODUCTS_EXT)
                )
            );
        } else {
            define('PATH_FE_TTPRODUCTS_REL', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath(TT_PRODUCTS_EXT));
        }
    }

    if (!defined ('ADDONS_EXT')) {
        define('ADDONS_EXT', 'addons_tt_products');
    }

    if (!defined ('PARTY_EXT')) {
        define('PARTY_EXT', 'party');
    }

    if (!defined ('TT_ADDRESS_EXT')) {
        define('TT_ADDRESS_EXT', 'tt_address');
    }

    if (!defined ('PARTNER_EXT')) {
        define('PARTNER_EXT', 'partner');
    }

    if (!defined ('POOL_EXT')) {
        define('POOL_EXT', 'pool');
    }

    // deprecated constants
    if (!defined ('TT_PRODUCTS_EXTkey')) {
        define('TT_PRODUCTS_EXTkey', TT_PRODUCTS_EXT);
    }

    if (!defined ('PATH_BE_ttproducts')) {
        define('PATH_BE_ttproducts', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(TT_PRODUCTS_EXT));
    }

    if (!defined ('TABLE_EXTkey')) {
        define('TABLE_EXTkey','table');
    }

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(TABLE_EXTkey)) {
        if (!defined ('PATH_BE_table')) {
            define('PATH_BE_table', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(TABLE_EXTkey));
        }
    }

    if (!defined ('ADDONS_EXTkey')) {
        define('ADDONS_EXTkey','addons_tt_products');
    }

    if (!defined ('TT_ADDRESS_EXTkey')) {
        define('TT_ADDRESS_EXTkey','tt_address');
    }

    if (!defined ('PARTNER_EXTkey')) {
        define('PARTNER_EXTkey','partner');
    }

    if (!defined ('PARTY_EXTkey')) {
        define('PARTY_EXTkey','party');
    }

    if (!defined ('DIV2007_EXTkey')) {
        define('DIV2007_EXTkey','div2007');
    }

    if (!defined ('POOL_EXTkey')) {
        define('POOL_EXTkey','pool');
    }

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(DIV2007_EXTkey)) {
        if (!defined ('PATH_BE_div2007')) {
            define('PATH_BE_div2007', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(DIV2007_EXTkey));
        }
    }

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(POOL_EXTkey)) {
        if (!defined ('PATH_BE_pool')) {
            define('PATH_BE_pool', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(POOL_EXTkey));
        }
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/pool/mod_main/index.php']['addClass'][] = 'EXT:'.TT_PRODUCTS_EXT.'/hooks/class.tx_ttproducts_hooks_pool.php:&tx_ttproducts_hooks_pool';
    }

    if (!defined ('TT_PRODUCTS_DIV_DLOG')) {
        define('TT_PRODUCTS_DIV_DLOG', '0');	// for development error logging
    }

    if (!defined ('TAXAJAX_EXT')) {
        define('TAXAJAX_EXT','taxajax');
    }

    if (!defined ('STATIC_INFO_TABLES_TAXES_EXT')) {
        define('STATIC_INFO_TABLES_TAXES_EXT','static_info_tables_taxes');
    }

    if (
        TYPO3_MODE == 'FE' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(TAXAJAX_EXT)
    ) {
        if (
            version_compare(TYPO3_version, '9.5.0', '<')
        ) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][TT_PRODUCTS_EXT] =  \JambageCom\TtProducts\Controller\TaxajaxController::class . '::processRequest';
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['taxajax_include'][TT_PRODUCTS_EXT] =  \JambageCom\TtProducts\Controller\TaxajaxController::class . '::processRequest';
        }
    }

    if (
        TYPO3_MODE == 'FE' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(TAXAJAX_EXT)
    ) {
        if (
            version_compare(TYPO3_version, '9.5.0', '<')
        ) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][TT_PRODUCTS_EXT] =  \JambageCom\TtProducts\Controller\TaxajaxController::class . '::processRequest';
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['taxajax_include'][TT_PRODUCTS_EXT] =  \JambageCom\TtProducts\Controller\TaxajaxController::class . '::processRequest';
        }
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig( 'options.saveDocNew.tt_products_language=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_cat=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_cat_language=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_articles=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_articles_language=1');

    if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]))	{
        $tmpArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT];
    } else {
        unset($tmpArray);
    }

    if (isset($extensionConfiguration) && is_array($extensionConfiguration)) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] = $extensionConfiguration;
        if (isset($tmpArray) && is_array($tmpArray)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] = array_merge($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT], $tmpArray);
        }
    } else if (!isset($tmpArray)) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] = array();
    }

    if (isset($extensionConfiguration) && is_array($extensionConfiguration)) {
        if (isset($extensionConfiguration['where.']) && is_array($extensionConfiguration['where.'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.'] = $extensionConfiguration['where.'];
        }

        if (isset($extensionConfiguration['exclude.']) && is_array($extensionConfiguration['exclude.'])) {
            $excludeArray = array();
            foreach ($extensionConfiguration['exclude.'] as $tablename => $excludefields) {
                if ($excludefields != '') {
                    $excludeArray[$tablename] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $excludefields);
                }
            }

            if (count($excludeArray)) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude'] = $excludeArray;
            }
        }
    }

    if (TYPO3_MODE == 'BE') {
        // replace the output of the former CODE field with the flexform
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][5][] = 'JambageCom\\TtProducts\\Hooks\\CmsBackend->pmDrawItem';

            // class for displaying the category tree in BE forms.
        $listType = TT_PRODUCTS_EXT . '_pi_int';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$listType][] = 'JambageCom\\TtProducts\\Hooks\\CmsBackend->pmDrawItem';
        
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('searchbox')) {

            $listType = TT_PRODUCTS_EXT . '_pi_search';
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$listType][] = 'JambageCom\\TtProducts\\Hooks\\CmsBackend->pmDrawItem';
        }
    }

    if (TYPO3_MODE == 'FE') { // hooks for FE extensions

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'][TT_PRODUCTS_EXT] = 'JambageCom\\TtProducts\\Hooks\\FrontendProcessor->loginConfirmed';

        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('patch10011')) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['includeLibs'][TT_PRODUCTS_EXT] = 
            \JambageCom\TtProducts\UserFunc\MatchCondition::class;
        }

        // add the table enhancements to the FE
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_products']['extendingTCA'][] = TT_PRODUCTS_EXT;

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['transactor']['listener'][TT_PRODUCTS_EXT] = 'tx_ttproducts_hooks_transactor';

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['hook.']) &&
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['hook.']['setPageTitle']
        ) {
            // TYPO3 page title
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'JambageCom\\TtProducts\\Hooks\\ContentPostProcessor->setPageTitle';

            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'][] = 'JambageCom\\TtProducts\\Hooks\\ContentPostProcessor->setPageTitle';
        }
    }

    if (
        version_compare(TYPO3_version, '9.0.0', '<')
    ) {
        // Extending TypoScript from static template uid=43 to set up userdefined tag:
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(TT_PRODUCTS_EXT, 'editorcfg',  'tt_content.CSS_editor.ch.tt_products = < plugin.tt_products.CSS_editor', 43);

        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'])) {
            // TYPO3 4.5 - 8.7 with livesearch
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'] = array_merge(
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'],
                array(
                    'tt_products' => 'tt_products',
                    'tt_products_language' => 'tt_products_language',
                    'tt_products_articles' => 'tt_products_articles',
                    'tt_products_articles_language' => 'tt_products_articles_language',
                    'tt_products_cat' => 'tt_products_cat',
                    'tt_products_cat_language' => 'tt_products_cat_language',
                    'sys_products_orders' => 'sys_products_orders'
                )
            );
        }
    }

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['JambageCom\\Div2007\\Hooks\\Evaluation\\Double6'] = '';

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('searchbox')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(TT_PRODUCTS_EXT, 'pi_search/class.tx_ttproducts_pi_search.php', '_pi_search', 'list_type', 0 );
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(TT_PRODUCTS_EXT, 'pi_int/class.tx_ttproducts_pi_int.php', '_pi_int', 'list_type', 0 );

    // support for new Caching Framework

    $optionsArray = array();

    // add missing setup for the tt_content "list_type = 5" which is used by tt_products
    $addLine = 'tt_content.list.20.5 = < plugin.tt_products';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    TT_PRODUCTS_EXT,
    'setup', '
    # Setting ' . TT_PRODUCTS_EXT . ' plugin TypoScript
    ' . $addLine . '
    ', 43);

    if (
        TYPO3_MODE == 'BE' &&
        version_compare(TYPO3_version, '7.5.0', '>')
    ) {
        $pageType = 'ttproducts'; // a maximum of 10 characters
        $icons = array(
            'apps-pagetree-folder-contains-' . $pageType => 'apps-pagetree-folder-contains-tt_products.svg'
        );
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\IconRegistry');
        foreach ($icons as $identifier => $filename) {
            $iconRegistry->registerIcon(
                $identifier,
                $iconRegistry->detectIconProvider($filename),
                array('source' => 'EXT:' . TT_PRODUCTS_EXT . '/Resources/Public/Icons/apps/' . $filename)
            );
        }

        // Register Status Report Hook
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['Shop System'][] = \JambageCom\TtProducts\Hooks\StatusProvider::class;
    }
    
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['productMMArticleTtProducts']
        = \JambageCom\TtProducts\Updates\ProductMMArticleTtProductsUpdater::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['productMMGraduatedPriceTtProducts']
        = \JambageCom\TtProducts\Updates\ProductMMGraduatedPriceTtProductsUpdater::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['OrderMMProductTtProducts']
        = \JambageCom\TtProducts\Updates\OrderMMProductTtProductsUpdater::class;
});


