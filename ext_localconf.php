<?php
if (!defined ('TYPO3_MODE'))	die ('Access denied.');

$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:

$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';
$divClass = '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
	$divClass = 't3lib_div';
}

// these constants shall be used in the future:
if (!defined ('TT_PRODUCTS_EXT')) {
	define('TT_PRODUCTS_EXT', $_EXTKEY);
}


if (!defined ('TT_PRODUCTS_EXT')) {
	define('TT_PRODUCTS_EXT', $_EXTKEY);
}

if (!defined ('PATH_BE_TTPRODUCTS')) {
	define('PATH_BE_TTPRODUCTS', call_user_func($emClass . '::extPath', $_EXTKEY));
}


if (version_compare(TYPO3_version, '7.0.0', '>=')) {
    if (!defined ('PATH_TTPRODUCTS_ICON_TABLE_REL')) {
        define('PATH_TTPRODUCTS_ICON_TABLE_REL', 'EXT:' . $_EXTKEY . '/res/icons/table/');
    }
} else {
    if (!defined ('PATH_BE_TTPRODUCTS_REL')) {
        define('PATH_BE_TTPRODUCTS_REL', call_user_func($emClass . '::extRelPath', $_EXTKEY));
    }

    if (!defined ('PATH_TTPRODUCTS_ICON_TABLE_REL')) {
        define('PATH_TTPRODUCTS_ICON_TABLE_REL', PATH_BE_TTPRODUCTS_REL . 'res/icons/table/');
    }
}

if (!defined ('PATH_FE_TTPRODUCTS_REL')) {
	define('PATH_FE_TTPRODUCTS_REL', call_user_func($emClass . '::siteRelPath', $_EXTKEY));
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
	define('TT_PRODUCTS_EXTkey', $_EXTKEY);
}


if (!defined ('PATH_BE_ttproducts')) {
	define('PATH_BE_ttproducts', call_user_func($emClass . '::extPath', TT_PRODUCTS_EXT));
}

if (!defined ('PATH_FE_ttproducts_rel')) {
	define('PATH_FE_ttproducts_rel', call_user_func($emClass . '::siteRelPath', TT_PRODUCTS_EXT));
}

if (!defined ('TABLE_EXTkey')) {
	define('TABLE_EXTkey','table');
}

if (call_user_func($emClass . '::isLoaded', TABLE_EXTkey)) {
	if (!defined ('PATH_BE_table')) {
		define('PATH_BE_table', call_user_func($emClass . '::extPath', TABLE_EXTkey));
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

if (call_user_func($emClass . '::isLoaded', DIV2007_EXTkey)) {
	if (!defined ('PATH_BE_div2007')) {
		define('PATH_BE_div2007', call_user_func($emClass . '::extPath', DIV2007_EXTkey));
	}
}

if (call_user_func($emClass . '::isLoaded', POOL_EXTkey)) {
	if (!defined ('PATH_BE_pool')) {
		define('PATH_BE_pool', call_user_func($emClass . '::extPath', POOL_EXTkey));
	}
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/pool/mod_main/index.php']['addClass'][] = 'EXT:'.TT_PRODUCTS_EXT.'/hooks/class.tx_ttproducts_hooks_pool.php:&tx_ttproducts_hooks_pool';
}

if (!defined ('TT_PRODUCTS_DIV_DLOG')) {
	define('TT_PRODUCTS_DIV_DLOG', '0');	// for development error logging
}

if (!defined ('TAXAJAX_EXT')) {
	define('TAXAJAX_EXT','taxajax');
}

if (!defined ('DAM_EXTkey')) {
	define('DAM_EXTkey','dam');
}

if (!defined ('STATIC_INFO_TABLES_TAXES_EXTkey')) {
	define('STATIC_INFO_TABLES_TAXES_EXTkey','static_info_tables_taxes');
}

if (call_user_func($emClass . '::isLoaded', TAXAJAX_EXT)) {
    if (!defined ('PATH_BE_taxajax')) {
        define('PATH_BE_taxajax', call_user_func($emClass . '::extPath', TAXAJAX_EXT));
    }
	$GLOBALS['TYPO3_CONF_VARS'] ['FE']['eID_include'][TT_PRODUCTS_EXT] =  'EXT:'.TT_PRODUCTS_EXT.'/eid/class.tx_ttproducts_eid.php' ;
}

call_user_func($emClass . '::addUserTSConfig', 'options.saveDocNew.tt_products=1');
call_user_func($emClass . '::addUserTSConfig', 'options.saveDocNew.tt_products_cat=1');
call_user_func($emClass . '::addUserTSConfig', 'options.saveDocNew.tt_products_articles=1');

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]))	{
	$tmpArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT];
} else {
	unset($tmpArray);
}


