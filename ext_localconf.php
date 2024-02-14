<?php

defined('TYPO3') || die('Access denied.');

call_user_func(function ($extensionKey): void {
    if (!defined('TT_PRODUCTS_EXT')) {
        define('TT_PRODUCTS_EXT', 'tt_products');
    }

    if (!defined('PATH_BE_TTPRODUCTS')) {
        define('PATH_BE_TTPRODUCTS', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey));
    }

    if (!defined('PATH_FE_TTPRODUCTS_REL')) {
        define(
            'PATH_FE_TTPRODUCTS_REL',
            \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey)
            )
        );
    }

    if (!defined('ADDONS_EXT')) {
        define('ADDONS_EXT', 'addons_tt_products');
    }

    if (!defined('POOL_EXT')) {
        define('POOL_EXT', 'pool');
    }

    // The autoloader does not work in ext_localconf.php and in the folder Configuratin/TCA
    require_once PATH_BE_TTPRODUCTS . 'control/class.tx_ttproducts_control_address.php';
    require_once PATH_BE_TTPRODUCTS . 'Classes/Domain/Model/Dto/EmConfiguration.php';

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(POOL_EXT)) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/pool/mod_main/index.php']['addClass'][] = 'EXT:' . $extensionKey . '/hooks/class.tx_ttproducts_hooks_pool.php:&tx_ttproducts_hooks_pool';
    }

    if (!defined('TT_PRODUCTS_DIV_DLOG')) {
        define('TT_PRODUCTS_DIV_DLOG', '0');	// for development error logging
    }

    if (!defined('TAXAJAX_EXT')) {
        define('TAXAJAX_EXT', 'taxajax');
    }

    if (
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(TAXAJAX_EXT)
    ) {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['taxajax_include'][$extensionKey] = \JambageCom\TtProducts\Controller\TaxajaxController::class . '::processRequest';
    }

    $extensionConfiguration = [];
    $originalConfiguration = [];

    $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get($extensionKey);

    if (
        isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]) &&
        is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey])
    ) {
        $originalConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey];
    }

    if (
        !isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['exclude']) ||
        !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['exclude'])
    ) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['exclude'] = [];
    }

    if (
        isset($extensionConfiguration) && is_array(
            $extensionConfiguration
        )) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey] =
            array_merge($extensionConfiguration, $originalConfiguration);
    } elseif (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey])) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey] = [];
    }

    $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey];

    if (isset($extensionConfiguration) && is_array($extensionConfiguration)) {
        if (
            isset($extensionConfiguration['exclude']) && is_array($extensionConfiguration['exclude'])
        ) {
            $excludeArray = [];
            foreach ($extensionConfiguration['exclude'] as $tablename => $excludefields) {
                if ($excludefields != '' && is_string($excludefields)) {
                    $excludeArray[$tablename] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $excludefields);
                }
            }

            if (count($excludeArray)) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['exclude'] = $excludeArray;
            }
        }
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_language=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_cat=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_cat_language=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_articles=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_articles_language=1');

    // replace the output of the former CODE field with the flexform
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][5][] = 'JambageCom\\TtProducts\\Hooks\\CmsBackend->pmDrawItem';

    // class for displaying the category tree in BE forms.
    $listType = $extensionKey . '_pi_int';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$listType][] = 'JambageCom\\TtProducts\\Hooks\\CmsBackend->pmDrawItem';

    if (
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('searchbox')) {
        $listType = $extensionKey . '_pi_search';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$listType][] = 'JambageCom\\TtProducts\\Hooks\\CmsBackend->pmDrawItem';
    }

    // hooks for FE extensions
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'][$extensionKey] = 'JambageCom\\TtProducts\\Hooks\\FrontendProcessor->loginConfirmed';

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('patch10011')) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['includeLibs'][$extensionKey] =
        \JambageCom\TtProducts\UserFunc\MatchCondition::class;
    }

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ws_flexslider']['listAction'][$extensionKey] = 'EXT:' . $extensionKey . '/hooks/class.tx_ttproducts_ws_flexslider.php:&tx_ttproducts_ws_flexslider';

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['transactor']['listener'][$extensionKey] = \JambageCom\TtProducts\Hooks\TransactorListener::class;

    if (
        isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['hook.']) &&
        !empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['hook.']['setPageTitle'])
    ) {
        // TYPO3 page title
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'JambageCom\\TtProducts\\Hooks\\ContentPostProcessor->setPageTitle';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'][] = 'JambageCom\\TtProducts\\Hooks\\ContentPostProcessor->setPageTitle';
    }

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\JambageCom\Div2007\Hooks\Evaluation\Double6::class] = '';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($extensionKey, 'pi_int/class.tx_ttproducts_pi_int.php', '_pi_int', 'list_type', 0);

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('searchbox')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($extensionKey, 'pi_search/class.tx_ttproducts_pi_search.php', '_pi_search', 'list_type', 0);
    }

    // add missing setup for the tt_content "list_type = 5" which is used by tt_products
    $addLine = 'tt_content.list.20.5 = < plugin.tt_products';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        $extensionKey,
        'setup',
        '
    # Setting ' . $extensionKey . ' plugin TypoScript
    ' . $addLine . '
    ',
        43
    );

    $addressExtKey = '';

    $addressTable = tx_ttproducts_control_address::getAddressTablename($addressExtKey);

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['addressTable'] = $addressTable;

    // Register Status Report Hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['Shop System'][] = \JambageCom\TtProducts\Hooks\StatusProvider::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['productMMArticleTtProducts']
        = \JambageCom\TtProducts\Updates\ProductMMArticleTtProductsUpdater::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['productMMGraduatedPriceTtProducts']
        = \JambageCom\TtProducts\Updates\ProductMMGraduatedPriceTtProductsUpdater::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['OrderMMProductTtProducts']
        = \JambageCom\TtProducts\Updates\OrderMMProductTtProductsUpdater::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['productDatasheetTtProducts']
        = \JambageCom\TtProducts\Updates\ProductDatasheetUpdater::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['productImageTtProducts']
        = \JambageCom\TtProducts\Updates\ProductImageUpdater::class;

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['JambageCom']['TtProducts']['Api']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::WARNING] = [
        // configuration for WARNING severity, including all
        // levels with higher severity (ERROR, CRITICAL, EMERGENCY)
        // add a SyslogWriter
        \TYPO3\CMS\Core\Log\Writer\SyslogWriter::class => [],
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1613165400] = [
        'nodeName' => 'orderedProductsElement',
        'priority' => 40,
        'class' => \JambageCom\TtProducts\Form\Element\OrderedProductsElement::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1613221454] = [
        'nodeName' => 'orderHtmlElement',
        'priority' => 40,
        'class' => \JambageCom\TtProducts\Form\Element\OrderHtmlElement::class,
    ];
    $excludedParameters = [
        'tt_products[sword]',
        'sword',
        'tt_products[activity][verify]',
        'tt_products[backPID]',
    ];
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] =
        array_merge($GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'], $excludedParameters);
}, 'tt_products');
