<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2012 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the tt_products (Shop System) extension.
 *
 * main loop
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_main implements \TYPO3\CMS\Core\SingletonInterface {
		// Internal
	public $uid_list='';			// List of existing uid's from the basket, set by initBasket()
	public $orderRecord = array();		// Will hold the order record if fetched.

		// Internal: init():
	public $config=array();			// updated configuration
	public $conf;
	public $tt_product_single=array();
	public $control;			// object for the control of the application
	public $singleView;			// single view object
	public $memoView;			// memo view and data object

	public $pid;				// the page to which the script shall go
	public $piVars;
	public $ajax;				// ajax object
	public $javaScriptObj;			// JavaScript object
	public $codeArray;			// Codes
	public $pageAsCategory;			// > 0 if pages are used as categories
	/**
	 * Content object
	 * @var tslib_cObj
	 */
	public $cObj;
	protected $bSingleFromList = false;	// if the single view shall be shown instead of a list view
	public $pibaseClass;			// class of the pibase object
	/**
	 * The list of codes that must run uncached. Note that if you combine any
	 * of these codes with cached codes in TS or flexform, those cached will
	 * be rendered uncached too! Better insert two or more instances of
	 * tt_products where cached and uncached codes are separate.
	 *
	 * @var array
	 */
	protected static $uncachedCodes = array(
		'BASKET',
		'BILL',
		'CURRENCY',
		'DELIVERY',
		'FINALIZE',
		'HELP',
		'INFO',
		'MEMO',
		'ORDERS',
		'OVERVIEW',
		'PAYMENT',
		'SEARCH',
		'TRACKING'
	);


	/**
	 * does the initialization stuff
	 *
	 * @param	string		content string
	 * @param	string		configuration array
	 * @param	string		modified configuration array
	 * @return	  bool		if true processing should be done
	 */
	public function init (&$content, &$conf, &$config, $pibaseClass, &$errorCode, $bRunAjax = false) {
		$rc = true;
		$this->setSingleFromList(false);
		$this->tt_product_single = array();

		$pibaseObj = GeneralUtility::makeInstance(''.$pibaseClass);
		$this->cObj = &$pibaseObj->cObj;
		$flexformArray = GeneralUtility::xml2array($this->cObj->data['pi_flexform']);
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

		$flexformTyposcript = tx_div2007_ff::get($flexformArray, 'myTS');

		if($flexformTyposcript) {
			$tsparser = tx_div2007_core::newTsParser();
			// Copy conf into existing setup
			$tsparser->setup = $conf;
			// Parse the new Typoscript
			$tsparser->parse($flexformTyposcript);
			// Copy the resulting setup back into conf
			$conf = $tsparser->setup;
		}
		$this->conf = &$conf;
		$this->config = &$config;
		$this->piVars = &$pibaseObj->piVars;
		$this->pibaseClass = $pibaseClass;
		$config['LLkey'] = $pibaseObj->LLkey;

			// basket
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003(TT_PRODUCTS_EXT);
		$this->config['version'] = $eInfo['version'];
		// Save the original flexform in case if we need it later as USER_INT
		$this->cObj->data['_original_pi_flexform'] = $this->cObj->data['pi_flexform'];
		$this->cObj->data['pi_flexform'] = GeneralUtility::xml2array($this->cObj->data['pi_flexform']);

		$config['code'] =
			tx_div2007_alpha5::getSetupOrFFvalue_fh004(
				$this->cObj,
	 			$conf['code'],
	 			$conf['code.'],
				$conf['defaultCode'],
				$this->cObj->data['pi_flexform'],
				'display_mode',
				true
			);

		$this->codeArray = GeneralUtility::trimExplode(',', $config['code'],1);
		$required_pivars = tx_div2007_ff::get($flexformArray, 'required_pivars');

		$requiredArray = GeneralUtility::trimExplode(',', $required_pivars);
		$bDoProcessing = true;
		if (count($requiredArray))	{
			foreach ($requiredArray as $k => $pivar)	{
				if ($pivar && $pivar != 'empty')	   {
					$gpVar = GeneralUtility::_GP($pivar);
					if (
						!isset($this->piVars[$pivar]) &&
						!isset($gpVar)
					)	{
						$bDoProcessing = false;
						break;
					}
				}
			}
		}

		if ($bDoProcessing && (method_exists($this->cObj, 'getUserObjectType') && $this->cObj->getUserObjectType() == \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER)) {
			$intersection = array_intersect(self::$uncachedCodes, $this->codeArray);
			if (!empty($intersection)) {
				if ($this->convertToUserInt()) {
                    $bDoProcessing = false;
				}
			}
		}
		if (!$bDoProcessing)	{
			return false;
		}
		$urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
		$urlObj->init($pibaseObj, $conf);

			// initialise AJAX at the beginning because the AJAX functions can set piVars
		if (!$bRunAjax && ExtensionManagementUtility::isLoaded('taxajax')) {
			$this->ajax = GeneralUtility::makeInstance('tx_ttproducts_ajax');
			$this->ajax->init();

			$this->ajax->main(
				$this->cObj,
				$urlObj,
				$conf['ajaxDebug'],
				'product',
				'cat'
			);
		}
		$config['defaultCategoryID'] = tx_div2007_ff::get($flexformArray, 'categorySelection');

		if (!$bRunAjax) {
			// ### central initialization ###
			$db = GeneralUtility::makeInstance('tx_ttproducts_db');
			$db->init($conf, $config, $this->ajax, $pibaseObj); // this initializes tx_ttproducts_config inside of creator
		}

		if (!$bRunAjax && ExtensionManagementUtility::isLoaded('taxajax')) {
			if($_POST['xajax']){
				global $trans;
				$trans = $this;
				$this->ajax->taxajax->processRequests();
				exit();
			}
		}

		// *************************************
		// *** getting configuration values:
		// *************************************

		$backPID = $this->piVars['backPID'];
		$backPID = ($backPID ? $backPID : GeneralUtility::_GP('backPID'));

		// page where to go usually
		$this->pid = ($this->conf['PIDbasket'] && $this->conf['clickIntoBasket'] ? $this->conf['PIDbasket'] : ($backPID ? $backPID : $GLOBALS['TSFE']->id));

		if ($this->conf['TAXmode'] == '' ||  $this->conf['TAXmode'] == '{$plugin.tt_products.TAXmode}')	{
			$this->conf['TAXmode'] = 1;
		}
		$this->pageAsCategory = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'];
		// get template suffix string
		$config['templateSuffix'] = strtoupper($this->conf['templateSuffix']);

		$templateSuffix = $pibaseObj->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_suffix');
		$templateSuffix = strtoupper($templateSuffix);
		$config['templateSuffix'] = ($templateSuffix ? $templateSuffix : $config['templateSuffix']);
		$config['templateSuffix'] = ($config['templateSuffix'] ? '_'.$config['templateSuffix'] : '');

		$config['limit'] = $this->conf['limit'] ? $this->conf['limit'] : 50;
		$config['limitImage'] = tx_div2007_core::intInRange($this->conf['limitImage'], 0, 50);
		$config['limitImage'] = $config['limitImage'] ? $config['limitImage'] : 1;
		$config['limitImageSingle'] = tx_div2007_core::intInRange($this->conf['limitImageSingle'], 0, 50);
		$config['limitImageSingle'] = $config['limitImageSingle'] ? $config['limitImageSingle'] : 1;
		$recursive = ($this->cObj->data['recursive'] ? $this->cObj->data['recursive']: $this->conf['recursive']);
		$config['recursive'] = tx_div2007_core::intInRange($recursive, 0, 100);
		if ($this->conf['PIDstoreRoot'])	{
			$config['storeRootPid'] = $this->conf['PIDstoreRoot'];
		} else {
			foreach ($GLOBALS['TSFE']->tmpl->rootLine as $k => $row)	{
				if ($row['doktype'] == 1)	{
					$config['storeRootPid'] = $row['uid'];
					break;
				}
			}
		}

		if ($this->conf['priceNoReseller']) {
			$config['priceNoReseller'] = tx_div2007_core::intInRange($this->conf['priceNoReseller'], 2, 10);
		}

		if ($this->conf['pid_list'] == '{$plugin.tt_products.pid_list}')	{
			$this->conf['pid_list'] = '';
		}

		$tmp = $this->cObj->stdWrap($this->conf['pid_list'],$this->conf['pid_list.']);

		$pid_list = ($this->cObj->data['pages'] ? $this->cObj->data['pages'] : ($this->conf['pid_list.'] ? trim($tmp) : ''));
		$pid_list = ($pid_list ? $pid_list : $this->conf['pid_list']);
		$config['pid_list'] = (isset($pid_list) ? $pid_list : $config['storeRootPid']);

		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$res = $markerObj->init(
			$this->cObj,
			$pibaseObj->piVars
		);

		$rc &= $res;

		if ($res == false)	{
			$errorCode = $markerObj->getErrorCode();
			return false;
		}

			// If the current record should be displayed.
		$config['displayCurrentRecord'] = $this->conf['displayCurrentRecord'];
		if ($config['displayCurrentRecord'])	{
			$row = $this->cObj->data;
			$this->tt_product_single['product'] = $row['uid'];

		} else {
			$error_detail = '';
			$paramArray = array('product', 'article');

			foreach ($paramArray as $param)	{
				$paramVal = ($this->piVars[$param] ? $this->piVars[$param] : '');
				if ($paramVal)	{
					if (
						tx_div2007_core::testInt($paramVal)
					) {
						$this->tt_product_single[$param] = intval($paramVal);
					} else if (!is_array($paramVal)) {
						$error_detail = $param;
						break;
					}
				}
			}

			if ($error_detail != '')	{
				$errorCode[0] = 'wrong_' . $error_detail;
				$errorCode[1] = $paramVal;
				return false;
			}
		}

			// image
		$imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image');
		$imageObj->init($pibaseObj->cObj);

			// image view
		$imageViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');
		$imageViewObj->init($imageObj);

			// get all extending TCAs
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['extendingTCA']))	{
			tx_div2007_alpha5::loadTcaAdditions_fh002($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['extendingTCA']);
		}

		$tmpObj = GeneralUtility::makeInstance('tx_ttproducts_css');
		$tmpObj->init(
			$pibaseObj
		);

			// price
		$priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
		$priceViewObj->init(
			$priceObj
		);

			// graduated price
		$graduatedPriceObj = GeneralUtility::makeInstance('tx_ttproducts_graduated_price');
		$graduatedPriceObj->init($this->conf['table.']['tt_products_graduated_price'], $this->conf['table.']['tt_products_mm_graduated_price']);

		$graduatedPriceViewObj = GeneralUtility::makeInstance('tx_ttproducts_graduated_price_view');
		$graduatedPriceViewObj->init($graduatedPriceObj);

		$this->javaScriptObj = GeneralUtility::makeInstance('tx_ttproducts_javascript');

			// JavaScript
		$this->javaScriptObj->init(
			$pibaseObj,
			$this->ajax
		);

		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$subpartmarkerObj->init($this->cObj);

		$billdeliveryObj = GeneralUtility::makeInstance('tx_ttproducts_billdelivery');
		$billdeliveryObj->init(
			$this->cObj
		);

		return $rc;
	} // init


	public function destruct () {
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$tablesObj->destruct();

		$db = GeneralUtility::makeInstance('tx_ttproducts_db');
		if (is_object($db)) {
			$db->destruct();
		}
	}


	public function &run ($pibaseClass, &$errorCode, $content = '', $bRunAjax = false) {
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnf->getConf();
		$config = $cnf->getConfig();

		if ($conf['no_cache'] && $this->convertToUserInt()) {
			// Compatibility with previous versions where users could set
			// 'no_cache' TS option. This option does not exist anymore and we
			// simply convert the plugin to USER_INT if that old option is set.
			return false;
		}
		$bStoreBasket = true;
		$errorMessage = '';
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$pibaseObj = GeneralUtility::makeInstance($pibaseClass);
		$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
		$errorObj = GeneralUtility::makeInstance('tx_ttproducts_model_error');
		$showAmount = $cnf->getBasketConf('view','showAmount');
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');

		if (!count($this->codeArray) && !$bRunAjax)	{
			$this->codeArray = array('HELP');
		}

		if (ExtensionManagementUtility::isLoaded('taxajax'))	{
			if ($bRunAjax)	{
				// TODO: get AJAX configuration
			} else {
				$this->javaScriptObj->set('xajax');
			}
		}

		if (GeneralUtility::_GP('mode_update'))	{
			$updateMode = 1;
		} else {
			$updateMode = 0;
		}

		if (
			isset($conf['basket.']) && $conf['basket.']['store'] == '0' ||
			count($this->codeArray) == 1 &&
			$this->codeArray[0] == 'OVERVIEW' &&
			isset($conf['basket.']) &&
			isset($conf['basket.']['activity.']) &&
			isset($conf['basket.']['activity.']['overview.']) &&
			$conf['basket.']['activity.']['overview.']['store'] == '0'
		) {
			$bStoreBasket = false;
		}

		$basketObj->init(
			$pibaseClass,
			$updateMode,
			$this->config['pid_list'],
			$bStoreBasket
		);

		// *************************************
		// *** Listing items:
		// *************************************
        $basketExtra = tx_ttproducts_control_basket::getBasketExtra();
		$basketObj->create($basketExtra, $this->conf['useArticles'], $basketObj->getFuncTablename()); // get the basket->itemArray

		$recs = tx_ttproducts_control_basket::getRecs();

        $basketObj->calculate($basketExtra); // get the calculated arrays
		$basketObj->calculateSums();
		$voucher = $tablesObj->get('voucher');
		if (is_object($voucher)) {
            $voucher->doProcessing($recs);
        }
		$basketObj->addVoucherSums();

		$templateFile = '';
		$templateCode = $templateObj->get(
			'BASKET',
			$templateFile,
			$errorCode
		);

		if (!$errorMessage && empty($errorCode))	{

			$functablename = 'tt_products';
			tx_ttproducts_control_memo::process($functablename, $pibaseObj->piVars, $this->conf);
            $controlObj = GeneralUtility::makeInstance(tx_ttproducts_control::class);
			$controlObj->init(
				$pibaseClass,
				$conf,
				$cnf->getConfig(),
				$basketObj->getFuncTablename(),
				$this->conf['useArticles'],
				$basketExtra
			);
			$calculatedArray = $basketObj->getCalculatedSums();
			$content .= $controlObj->doProcessing(
				$this->codeArray,
				$calculatedArray,
				$basketExtra,
				$errorCode,
				$errorMessage
			);
		}

		$bErrorFound = false;
		$contentBasket = $content;
		$content = '';

		$tableArray = array_keys($tablesObj->getTableClassArray());

		foreach($this->codeArray as $theCode)	{
			$theCode = (string) trim($theCode);
			$contentTmp = '';
			if ($this->conf['fe'])	{
				$templateCode =
                    $templateObj->get(
                        $theCode,
                        $templateFile, 
                        $errorCode
                    );
			}
			if ($errorMessage || !empty($errorCode)) {
                $bErrorFound = true;
				break;
			}

			$bHidePlugin = false;

			foreach($tableArray as $functablename) {
				$tableConf = $cnf->getTableConf($functablename, $theCode);
				$hideIds = '';
				if (!empty($tableConf)) {
					if (isset($tableConf['hideID'])) {
						$hideIds = $tableConf['hideID'];
					}
					if ($hideIds != '') {
						$hideIdArray = GeneralUtility::trimExplode(',', $hideIds);
						$piVars = tx_ttproducts_model_control::getPiVars();
						$piVar = tx_ttproducts_model_control::getPiVar($functablename);
						if (isset($piVars[$piVar])) {
							$currentArray = GeneralUtility::trimExplode(',', $piVars[$piVar]);
							$hideCurrentArray = array_diff($currentArray, $hideIdArray);
							if (count($hideCurrentArray) < count($currentArray)) {
								$bHidePlugin = true;
							}
						}
					}
				}
			}

			if ($bHidePlugin) {
				continue;
			}

			switch($theCode)	{
                case 'CONTROL': // this will be introduced with tt_products 3.1
                    continue 2;
                    break;
                case 'SEARCH':
					if (!$bRunAjax && $this->convertToUserInt()) {
						return '';
					}
					// no break!
				case 'LIST':
				case 'LISTAFFORDABLE':
				case 'LISTARTICLES':
				case 'LISTGIFTS':
				case 'LISTHIGHLIGHTS':
				case 'LISTNEWITEMS':
				case 'LISTOFFERS':
				case 'LISTVIEWEDITEMS':
				case 'LISTVIEWEDMOST':
				case 'LISTVIEWEDMOSTOTHERS':

					if (count($this->tt_product_single) && !$this->conf['NoSingleViewOnList']) {
						if (!$bRunAjax && $this->convertToUserInt()) {
							return '';
						}
					}

					if (count($basketObj->itemArray) && $showAmount == 'basket') {
						if (!$bRunAjax && $this->convertToUserInt()) {
							return '';
						}
					}

					$contentTmp = $this->products_display($templateCode, $theCode, $errorMessage, $errorCode);
				break;
				case 'LISTCAT':
				case 'LISTAD':
				case 'MENUCAT':
				case 'MENUAD':
				case 'SELECTCAT':
				case 'SELECTAD':
					$codeTemplateArray = array(
						'SELECTCAT' => 'ITEM_CATEGORY_SELECT_TEMPLATE',
						'SELECTAD' => 'ITEM_ADDRESS_SELECT_TEMPLATE',
						'LISTCAT' => 'ITEM_CATLIST_TEMPLATE',
						'LISTAD' => 'ITEM_ADLIST_TEMPLATE',
						'MENUCAT' => 'ITEM_CATEGORY_MENU_TEMPLATE',
						'MENUAD' => 'ITEM_ADDRESS_MENU_TEMPLATE',
					);

					if (substr($theCode,-2,2) == 'AD')	{
						$tablename = '';
						$functablename = 'address';
						if (is_array($this->conf['table.']))	{
							$tablename = $this->conf['table.'][$functablename];
						}
						if (
							ExtensionManagementUtility == 'tx_party_addresses' && !ExtensionManagementUtility::isLoaded(PARTY_EXTkey) ||
							$tablename == 'tx_partner_main' && !ExtensionManagementUtility::isLoaded(PARTNER_EXTkey) ||
							$tablename == 'tt_address' && !ExtensionManagementUtility::isLoaded(TT_ADDRESS_EXTkey)
						) {
							$message = $languageObj->getLabel('extension_missing');
							$messageArr =  explode('|', $message);
							$extTableArray = array('tt_address' => TT_ADDRESS_EXTkey, 'tx_partner_main' => PARTNER_EXTkey, 'tx_party_addresses' => PARTY_EXTkey);
							$errorMessage = $messageArr[0] . $extTableArray[$tablename] . $messageArr[1];
						} else if (!$tablename) {
							$message = $languageObj->getLabel('setup_missing');
							$messageArr =  explode('|', $message);
							$errorMessage = $messageArr[0] . 'table.address' . $messageArr[1];
						}
					} else if (substr($theCode,-3,3) == 'CAT')	{
						if ($this->pageAsCategory)	{
							$functablename = 'pages';
						} else {
							$functablename = 'tt_products_cat';
						}
					}

					if (!$errorMessage)	{
						$templateArea = $codeTemplateArray[$theCode];
						if (substr($theCode,0, 6) == 'SELECT') {
							$categoryClass = 'tx_ttproducts_selectcat_view';
						} else if (substr($theCode,0, 4) == 'LIST') {
							$categoryClass = 'tx_ttproducts_catlist_view';
						} else if (substr($theCode,0, 4) == 'MENU') {
							$categoryClass = 'tx_ttproducts_menucat_view';
						}

							// category view
						$categoryView = GeneralUtility::makeInstance($categoryClass);
						$categoryView->init($pibaseClass, $this->config['pid_list'], $this->config['recursive'], $this->pid);
						$contentTmp = $categoryView->printView(
							$functablename,
							$templateCode,
							$theCode,
							$errorCode,
							$templateArea,
							$this->pageAsCategory,
							$this->config['templateSuffix']
						);
					}
				break;
				case 'SINGLE':
					$contentTmp = $this->products_display($templateCode, $theCode, $errorMessage, $errorCode);
				break;
				case 'BASKET':
				case 'FINALIZE':
				case 'INFO':
				case 'PAYMENT':
				case 'OVERVIEW':
					if (!$bRunAjax && $this->convertToUserInt()) {
						return '';
					}
					$contentTmp = $contentBasket;
					$contentBasket = '';
						// nothing here any more. This work is done in the control processing before
						// This line is necessary because some activities might have overriden these CODEs
				break;
				case 'BILL':
				case 'DELIVERY':
					if (!$bRunAjax && $this->convertToUserInt()) {
						return '';
					}
					$contentTmp = $this->products_tracking($errorCode, $templateCode, $theCode, $conf);
				break;
				case 'TRACKING':
					if (!$bRunAjax && $this->convertToUserInt()) {
						return '';
					}
					$contentTmp = $this->products_tracking($errorCode, $templateCode, $theCode, $conf);
				break;
				case 'MEMO':
					if (!$bRunAjax && $this->convertToUserInt()) {
						return '';
					}
					// memo view: has to be called always because it reads parameters from the list
					$this->memoView = GeneralUtility::makeInstance('tx_ttproducts_memo_view');
					$this->memoView->init(
						$pibaseClass,
						$theCode,
						$config['pid_list'],
						$conf,
						$conf['useArticles']
					);
					$contentTmp = $this->memoView->printView($theCode, $templateCode, $this->pid, $errorCode);
				break;
				case 'CURRENCY':
					if (!$bRunAjax && $this->convertToUserInt()) {
						return '';
					}
						// currency view
					$currencyView = GeneralUtility::makeInstance('tx_ttproducts_currency_view');
					$currencyView->init($pibaseObj);
					$contentTmp = $currencyView->printView();
				break;
				case 'ORDERS':
					if (!$bRunAjax && $this->convertToUserInt()) {
						return '';
					}

						// order view
					$orderView = $tablesObj->get('sys_products_orders', true);
					$contentTmp = $orderView->printView($templateCode, $errorCode);
				break;
				case 'SINGLECAT':
				case 'SINGLEAD':
					$catView = GeneralUtility::makeInstance('tx_ttproducts_cat_view');
					$catView->init(
						$pibaseObj,
						$this->pid,
						$this->config['pid_list'],
						$this->config['recursive']
					);
					$tableInfoArray = array('SINGLECAT' => 'tt_products_cat', 'SINGLEAD' => 'address');
					$functablename = $tableInfoArray[$theCode];
					$uid = $pibaseObj->piVars[tx_ttproducts_model_control::getPivar($functablename)];

					if ($uid)	{
						$contentTmp = $catView->printView(
							$templateCode,
							$functablename,
							$uid,
							$theCode,
							$errorCode,
							$this->config['templateSuffix']
						);
					}
				break;
				case 'SCRIPT':
					$contentTmp = '';
				break;
				case 'USER1':
				case 'USER2':
				case 'USER3':
					if (!$bRunAjax && $this->convertToUserInt()) {
						return '';
					}
					$viewObj = GeneralUtility::makeInstance('tx_ttproducts_user_view');
					$contentTmp = $viewObj->printView($this->pibaseClass, $templateCode, $theCode);
				break;
				default:	// 'HELP'
					if (!$bRunAjax && $this->convertToUserInt()) {
						return '';
					}
					$contentTmp = 'error';
			}
		}

        if ($errorCode[0]) {
            $messageArr = array();
            $i = 0;
            $errorConf = array();
            if (isset($this->conf['error.'])) {
                $errorConf = $this->conf['error.'];
                $urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
            }

            foreach ($errorCode as $key => $indice) {
                if (
                    isset($errorConf[$indice . '.']) &&
                    isset($errorConf[$indice . '.']['redirect.']) &&
                    isset($errorConf[$indice . '.']['redirect.']['pid'])
                ) {
                    $pid = $errorConf[$indice . '.']['redirect.']['pid'];
                    $url = tx_div2007_alpha5::getTypoLink_URL_fh003(
                        $this->cObj,
                        $pid,
                        $urlObj->getLinkParams(
                            'product,article',
                            '',
                            true,
                            false
                        ),
                        '',
                        array()
                    );

                    if ($url != '') {
                        \TYPO3\CMS\Core\Utility\HttpUtility::redirect($url);
                    }
                }

                if ($key == 0) {
                    $messageArr = explode('|', $message = $languageObj->getLabel($indice));
                    $contentTmp .= '<b>' . $languageObj->getLabel('tt_products') . ': ' . $messageArr[0] . '</b>';
                } else {
                    $contentTmp .= '<b>' . $indice . $messageArr[$i] . '</b>';
                }
                $i++;
            }
            $errorCode = array();
        }

        if ($contentTmp == 'error') {
            $fileName = 'EXT:' . TT_PRODUCTS_EXT . '/template/products_help.tmpl';
            $helpTemplate =  \JambageCom\Div2007\Utility\FrontendUtility::fileResource($fileName);
            $content .=
                \JambageCom\Div2007\Utility\ViewUtility::displayHelpPage(
                    $languageObj,
                    $this->cObj,
                    $helpTemplate,
                    TT_PRODUCTS_EXT,
                    $errorMessage,
                    $theCode
                );
            $bErrorFound = true;
            unset($errorMessage);
        }

        if (intval($this->conf['wrapInCode'])) {
            $content .=
                tx_div2007_alpha5::wrapContentCode_fh004(
                    $contentTmp,
                    $theCode,
                    $pibaseObj->prefixId,
                    $this->cObj->data['uid']
                );
        } else if (!$bErrorFound) {
            $content .= $contentTmp;
        }

        if ($errorMessage) {
            $content = '<p><b>' . $errorMessage . '</b></p>';
        }

		if ($bRunAjax || !intval($this->conf['wrapInBaseClass']))	{
			$rc = $content;
		} else {
			$content = tx_div2007_alpha5::wrapInBaseClass_fh002($content, $pibaseObj->prefixId, $pibaseObj->extKey);
			$cssObj = GeneralUtility::makeInstance('tx_ttproducts_css');

			if ($cssObj->isCSSStyled() && !$cssObj->getIncluded())	{
				$rc = '<style type="text/css">' .  \JambageCom\Div2007\Utility\FrontendUtility::fileResource($cssObj->conf['file']) . '</style>' . chr(13) . $content;
				$cssObj->setIncluded();
			} else {
				$rc = $content;
			}
		}

		if (!$this->conf['fe'])	{
			$rc = '';
		}

		$showConfigurationError = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['error.']['configuration']; 

		if ($showConfigurationError && !$this->conf['defaultSetup'])	{
			$rc .= '<h>Error: The default tt_products setup is missing.</h>';
		}

        if (
            $showConfigurationError &&
            (
                (
                    version_compare(TYPO3_version, '7.6.32', '>=') &&
                    version_compare(TYPO3_version, '8.7.0', '<')
                ) ||
                (
                    version_compare(TYPO3_version, '8.7.22', '>=') &&
                    version_compare(TYPO3_version, '9.5.0', '<')
                )
            ) &&
            !$GLOBALS['TYPO3_CONF_VARS']['FE']['enableRecordRegistration']
        ) {
            $rc .= '<h>Error: $GLOBALS[\'TYPO3_CONF_VARS\'][\'FE\'][\'enableRecordRegistration\'] must be set to true.</h>';
        }

		$this->destruct();
		return $rc;
	}


	/**
	 * Converts the plugin to USER_INT if it is not USER_INT already. After
	 * calling this function the plugin should return if the function returns
	 * true. The content will be ignored and the plugin will be called again
	 * later as USER_INT.
	 *
	 * @return boolean true if the plugin should return immediately
	 */
	protected function convertToUserInt() {
		$result = false;
		if (method_exists($this->cObj, 'getUserObjectType') && $this->cObj->getUserObjectType() == \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER) {
			$this->cObj->convertToUserIntObject();
			$this->cObj->data['pi_flexform'] = $this->cObj->data['_original_pi_flexform'];
			unset($this->cObj->data['_original_pi_flexform']);
			$result = true;
		}
		return $result;
	}


	public function set_no_cache () {
		// Should never be used!
	}


	/**
	 * Returns 1 if user is a shop admin
	 */
	public function shopAdmin (&$updateCode)	{
		$admin = 0;
		if ($GLOBALS['TSFE']->beUserLogin || $this->conf['shopAdmin'] != 'BE')	{
			$updateCode = GeneralUtility::_GP('update_code');

			if ($updateCode == $this->conf['update_code'])	{
				$admin = 1;	// Means that the administrator of the website is authenticated.
			}
		}

		return $admin;
	}


	/**
	 * Order tracking
	 *
	 *
	 * @param	integer		Code: TRACKING, BILL or DELIVERY
	 * @return	void
	 * @see enableFields()
	 */
	public function products_tracking (&$errorCode, &$templateCode, $theCode, $conf)	{ // GeneralUtility::_GP('tracking')
		$pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi1_base');
		$urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
		$updateCode = '';
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$trackingCode = GeneralUtility::_GP('tracking');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();
        $parser =  $this->cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		$trackingTemplateCode = &$templateCode;
		$admin = $this->shopAdmin($updateCode);
		$subpartMarker = '';
		if ($trackingCode || $admin)	{	// Tracking number must be set
			$orderObj = $tablesObj->get('sys_products_orders');
			$orderRow = $orderObj->getRecord('', $trackingCode);

			if (isset($orderRow) && is_array($orderRow) || $admin)	{	// If order is associated with tracking id.

				$type = strtolower($theCode);
				switch ($theCode) {
					case 'TRACKING':
				 		$tracking = GeneralUtility::makeInstance('tx_ttproducts_tracking');
				 		$tracking->init(
				 			$this->cObj
				 		);

				 		$orderRecord = GeneralUtility::_GP('orderRecord');
						if ($_REQUEST['userNotification'] != '' && isset($orderRecord) && is_array($orderRecord)) {
							$orderRecord['email_notify'] = intval($orderRecord['email_notify']);
						}

						$content = $tracking->getTrackingInformation($orderRow, $trackingTemplateCode, $trackingCode, $updateCode, $orderRecord, $admin);
						break;
					case 'BILL':
					case 'DELIVERY':
						$billdeliveryObj = GeneralUtility::makeInstance('tx_ttproducts_billdelivery');
						$content = $billdeliveryObj->getInformation($theCode, $orderRow, $trackingTemplateCode,$trackingCode,$type);
						$absFileName = $billdeliveryObj->getFileAbsFileName($type, $trackingCode, 'html');
						$billdeliveryObj->writeFile($absFileName, $content);
						$relfilename = $billdeliveryObj->getRelFilename($trackingCode, $type);
						$message = $languageObj->getLabel('open_' . $type);
						$content = '<a href="' . $relfilename . '">' .$message . '</a>';
						break;
					default:
						debug('error in '.TT_PRODUCTS_EXT.' calling function products_tracking with $theCode = "' . $theCode . '"'); // keep this
				}
			} else {	// ... else output error page
				$subpartMarker = '###TRACKING_WRONG_NUMBER###';
			}
		} else {	// No tracking number - show form with tracking number
			$subpartMarker = '###TRACKING_ENTER_NUMBER###';
		}

		if ($subpartMarker)	{
			$content = tx_div2007_core::getSubpart($trackingTemplateCode, $subpartmarkerObj->spMarker($subpartMarker));

			if ($content == '') {
				$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
				$errorCode[0] = 'no_subtemplate';
				$errorCode[1] = '###' . $subpartMarker . $templateObj->getTemplateSuffix() . '###';
				$errorCode[2] = $templateObj->getTemplateFile();
				return '';
			}

			if (!$GLOBALS['TSFE']->beUserLogin)	{
				$content = $parser->substituteSubpart($content,'###ADMIN_CONTROL###','');
			}
		}

		$markerArray = $globalMarkerArray;
		$markerArray['###FORM_URL###'] = $pibaseObj->pi_getPageLink($GLOBALS['TSFE']->id,'',$urlObj->getLinkParams('',array(),true)) ;
		$content = $parser->substituteMarkerArray($content, $markerArray);
		return $content;
	}  // products_tracking


	public function setSingleFromList ($bValue)	{
		$this->bSingleFromList = $bValue;
	}


	public function getSingleFromList ()	{
		return $this->bSingleFromList;
	}


	/**
	 * Displaying single products/ the products list / searching
	 */
	public function products_display ($templateCode, $theCode, &$errorMessage, &$errorCode)	{
		$pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi1_base');
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnf->getConf();
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');

		$bSingleFromList = false;

		if (
			(
				(
					($theCode=='SEARCH') && $this->conf['listViewOnSearch'] == '1' || (strpos($theCode,'LIST') !== false)
				) &&
				$theCode != 'LISTARTICLES' && count($this->tt_product_single) && !$this->conf['NoSingleViewOnList'] &&
			!$this->getSingleFromList()
			)
		) {
			$this->setSingleFromList(true);
			$bSingleFromList = true;
		}

		if (($theCode=='SINGLE') || $bSingleFromList) {

			$extVars = $this->piVars['variants'];
			$extVars = ($extVars ? $extVars : GeneralUtility::_GP('ttp_extvars'));
			$showAmount = $cnf->getBasketConf('view','showAmount');

			if (!count($this->tt_product_single))	{
				if ($conf['defaultProductID'])	{
					$this->tt_product_single['product'] = $conf['defaultProductID'];
				} else if ($this->conf['defaultArticleID'])	{
					$this->tt_product_single['article'] = $conf['defaultArticleID'];
				}
			}

			if ($showAmount == 'basket' && $this->tt_product_single['product'] && $basketObj->isInBasket($this->tt_product_single['product']) ||
				!$this->conf['NoSingleViewOnList'] && !$this->conf['PIDitemDisplay'] && !$this->conf['PIDitemDisplay.']
			) {
				if ($this->convertToUserInt()) {
					return '';
				}
			}

			if (!is_object($this->singleView)) {
				// List single product:
				$this->singleView = GeneralUtility::makeInstance('tx_ttproducts_single_view');
			}

			$this->singleView->init (
				$this->pibaseClass,
				$this->tt_product_single,
				$extVars,
				$this->pid,
				$conf['useArticles'],
				$this->config['pid_list'],
				$this->config['recursive']
			);
			$content = $this->singleView->printView(
				$templateCode,
				$errorCode,
				$this->pageAsCategory,
				$this->config['templateSuffix']
			);
			$ctrlContent = $this->cObj->cObjGetSingle($conf['SINGLECTRL'], $conf['SINGLECTRL.']);
			$content .= $ctrlContent;
		} else {
	// page where to go usually
			$pid = ($conf['PIDbasket'] && $conf['clickIntoBasket'] ? $conf['PIDbasket'] : $GLOBALS['TSFE']->id);

			// List all products:
			$listView = GeneralUtility::makeInstance('tx_ttproducts_list_view');
			$listView->init (
				$this->pibaseClass,
				$pid,
				$conf['useArticles'],
				$this->tt_product_single,
				$this->config['pid_list'],
				$this->config['recursive']
			);

			if ($theCode == 'LISTARTICLES' && $conf['useArticles'])	{
				$templateArea = 'ARTICLE_LIST_TEMPLATE';
			} else {
				$templateArea = 'ITEM_LIST_TEMPLATE';
			}

			if ($theCode == 'LISTARTICLES' && $conf['useArticles'])	{
				$functablename = 'tt_products_articles';
			} else {
				$functablename = 'tt_products';
			}

			$allowedItems = tx_div2007_ff::get($this->cObj->data['pi_flexform'], 'productSelection');

			$bAllPages = false;
			$templateArea = $templateArea . $this->config['templateSuffix'];
			$content = $listView->printView(
				$templateCode,
				$theCode,
				$functablename,
				$allowedItems,
				$bAllPages,
				$errorCode,
				$templateArea,
				$this->pageAsCategory,
				array()
			);
		}

		return $content;
	}	// products_display
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_main.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_main.php']);
}