if (isset($_EXTCONF) && is_array($_EXTCONF)) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] = $_EXTCONF;
	if (isset($tmpArray) && is_array($tmpArray)) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] = array_merge($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT], $tmpArray);
	}
} else if (!isset($tmpArray)) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT] = array();
}


if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['useFlexforms'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['useFlexforms'] = '1';
}

if (!defined($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products']['MENU'])) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' =>  'title,image,itemnumber,ean,price,price2,directcost',
			'icon' => TRUE
		),
		'ext' => array (
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_ext',
			'fList' =>  'title,price2,category;inStock;weight;tax',
			'icon' => TRUE
		),
		'variants' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_variants',
			'fList' =>  'title,color;size;gradings,description',
			'icon' => TRUE
		)
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products_language'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' => 'sys_language_uid,prod_uid,title,subtitle,datasheet,www',
			'icon' => TRUE
		),
		'ext' => array (
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_ext',
			'fList' => 'sys_language_uid,prod_uid,note,note2',
			'icon' => TRUE
		),
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products_articles'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' =>  'title,itemnumber,price,inStock,image',
			'icon' => TRUE
		),
		'ext' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_ext',
			'fList' =>  'title;price2,color;size;gradings',
			'icon' => TRUE
		)
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products_cat'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' =>  'title,image',
			'icon' => TRUE
		)
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tt_products_cat_language'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' => 'sys_language_uid,title,subtitle,cat_uid',
			'icon' => TRUE
		),
		'ext' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_ext',
			'fList' => 'sys_language_uid,title,note',
			'icon' => TRUE
		)
	);

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['sys_products_orders'] = array (
		'default' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_default',
			'fList' => 'name,zip,city,country,sys_language_uid,tracking_code,amount,tax_mode,pay_mode,date_of_payment,date_of_delivery,bill_no',
			'icon' => TRUE
		),
		'ext' => array(
			'MENU' => 'LLL:EXT:tt_products/locallang.xml:m_ext',
			'fList' => 'name,first_name,last_name,feusers_uid,address,telephone,email,date_of_birth,status,note',
			'icon' => TRUE
		)
	);
}

if (isset($_EXTCONF) && is_array($_EXTCONF)) {
	if (isset($_EXTCONF['where.']) && is_array($_EXTCONF['where.'])) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.'] = $_EXTCONF['where.'];
	}

	if (isset($_EXTCONF['exclude.']) && is_array($_EXTCONF['exclude.'])) {
		$excludeArray = array();
		foreach ($_EXTCONF['exclude.'] as $tablename => $excludefields) {
			if ($excludefields != '') {
				$excludeArray[$tablename] = call_user_func($divClass . '::trimExplode',  ',', $excludefields);
			}
		}

		if (count($excludeArray)) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude'] = $excludeArray;
		}
	}
}


if (
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['useFlexforms']
) {
	// replace the output of the former CODE field with the flexform
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][5][] = 'EXT:'.TT_PRODUCTS_EXT.'/hooks/class.tx_ttproducts_hooks_cms.php:&tx_ttproducts_hooks_cms->pmDrawItem';
}

