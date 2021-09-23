<?php
defined('TYPO3_MODE') || die('Access denied.');

if (!defined ('TT_PRODUCTS_EXT')) {
    define('TT_PRODUCTS_EXT', 'tt_products');
}


call_user_func(function () {
    if (!defined ('PATH_BE_TTPRODUCTS')) {
        define('PATH_BE_TTPRODUCTS', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(TT_PRODUCTS_EXT));
    }

    if (!defined ('PATH_TTPRODUCTS_ICON_TABLE_REL')) {
        define('PATH_TTPRODUCTS_ICON_TABLE_REL', 'EXT:' . TT_PRODUCTS_EXT . '/res/icons/table/');
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

    if (!defined ('EXTERNAL_FIELD_PREFIX')) {
        define('EXTERNAL_FIELD_PREFIX', 'tx_ttproducts_');
    }

    // The autoloader does not work in ext_localconf.php and in the folder Configuratin/TCA
    require_once(PATH_BE_TTPRODUCTS . 'control/class.tx_ttproducts_control_address.php');

    require_once(PATH_BE_TTPRODUCTS . 'Classes/Domain/Model/Dto/EmConfiguration.php');

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(POOL_EXT)) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/pool/mod_main/index.php']['addClass'][] = 'EXT:' . TT_PRODUCTS_EXT . '/hooks/class.tx_ttproducts_hooks_pool.php:&tx_ttproducts_hooks_pool';
    }

    if (!defined ('TT_PRODUCTS_DIV_DLOG')) {
        define('TT_PRODUCTS_DIV_DLOG', '0');	// for development error logging
    }

    if (!defined ('TAXAJAX_EXT')) {
        define('TAXAJAX_EXT', 'taxajax');
    }

    if (!defined ('DAM_EXT')) {
        define('DAM_EXT', 'dam');
    }

    if (!defined ('STATIC_INFO_TABLES_TAXES_EXT')) {
        define('STATIC_INFO_TABLES_TAXES_EXT', 'static_info_tables_taxes');
    }

    if (
        TYPO3_MODE == 'FE' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(TAXAJAX_EXT)
    ) {
        if (
            version_compare(TYPO3_version, '10.4.0', '>=')
        ) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['taxajax_include'][TT_PRODUCTS_EXT] =  \JambageCom\TtProducts\Controller\TaxajaxController::class . '::processRequest';
        } else if (
            version_compare(TYPO3_version, '9.5.0', '<')
        ) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][TT_PRODUCTS_EXT] =  \JambageCom\TtProducts\Controller\OldTaxajaxController::class . '::processRequest';
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['taxajax_include'][TT_PRODUCTS_EXT] =  \JambageCom\TtProducts\Controller\OldTaxajaxController::class . '::processRequest';
        }
    }

    $extensionConfiguration = array();
    $originalConfiguration = array();

    if (
        defined('TYPO3_version') &&
        version_compare(TYPO3_version, '9.0.0', '>=')
    ) {
        $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get(TT_PRODUCTS_EXT);
    } else { // before TYPO3 9
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][TT_PRODUCTS_EXT]);
    }

    if (
        isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]) &&
        is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT])
    ) {
        $originalConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT];
    }

    if (
        isset($extensionConfiguration) && is_array($extensionConfiguration
    )) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] =
            array_merge($extensionConfiguration, $originalConfiguration);
    } else if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT])) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] = array();
    }

    $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT];

    if (isset($extensionConfiguration) && is_array($extensionConfiguration)) {
        $excludeConf =  
            (version_compare(TYPO3_version, '10.0.0', '>=') ? 
                $extensionConfiguration['exclude']['configuration'] :
                $extensionConfiguration['exclude.']['configuration']
            );
        if (isset($excludeConf) && is_array($excludeConf)) {
            $excludeArray = array();
            foreach ($excludeConf as $tablename => $excludefields) {
                if ($excludefields != '') {
                    $excludeArray[$tablename] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $excludefields);
                }
            }

            if (count($excludeArray)) {
                if (version_compare(TYPO3_version, '10.0.0', '>=')) {
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude'] = $excludeArray;
                } else {
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.'] = $excludeArray;
                }
            }
        }
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig( 'options.saveDocNew.tt_products_language=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_cat=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_cat_language=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_articles=1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tt_products_articles_language=1');

    if (TYPO3_MODE == 'BE') {
        // replace the output of the former CODE field with the flexform
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][5][] = 'JambageCom\\TtProducts\\Hooks\\CmsBackend->pmDrawItem';

            // class for displaying the category tree in BE forms.
        $listType = TT_PRODUCTS_EXT . '_pi_int';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$listType][] = 'JambageCom\\TtProducts\\Hooks\\CmsBackend->pmDrawItem';
    }

    if (TYPO3_MODE == 'BE' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('searchbox')) {

        $listType = TT_PRODUCTS_EXT . '_pi_search';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$listType][] = 'JambageCom\\TtProducts\\Hooks\\CmsBackend->pmDrawItem';
    }


    if (TYPO3_MODE == 'FE') { // hooks for FE extensions

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'][TT_PRODUCTS_EXT] = 'JambageCom\\TtProducts\\Hooks\\FrontendProcessor->loginConfirmed';

        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('patch10011')) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['includeLibs'][TT_PRODUCTS_EXT] = 
            \JambageCom\TtProducts\UserFunc\MatchCondition::class;
        }

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ws_flexslider']['listAction'][TT_PRODUCTS_EXT] = 'EXT:' . TT_PRODUCTS_EXT . '/hooks/class.tx_ttproducts_ws_flexslider.php:&tx_ttproducts_ws_flexslider';

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

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['JambageCom\\Div2007\\Hooks\\Evaluation\\Double6'] = '';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(TT_PRODUCTS_EXT, '_pi_int/class.tx_ttproducts_pi_int.php', '_pi_int', 'list_type', 0 );

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('searchbox')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(TT_PRODUCTS_EXT, 'pi_search/class.tx_ttproducts_pi_search.php', '_pi_search', 'list_type', 0 );
    }

    // add missing setup for the tt_content "list_type = 5" which is used by tt_products
    $addLine = 'tt_content.list.20.5 = < plugin.tt_products';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        TT_PRODUCTS_EXT,
        'setup', '
    # Setting ' . TT_PRODUCTS_EXT . ' plugin TypoScript
    ' . $addLine . '
    ',
        43
    );

    $addressExtKey = '';

    $addressTable = tx_ttproducts_control_address::getAddressTablename($addressExtKey);

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addressTable'] = $addressTable;

    if (
        version_compare(TYPO3_version, '10.0.0', '>=') &&
        !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude'])
    ) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude'] = [];
    } else if (
        version_compare(TYPO3_version, '10.0.0', '<') &&
        !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.'])
    ) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.'] = [];
    }

    if (TYPO3_MODE == 'BE' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('db_list')) {

        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\\TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
        $signalSlotDispatcher->connect(
            '\\JambageCom\\DbList\\RecordList\\DatabaseRecordList',     // Signal class name
            'beforeSetCsvRow',                                           // Signal name
            '\\JambageCom\\TtProducts\\Slots\\DatabaseRecordListSlots', // Slot class name
            'addValuesToCsvRow'                                          // Slot name
        );

    }

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
        'tt_products[backPID]'
    ];
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = 
        array_merge($GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'], $excludedParameters);
});