if (call_user_func($emClass . '::isLoaded', 'searchbox')) {

	$listType = TT_PRODUCTS_EXT.'_pi_search';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$listType][] = 'EXT:'.TT_PRODUCTS_EXT.'/hooks/class.tx_ttproducts_hooks_cms.php:&tx_ttproducts_hooks_cms->pmDrawItem';
}

if (TYPO3_MODE == 'FE') { // hooks for FE extensions

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'][TT_PRODUCTS_EXT] = 'EXT:'.TT_PRODUCTS_EXT.'/hooks/class.tx_ttproducts_hooks_fe.php:&tx_ttproducts_hooks_fe->resetAdresses';

	if (call_user_func($emClass . '::isLoaded', 'patch10011')) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['includeLibs'][TT_PRODUCTS_EXT] = 'EXT:'.TT_PRODUCTS_EXT.'/hooks/class.tx_ttproducts_match_condition.php:&tx_ttproducts_match_condition';
	}

	// add the table enhancements to the FE
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_products']['extendingTCA'][] = TT_PRODUCTS_EXT;

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['transactor']['listener'][TT_PRODUCTS_EXT] = 'tx_ttproducts_hooks_transactor';

    if (
        isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['hook.']) &&
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['hook.']['setPageTitle'] &&
        version_compare(TYPO3_version, '6.1.0', '>') &&
        version_compare(PHP_VERSION, '5.5.0', '>=')
    ) {
        // TYPO3 page title
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'JambageCom\\TtProducts\\Hooks\\ContentPostProcessor->setPageTitle';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'][] = 'JambageCom\\TtProducts\\Hooks\\ContentPostProcessor->setPageTitle';
    }
}

if (TYPO3_MODE == 'BE') {
		// class for displaying the category tree in BE forms.
	include_once(PATH_BE_ttproducts . 'hooks/class.tx_ttproducts_hooks_be.php');
}


$listType = TT_PRODUCTS_EXT.'_pi_int';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$listType][] = 'EXT:' . TT_PRODUCTS_EXT . '/hooks/class.tx_ttproducts_hooks_cms.php:&tx_ttproducts_hooks_cms->pmDrawItem';


  // Extending TypoScript from static template uid=43 to set up userdefined tag:
call_user_func($emClass . '::addTypoScript', $_EXTKEY,'editorcfg','tt_content.CSS_editor.ch.tt_products = < plugin.tt_products.CSS_editor ',43);


$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/mydashboard/class.tx_mydashboard_widgetmgm.php']['addWidget']['tt_products_latest'] = 'EXT:' . TT_PRODUCTS_EXT . '/widgets/class.tx_ttproducts_latest.php:tx_ttproducts_latest';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_double6'] = 'EXT:' . DIV2007_EXTkey . '/hooks/class.tx_div2007_hooks_eval.php';


if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'])) {
	// TYPO3 4.5 with livesearch
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


if (call_user_func($emClass . '::isLoaded', 'searchbox')) {
	call_user_func($emClass . '::addPItoST43', $_EXTKEY, 'pi_search/class.tx_ttproducts_pi_search.php', '_pi_search', 'list_type', 0 );
}

call_user_func($emClass . '::addPItoST43', $_EXTKEY, 'pi_int/class.tx_ttproducts_pi_int.php', '_pi_int', 'list_type', 0 );




// support for new Caching Framework

$optionsArray = array();
$backendCache = 't3lib_cache_backend_NullBackend';

// Register cache 'tt_products_cache'
if (
	version_compare(TYPO3_version, '7.0.0', '<') &&
	isset($_EXTCONF['cache.']) &&
	$_EXTCONF['cache.']['backend'] &&
	!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache'])
) {
	if (
		isset($_EXTCONF['cache.']['options.']) &&
		$_EXTCONF['cache.']['options.']['servers'] != ''
	) {
		$optionsArray['servers'] = array($_EXTCONF['cache.']['options.']['servers']);
	}

	if (
		extension_loaded('memcache') &&
		isset($optionsArray['servers']) &&
		is_array($optionsArray['servers'])
	) {
		$backendCache = 't3lib_cache_backend_MemcachedBackend';
	} else if (extension_loaded('apc') || extension_loaded('apcu')) {
		$backendCache = 't3lib_cache_backend_ApcBackend';
	} else if (extension_loaded('redis')) {
		$backendCache = 't3lib_cache_backend_RedisBackend';
	}
}

if (
	version_compare(TYPO3_version, '7.0.0', '<') &&
	!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache'])
) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache'] = array();
}

// Define string frontend as default frontend, this must be set with TYPO3 4.5 and below
// and overrides the default variable frontend of 4.6
if (
	version_compare(TYPO3_version, '7.0.0', '<') &&
	!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['frontend'])
) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['frontend'] = 't3lib_cache_frontend_StringFrontend';
}

if (
	version_compare(TYPO3_version, '4.6.0', '>=') &&
	version_compare(TYPO3_version, '7.0.0', '<')
) {
	if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['backend'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['backend'] = $backendCache;
	}

	if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options'])) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options'] = $optionsArray;
	}
}


if (version_compare(TYPO3_version, '4.6.0', '<')) {

	call_user_func($emClass . '::addPItoST43', $_EXTKEY, 'pi1/class.tx_ttproducts_pi1.php', '_pi1', 'list_type', 1);

	// Define database backend as backend for 4.5 and below (default in 4.6)
	if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['backend'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['backend'] = 't3lib_cache_backend_DbBackend';
    }
	// Define data and tags table for 4.5 and below (obsolete in 4.6)
	if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options'] = array();
    }
	if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options']['cacheTable'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options']['cacheTable'] = 'tt_products_cache';
    }
	if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options']['tagsTable'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options']['tagsTable'] = 'tt_products_cache_tags';
    }

	t3lib_cache::initializeCachingFramework();
} else {
	// add missing setup for the tt_content "list_type = 5" which is used by tt_products
	$addLine = 'tt_content.list.20.5 = < plugin.tt_products';
	call_user_func($emClass . '::addTypoScript', TT_PRODUCTS_EXT, 'setup', '
	# Setting ' . TT_PRODUCTS_EXT . ' plugin TypoScript
	' . $addLine . '
	', 43);
}


if (
	version_compare(TYPO3_version, '7.0.0', '<') &&
	isset($GLOBALS['typo3CacheFactory']) &&
	is_object($GLOBALS['typo3CacheFactory']) &&
	(
		version_compare(TYPO3_version, '4.6.0', '>=') ||
		TYPO3_UseCachingFramework
	)
) {
    // register the cache in BE so it will be cleared with "clear all caches"
    try {
		$cacheName = 'tt_products_cache';
		if (!$GLOBALS['typo3CacheManager']->hasCache($cacheName)) {
			$GLOBALS['typo3CacheFactory']->create(
				'tt_products_cache',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['frontend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['options']
			);
		}
    } catch (t3lib_cache_exception_DuplicateIdentifier $e) {
        // do nothing, a tt_products_cache cache already exists
    }
}


if (
    TYPO3_MODE == 'BE' &&
    version_compare(TYPO3_version, '7.5.0', '>')
) {
    $pageType = 'ttproducts'; // a maximum of 10 characters
    $icons = array(
        'apps-pagetree-folder-contains-' . $pageType => 'apps-pagetree-folder-contains-tt_products.svg'
    );
    $iconRegistry = call_user_func($divClass . '::makeInstance', 'TYPO3\\CMS\\Core\\Imaging\\IconRegistry');
    foreach ($icons as $identifier => $filename) {
        $iconRegistry->registerIcon(
            $identifier,
            $iconRegistry->detectIconProvider($filename),
            array('source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/apps/' . $filename)
        );
    }
}
