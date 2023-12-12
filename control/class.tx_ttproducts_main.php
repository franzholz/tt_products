<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger (franz@ttproducts.de)
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
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\ConfigUtility;
use JambageCom\Div2007\Utility\ErrorUtility;
use JambageCom\Div2007\Utility\FlexformUtility;
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\ViewUtility;
use JambageCom\TtProducts\Api\ControlApi;
use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\PluginApi;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

class tx_ttproducts_main implements SingletonInterface
{
    // Internal
    public $uid_list = '';			// List of existing uid's from the basket, set by initBasket()
    public $orderRecord = [];		// Will hold the order record if fetched.

    // Internal: init():
    public $conf;
    public $tt_product_single = [];
    public $control;			// object for the control of the application
    public $singleView;			// single view object
    public $memoView;			// memo view and data object

    public $pid;				// the page to which the script shall go
    public $ajax;				// ajax object
    public $codeArray;			// Codes
    public $pageAsCategory;		// > 0 if pages are used as categories

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
    protected static $uncachedCodes = [
        'BASKET',
        'BILL',
        'DELIVERY',
        'FINALIZE',
        'HELP',
        'INFO',
        'MEMO',
        'MEMODAM',
        'MEMODAMOVERVIEW',
        'ORDERS',
        'OVERVIEW',
        'PAYMENT',
        'SEARCH',
        'TRACKING',
    ];

    public $cObj;

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    /**
     * does the initialization stuff.
     *
     * @param	string		content string
     * @param	string		configuration array
     * @param	string		modified configuration array
     *
     * @return	  bool		if true processing should be done
     */
    public function init(
        &$conf,
        &$config,
        &$cObj,
        $pibaseClass,
        &$errorCode,
        $bRunAjax = false
    ) {
        $result = true;
        $this->setSingleFromList(false);
        $this->tt_product_single = [];
        $piVars = tx_ttproducts_model_control::getPiVars();

        $pibaseObj = GeneralUtility::makeInstance('' . $pibaseClass);

        // Save the original flexform in case if we need it later as USER_INT
        $cObj->data['_original_pi_flexform'] = $cObj->data['pi_flexform'] ?? '';
        PluginApi::initFlexform($cObj);

        $flexformArray = PluginApi::getFlexform();
        $flexformTyposcript = FlexformUtility::get($flexformArray, 'myTS');

        if ($flexformTyposcript) {
            $tsparser = GeneralUtility::makeInstance(
                TypoScriptParser::class
            );
            // Copy conf into existing setup
            $tsparser->setup = $conf;
            // Parse the new Typoscript
            $tsparser->parse($flexformTyposcript);
            // Copy the resulting setup back into conf
            $conf = $tsparser->setup;
        }
        $this->pibaseClass = $pibaseClass;
        $conf['code'] ??= '';
        $conf['code.'] ??= [];

        $config['code'] =
            ConfigUtility::getSetupOrFFvalue(
                $cObj,
                $conf['code'],
                $conf['code.'],
                $conf['defaultCode'],
                $flexformArray,
                'display_mode',
                true
            );

        $this->codeArray = GeneralUtility::trimExplode(',', $config['code'], 1);

        $required_pivars =
            FlexformUtility::get(
                $flexformArray,
                'required_pivars'
            );

        $requiredArray = GeneralUtility::trimExplode(',', $required_pivars);

        $bDoProcessing = true;
        if (count($requiredArray)) {
            foreach ($requiredArray as $k => $pivar) {
                if ($pivar && $pivar != 'empty') {
                    $gpVar = GeneralUtility::_GP($pivar);
                    if (
                        !isset($piVars[$pivar]) &&
                        !isset($gpVar)
                    ) {
                        $bDoProcessing = false;
                        break;
                    }
                }
            }
        }

        if (
            $bDoProcessing &&
            (
                method_exists($cObj, 'getUserObjectType') &&
                $cObj->getUserObjectType() == ContentObjectRenderer::OBJECTTYPE_USER
            )
        ) {
            $intersection =
                array_intersect(
                    self::$uncachedCodes,
                    $this->codeArray
                );
            if (count($intersection)) {
                if ($this->convertToUserInt($cObj)) {
                    $bDoProcessing = false;
                }
            }
        }

        if (!$bDoProcessing) {
            return false;
        }

        PluginApi::initUrl(
            $urlObj,
            $cObj,
            $conf
        );

        // initialise AJAX at the beginning because the AJAX functions can set piVars
        if (!$bRunAjax) {
            $result = PluginApi::initAjax(
                $this->ajax,
                $urlObj,
                $cObj,
                $conf['ajaxDebug']
            );

            if (!$result) {
                return false;
            }
        }

        $result = PluginApi::initConfig(
            $config,
            $conf,
            $this->pid,
            $this->pageAsCategory,
            $errorCode,
            $piVars['backPID'] ?? ''
        );

        if (!$result) {
            return false;
        }

        // ### central initialization ###
        if (!$bRunAjax) {
            $db = GeneralUtility::makeInstance('tx_ttproducts_db');
            $result =
                $db->init(
                    $conf,
                    $config,
                    $this->ajax,
                    $pibaseObj,
                    $cObj,
                    $errorCode
                ); // this initializes tx_ttproducts_config inside of creator class tx_ttproducts_model_creator
        }

        if (!$result) {
            return false;
        }

        if (!$bRunAjax && ExtensionManagementUtility::isLoaded('taxajax')) {
            if (!empty($_POST['xajax'])) {
                global $trans;
                $trans = $this;
                $this->ajax->taxajax->processRequests();
                exit;
            }
        }

        // *************************************
        // *** getting configuration values:
        // *************************************

        if ($config['displayCurrentRecord']) {
            // $config['code']='SINGLE';
            $row = $cObj->data;
            $this->tt_product_single['product'] = $row['uid'];
        } else {
            $error_detail = '';
            $paramArray = ['product', 'article', 'dam', 'fal'];
            $paramVal = '';

            foreach ($paramArray as $param) {
                $paramVal = ($piVars[$param] ?? '');
                if ($paramVal) {
                    $bParamValIsInt = MathUtility::canBeInterpretedAsInteger($paramVal);

                    if ($bParamValIsInt) {
                        $this->tt_product_single[$param] = intval($paramVal);
                    } elseif (!is_array($paramVal)) {
                        $error_detail = $param;
                        break;
                    }
                }
            }

            if ($error_detail != '') {
                $errorCode[0] = 'wrong_' . $error_detail;
                $errorCode[1] = htmlspecialchars($paramVal);

                return false;
            }
        }

        // if t3jquery is loaded and the custom library had been created
        if (
            defined('T3JQUERY') &&
            T3JQUERY === true
        ) {
            tx_t3jquery::addJqJS();
        } elseif (!empty($conf['pathToJquery'])) {
            // if none of the previous is true, you need to include your own library
            $GLOBALS['TSFE']->additionalHeaderData[TT_PRODUCTS_EXT . '-jquery'] = '<script src="' . GeneralUtility::getFileAbsFileName($conf['pathToJquery']) . '" type="text/javascript" ></script>';
        }

        return $result;
    } // init

    public function destruct()
    {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $tablesObj->destruct();

        $db = GeneralUtility::makeInstance('tx_ttproducts_db');
        if (is_object($db)) {
            $db->destruct();
        }
    }

    public function run(&$cObj, $pibaseClass, &$errorCode, $content = '', $bRunAjax = false)
    {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $config = $cnf->getConfig();
        $piVars = tx_ttproducts_model_control::getPiVars();
        $urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        if (!empty($conf['no_cache']) && $this->convertToUserInt($cObj)) {
            // Compatibility with previous versions where users could set
            // 'no_cache' TS option. This option does not exist anymore and we
            // simply convert the plugin to USER_INT if that old option is set.
            return false;
        }

        $bStoreBasket = true;
        $errorMessage = '';
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $pibaseObj = GeneralUtility::makeInstance('' . $pibaseClass);
        $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
        $showAmount = $cnf->getBasketConf('view', 'showAmount');
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $javaScriptObj = GeneralUtility::makeInstance('tx_ttproducts_javascript');
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $globalMarkerArray = $markerObj->getGlobalMarkerArray();

        if (!count($this->codeArray) && !$bRunAjax) {
            $this->codeArray = ['HELP'];
        }

        if (ExtensionManagementUtility::isLoaded('taxajax')) {
            if ($bRunAjax) {
                // TODO: get AJAX configuration
            } else {
                $javaScriptObj->set($languageObj, 'xajax');
            }
        }
        $updateMode = 0;
        if (GeneralUtility::_GP('mode_update')) {
            $updateMode = 1;
        }

        if (
            (
                isset($conf['basket.']) &&
                $conf['basket.']['store'] == '0'
            ) ||
            (
                count($this->codeArray) == 1 &&
                $this->codeArray[0] == 'OVERVIEW' &&
                isset($conf['basket.']) &&
                isset($conf['basket.']['activity.']) &&
                isset($conf['basket.']['activity.']['overview.']) &&
                $conf['basket.']['activity.']['overview.']['store'] == '0'
            )
        ) {
            $bStoreBasket = false;
        }

        tx_ttproducts_control_basket::doProcessing();

        $basketObj->init(
            $pibaseClass,
            $updateMode,
            $bStoreBasket
        );

        // *************************************
        // *** Listing items:
        // *************************************
        $basketExt = tx_ttproducts_control_basket::getBasketExt();
        $basketExtra = tx_ttproducts_control_basket::getBasketExtra();
        $basketRecs = tx_ttproducts_control_basket::getRecs();
        $basketObj->create(
            'BASKET',
            $basketExt,
            $basketExtra,
            $basketRecs,
            $conf['useArticles'],
            $conf['priceTAXnotVarying'],
            tx_ttproducts_control_basket::getFuncTablename()
        );

        $itemArray = $basketObj->getItemArray();
        $basketObj->calculate($itemArray); // get the calculated arrays
        $basketObj->setItemArray($itemArray);

        $voucher = $tablesObj->get('voucher');
        if (is_object($voucher) && $voucher->isEnabled()) {
            $recs = tx_ttproducts_control_basket::getRecs();
            $voucher->doProcessing($recs);
        }

        $basketObj->calculateSums();
        $basketObj->addVoucherSums();
        $templateFile = '';
        $templateCode = '';

        if (!$errorMessage && !count($errorCode)) {
            $functablename = 'tt_products';
            tx_ttproducts_control_memo::process(
                $functablename,
                $piVars,
                $conf
            );

            $controlObj = GeneralUtility::makeInstance('tx_ttproducts_control');
            $controlObj->init(
                $pibaseClass,
                tx_ttproducts_control_basket::getFuncTablename(),
                $conf['useArticles'],
                $errorCode
            );

            if ($errorCode) {
                $errorText =
                    $languageObj->getLabel(
                        'no_template'
                    );
                $errorMessage = str_replace('|', 'plugin.tt_products.templateFile', $errorText);
            }

            $addressArray = [];
            $addressObj = $tablesObj->get('address', false);
            if (is_object($addressObj)) {
                $addressArray = $addressObj->fetchAddressArray($itemArray);
            }
            $content .= $controlObj->doProcessing(
                $this->codeArray,
                $basketObj->getCalculatedSums(),
                $basketExtra,
                $basketRecs,
                $basketExt,
                $addressArray,
                $errorCode,
                $errorMessage
            );
        }

        $bErrorFound = false;
        $contentBasket = $content;
        $content = '';
        $tableArray = array_keys($tablesObj->getTableClassArray());

        foreach ($this->codeArray as $theCode) {
            $theCode = (string)trim($theCode);
            $contentTmp = '';
            if ($conf['fe']) {
                $templateCode =
                    $templateObj->get(
                        $theCode,
                        $templateFile,
                        $errorCode
                    );
            }
            if ($errorMessage || !empty($errorCode)) {
                $bErrorFound = true;
            }

            $bHidePlugin = false;

            foreach ($tableArray as $functablename) {
                $tableConf = $cnf->getTableConf($functablename, $theCode);
                if (!empty($tableConf)) {
                    $hideIds = '';
                    $hideChildless = false;
                    $hideZero = false;

                    if (isset($tableConf['hideID'])) {
                        $hideIds = $tableConf['hideID'];
                    }

                    if ($functablename == 'tt_products_cat') {
                        if (isset($tableConf['hideChildless'])) {
                            $hideChildless = (bool)$tableConf['hideChildless'];
                        }
                        if (isset($tableConf['hideZero'])) {
                            $hideZero = (bool)$tableConf['hideZero'];
                        }
                    }

                    if (
                        $hideIds != '' ||
                        $hideChildless ||
                        $hideZero
                    ) {
                        $hideIdArray = GeneralUtility::trimExplode(',', $hideIds);
                        $piVar = tx_ttproducts_model_control::getPiVar($functablename);

                        if (isset($piVars[$piVar])) {
                            $currentArray = GeneralUtility::trimExplode(',', $piVars[$piVar]);
                        } else {
                            $currentArray = ['0'];
                        }

                        if ($hideIds != '') {
                            $hideCurrentArray = array_diff($currentArray, $hideIdArray);
                            if (count($hideCurrentArray) < count($currentArray)) {
                                $bHidePlugin = true;
                            }
                        }

                        if (!$bHidePlugin) {
                            if (isset($piVars[$piVar])) {
                                if ($hideChildless) {
                                    $categoryfunctablename = 'tt_products_cat';
                                    $categoryTable = $tablesObj->get($categoryfunctablename, false);
                                    foreach ($currentArray as $currentUid) {
                                        $childs =
                                            $categoryTable->getAllChildCats(
                                                $config['pid_list'],
                                                '',
                                                $currentUid
                                            );
                                        if ($childs == '') {
                                            $bHidePlugin = true;
                                            break;
                                        }
                                    }
                                }
                            } else {
                                if ($hideZero) {
                                    $bHidePlugin = true;
                                }
                            }
                        }
                    }
                }
            }

            if ($bHidePlugin) {
                continue;
            }

            switch ($theCode) {
                case 'CONTROL': // this will come with tt_products 3.1
                    continue 2;
                    break;
                case 'SEARCH':
                    if (!$bRunAjax && $this->convertToUserInt($cObj)) {
                        return '';
                    }
                    // no break!
                case 'LIST':
                case 'LISTAFFORDABLE':
                case 'LISTARTICLES':
                case 'LISTDAM':
                case 'LISTGIFTS':
                case 'LISTHIGHLIGHTS':
                case 'LISTNEWITEMS':
                case 'LISTOFFERS':
                case 'LISTORDERED':
                case 'LISTVIEWEDITEMS':
                case 'LISTVIEWEDMOST':
                case 'LISTVIEWEDMOSTOTHERS':
                    if (
                        count($this->tt_product_single) &&
                        !$conf['NoSingleViewOnList']
                    ) {
                        if (!$bRunAjax && $this->convertToUserInt($cObj)) {
                            return '';
                        }
                    }

                    $contentTmp =
                        $this->products_display(
                            $cObj,
                            $basketExtra,
                            $basketRecs,
                            $templateCode,
                            $theCode,
                            $errorMessage,
                            $errorCode
                        );
                    break;
                case 'LISTCAT':
                case 'LISTDAMCAT':
                case 'LISTAD':
                case 'MENUCAT':
                case 'MENUDAMCAT':
                case 'MENUAD':
                case 'SELECTCAT':
                case 'SELECTDAMCAT':
                case 'SELECTAD':
                    $codeTemplateArray = [
                        'SELECTCAT' => 'ITEM_CATEGORY_SELECT_TEMPLATE',
                        'SELECTDAMCAT' => 'ITEM_DAMCATSELECT_TEMPLATE',
                        'SELECTAD' => 'ITEM_ADDRESS_SELECT_TEMPLATE',
                        'LISTCAT' => 'ITEM_CATLIST_TEMPLATE',
                        'LISTDAMCAT' => 'ITEM_DAMCATLIST_TEMPLATE',
                        'LISTAD' => 'ITEM_ADLIST_TEMPLATE',
                        'MENUCAT' => 'ITEM_CATEGORY_MENU_TEMPLATE',
                        'MENUDAMCAT' => 'ITEM_DAMCATMENU_TEMPLATE',
                        'MENUAD' => 'ITEM_ADDRESS_MENU_TEMPLATE',
                    ];

                    if (substr($theCode, -2, 2) == 'AD') {
                        $tablename = '';
                        $functablename = 'address';
                        if (is_array($conf['table.'])) {
                            $tablename = $conf['table.'][$functablename];
                        }

                        if (!isset($GLOBALS['TCA'][$tablename]['columns'])) {
                            GeneralUtility::loadTCA($tablename);
                        }

                        $addressExtKeyTable = tx_ttproducts_control_address::getAddressExtKeyTable();

                        if (
                            isset($addressExtKeyTable[$tablename]) &&
                            !ExtensionManagementUtility::isLoaded($addressExtKeyTable[$tablename])
                        ) {
                            $languageObj->getLabel('extension_missing');
                            $messageArr = explode('|', $message);
                            $errorMessage = $messageArr[0] . $addressExtKeyTable[$tablename] . $messageArr[1];
                        } elseif (!$tablename) {
                            $message = $languageObj->getLabel('setup_missing');
                            $messageArr = explode('|', $message);
                            $errorMessage = $messageArr[0] . 'table.address' . $messageArr[1];
                        }
                    } elseif (substr($theCode, -6, 6) == 'DAMCAT') {
                        $functablename = 'tx_dam_cat';
                    } elseif (substr($theCode, -3, 3) == 'CAT') {
                        if ($this->pageAsCategory) {
                            $functablename = 'pages';
                        } else {
                            $functablename = 'tt_products_cat';
                        }
                    }

                    if (!$errorMessage) {
                        $templateArea = $codeTemplateArray[$theCode];
                        if (substr($theCode, 0, 6) == 'SELECT') {
                            $categoryClass = 'tx_ttproducts_selectcat_view';
                        } elseif (substr($theCode, 0, 4) == 'LIST') {
                            $categoryClass = 'tx_ttproducts_catlist_view';
                        } elseif (substr($theCode, 0, 4) == 'MENU') {
                            $categoryClass = 'tx_ttproducts_menucat_view';
                        }

                        // category view
                        $categoryView = GeneralUtility::makeInstance($categoryClass);
                        $categoryView->init(
                            $cObj,
                            $pibaseClass,
                            $config['pid_list'],
                            $config['recursive'],
                            $this->pid
                        );

                        $contentTmp = $categoryView->printView(
                            $functablename,
                            $templateCode,
                            $theCode,
                            $errorCode,
                            $templateArea,
                            $this->pageAsCategory,
                            $config['templateSuffix'],
                            $basketExtra,
                            $basketRecs
                        );
                    }
                    break;
                case 'SINGLE':
                    $contentTmp =
                        $this->products_display(
                            $cObj,
                            $basketExtra,
                            $basketRecs,
                            $templateCode,
                            $theCode,
                            $errorMessage,
                            $errorCode
                        );
                    break;
                case 'BASKET':
                case 'FINALIZE':
                case 'INFO':
                case 'PAYMENT':
                case 'OVERVIEW':
                    if (!$bRunAjax && $this->convertToUserInt($cObj)) {
                        return '';
                    }
                    $contentTmp = $contentBasket;
                    $contentBasket = '';
                    // nothing here any more. This work is done in the control processing before
                    // This line is necessary because some activities might have overriden these CODEs
                    break;
                case 'BILL':
                case 'DELIVERY':
                case 'TRACKING':
                    if (!$bRunAjax && $this->convertToUserInt($cObj)) {
                        return '';
                    }
                    $contentTmp =
                        $this->products_tracking(
                            $errorCode,
                            $templateCode,
                            $theCode,
                            $conf
                        );
                    break;
                case 'MEMO':
                case 'MEMODAM':
                case 'MEMODAMOVERVIEW':
                    if (!$bRunAjax && $this->convertToUserInt($cObj)) {
                        return '';
                    }
                    // memo view: has to be called always because it reads parameters from the list
                    $this->memoView = GeneralUtility::makeInstance('tx_ttproducts_memo_view');
                    $this->memoView->init(
                        $theCode,
                        $config['pid_list'],
                        $conf,
                        $conf['useArticles']
                    );
                    $contentTmp =
                        $this->memoView->printView(
                            $templateCode,
                            $theCode,
                            $conf,
                            $this->pid,
                            $errorCode
                        );
                    break;
                case 'DOWNLOAD':
                    tx_ttproducts_control_access::getVariables(
                        $conf,
                        $updateCode,
                        $bIsAllowed,
                        $bValidUpdateCode,
                        $trackingCode
                    );

                    tx_ttproducts_control_command::doProcessing(
                        $theCode,
                        $conf,
                        $bIsAllowed,
                        $bValidUpdateCode,
                        $trackingCode,
                        $config['pid_list'],
                        $config['recursive']
                    );
                    $onlyProductsWithFalOrders = $conf['downloadViewOnlyProductsFal'];

                    // order view
                    $orderView = $tablesObj->get('sys_products_orders', true);
                    $contentTmp = $orderView->printDownloadView(
                        $pibaseClass,
                        $templateCode,
                        $theCode,
                        $config['pid_list'],
                        $config['recursive'],
                        $this->pageAsCategory,
                        $updateCode,
                        $bIsAllowed,
                        $bValidUpdateCode,
                        $trackingCode,
                        $onlyProductsWithFalOrders,
                        $errorCode
                    );
                    break;
                case 'ORDERS':
                    if (!$bRunAjax && $this->convertToUserInt($cObj)) {
                        return '';
                    }
                    tx_ttproducts_control_access::getVariables(
                        $conf,
                        $updateCode,
                        $bIsAllowed,
                        $bValidUpdateCode,
                        $trackingCode
                    );

                    // order view
                    $orderView = $tablesObj->get('sys_products_orders', true);
                    $contentTmp = $orderView->printView(
                        $pibaseClass,
                        $templateCode,
                        $theCode,
                        $config['pid_list'],
                        $config['recursive'],
                        $this->pageAsCategory,
                        $updateCode,
                        $bIsAllowed,
                        $bValidUpdateCode,
                        $trackingCode,
                        $errorCode
                    );
                    break;
                case 'SINGLECAT':
                case 'SINGLEDAMCAT':
                case 'SINGLEAD':
                    $catView = GeneralUtility::makeInstance('tx_ttproducts_cat_view');
                    $catView->init(
                        $pibaseObj,
                        $this->pid,
                        $config['pid_list'],
                        $config['recursive']
                    );
                    $tableInfoArray = ['SINGLECAT' => 'tt_products_cat', 'SINGLEDAMCAT' => 'tx_dam_cat', 'SINGLEAD' => 'address'];
                    $functablename = $tableInfoArray[$theCode];
                    $uid = $piVars[tx_ttproducts_model_control::getPivar($functablename)];

                    if ($uid) {
                        $contentTmp = $catView->printView(
                            $templateCode,
                            $functablename,
                            $uid,
                            $theCode,
                            $errorCode,
                            $config['templateSuffix']
                        );
                    }
                    break;
                case 'SCRIPT':
                    $contentTmp = '';
                    break;
                case 'TEST':
                    $contentTmp = '';

                    $test = 0;
                    if (!$test) {
                        continue 2;
                    }

                    $scriptPath = '';
                    $defaultSettings = [];

                    $buildScriptOptions = $defaultSettings;
                    $paramString = '';
                    foreach ($buildScriptOptions as $param => $value) {
                        if (strlen($value) > 0) {
                            $value = '"' . $value . '"';
                        }
                        $paramsString .= ' ' . $param . ' ' . $value;
                    }

                    $outputPath = GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT');
                    $outputPath .= '/typo3temp/tt_products/';
                    $filename = $outputPath . 'testseite.pdf';

                    $urls = ['https://demosite.jambage.com/index.php?id=5'];
                    $scriptCall =
                        $scriptPath . 'wkhtmltopdf ' .
                            $paramsString . ' ' .
                            implode(' ', $urls) . ' ' .
                            $filename .
                            ' 2>&1';

                    $commandOut = exec($scriptCall, $output);
                    $contentTmp = '<br>TEST:' . $scriptCall . '</br><br/><br>' . $commandOut . '<br/>';

                    // Call all test hooks
                    if (
                        isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['test']) &&
                        is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['test'])
                    ) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['test'] as $classRef) {
                            if (count($errorCode)) {
                                break;
                            }
                            $hookObj = GeneralUtility::makeInstance($classRef);
                            if (method_exists($hookObj, 'test')) {
                                $contentTmp .= $hookObj->test(
                                    $this,
                                    $cObj,
                                    $pibaseClass,
                                    $basketObj,
                                    $controlObj,
                                    $errorCode,
                                    $errorMessage,
                                    $content,
                                    $contentTmp,
                                    $bRunAjax
                                );
                            }
                        }
                    }
                    break;
                case 'USER1':
                case 'USER2':
                case 'USER3':
                case 'USER4':
                case 'USER5':
                    if (!$bRunAjax && $this->convertToUserInt($cObj)) {
                        return '';
                    }
                    $viewObj = GeneralUtility::makeInstance('tx_ttproducts_user_view');
                    $contentTmp =
                        $viewObj->printView(
                            $this->pibaseClass,
                            $templateCode,
                            $theCode
                        );
                    break;
                default:	// 'HELP'
                    if (!$bRunAjax && $this->convertToUserInt($cObj)) {
                        return '';
                    }
                    $contentTmp = 'error';
            } // switch

            if ($contentTmp != '') {
                $contentTmp =
                    $templateService->substituteMarkerArray(
                        $contentTmp,
                        $globalMarkerArray
                    );
            }

            if (!empty($errorCode[0])) {
                $errorConf = [];
                if (isset($this->conf['error.'])) {
                    $errorConf = $this->conf['error.'];
                }

                foreach ($errorCode as $key => $indice) {
                    if (
                        isset($errorConf[$indice . '.']) &&
                        isset($errorConf[$indice . '.']['redirect.']) &&
                        isset($errorConf[$indice . '.']['redirect.']['pid'])
                    ) {
                        $pid = $errorConf[$indice . '.']['redirect.']['pid'];
                        $url = FrontendUtility::getTypoLink_URL(
                            $cObj,
                            $pid,
                            $urlObj->getLinkParams(
                                'product,article,dam',
                                '',
                                true,
                                false
                            ),
                            '',
                            []
                        );

                        if ($url != '') {
                            HttpUtility::redirect($url);
                        }
                    }
                }

                $contentTmp .= ErrorUtility::getMessage($languageObj, $errorCode);
                $errorCode = [];
            }

            if ($contentTmp == 'error') {
                $fileName = 'EXT:' . TT_PRODUCTS_EXT . '/Resources/Public/Templates/products_help.tmpl';
                $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
                $fileName = $sanitizer->sanitize($fileName);

                $helpTemplate = file_get_contents($fileName);
                $content .=
                    ViewUtility::displayHelpPage(
                        $languageObj,
                        $cObj,
                        $helpTemplate,
                        TT_PRODUCTS_EXT,
                        $errorMessage,
                        $theCode
                    );

                $bErrorFound = true;
                unset($errorMessage);
            }

            if (!$bRunAjax && intval($conf['wrapInCode'])) {
                $content .=
                    FrontendUtility::wrapContentCode(
                        $contentTmp,
                        $theCode,
                        $pibaseObj->prefixId,
                        $cObj->data['uid'] ?? ''
                    );
            } elseif (!$bErrorFound) {
                $content .= $contentTmp;
            }

            $javaScriptConf = $cnf->getJsConf($theCode);
            if (!empty($javaScriptConf['file'])) {
                $fileName = $javaScriptConf['file'];
                $incFile = '';

                $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
                $incFile = $sanitizer->sanitize($fileName);

                if ($incFile != '' && !$javaScriptObj->getIncluded($incFile)) {
                    $text = '<script type="text/javascript" src="' . $incFile . '" ></script>';
                    $GLOBALS['TSFE']->additionalHeaderData[$pibaseObj->prefixId] = $text;
                    $javaScriptObj->setIncluded($incFile);
                }
            }
        } // foreach

        if ($errorMessage) {
            $content = '<p><b>' . $errorMessage . '</b></p>';
        }

        if ($bRunAjax || !intval($conf['wrapInBaseClass'])) {
            $result = $content;
        } else {
            $content = FrontendUtility::wrapInBaseClass($content, $pibaseObj->prefixId, $pibaseObj->extKey);
            $cssObj = GeneralUtility::makeInstance('tx_ttproducts_css');
            $result = '';

            if ($cssObj->isCSSStyled() && !$cssObj->getIncluded() && !empty($cssObj->conf['file'])) {
                $fileName = $cssObj->conf['file'];
                $incFile = '';

                $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
                $incFile = $sanitizer->sanitize($fileName);

                if ($incFile != '') {
                    $result = '<style type="text/css">@import "' . $incFile . '"</style>' . chr(13) . $content;
                    $cssObj->setIncluded();
                }
            }

            if ($result == '') {
                $result = $content;
            }
        }

        if (!$conf['fe']) {
            $result = '';
        }

        $showConfigurationError =
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['error']['configuration'];

        if ($showConfigurationError && !$conf['defaultSetup']) {
            $result .= '<h1>Error: The default tt_products setup is missing.</h1>';
        }

        $control = tx_ttproducts_control_basket::readControl();
        if ($control) {
            $control['pid'] = $GLOBALS['TSFE']->id;
            tx_ttproducts_control_basket::writeControl($control);
        }

        $this->destruct();
        return $result;
    }

    /**
     * Converts the plugin to USER_INT if it is not USER_INT already. After
     * calling this function the plugin should return if the function returns
     * true. The content will be ignored and the plugin will be called again
     * later as USER_INT.
     *
     * @return bool true if the plugin should return immediately
     */
    protected function convertToUserInt(&$cObj)
    {
        $result = false;
        if (
            method_exists($cObj, 'getUserObjectType') &&
            $cObj->getUserObjectType() == ContentObjectRenderer::OBJECTTYPE_USER
        ) {
            $cObj->convertToUserIntObject();
            $cObj->data['pi_flexform'] = $cObj->data['_original_pi_flexform'];
            unset($cObj->data['_original_pi_flexform']);
            $result = true;
        }

        return $result;
    }

    public function set_no_cache()
    {
        // Should never be used!
    }

    /**
     * Order tracking.
     *
     * @param	int		Code: TRACKING, BILL or DELIVERY
     */
    public function products_tracking(
        &$errorCode,
        $templateCode,
        $theCode,
        $conf
    ) { // GeneralUtility::_GP('tracking')
        $pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi1_base');
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
        $updateCode = '';
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');

        $cObj = ControlApi::getCObj();
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $globalMarkerArray = $markerObj->getGlobalMarkerArray();

        $trackingTemplateCode = $templateCode;

        tx_ttproducts_control_access::getVariables(
            $conf,
            $updateCode,
            $bIsAllowed,
            $bValidUpdateCode,
            $trackingCode
        );

        $subpartMarker = '';

        if ($trackingCode || $bValidUpdateCode) {	// Tracking number must be set
            $orderObj = $tablesObj->get('sys_products_orders');
            $orderRow = $orderObj->getRecord('', $trackingCode);

            if (
                isset($orderRow) &&
                is_array($orderRow) ||
                $bValidUpdateCode
            ) {	// If order is associated with tracking id.
                $type = strtolower($theCode);
                switch ($theCode) {
                    case 'TRACKING':
                        $tracking = GeneralUtility::makeInstance('tx_ttproducts_tracking');
                        $tracking->init(
                            $cObj
                        );
                        $orderRecord = GeneralUtility::_GP('orderRecord');
                        if (
                            !empty($_REQUEST['userNotification']) &&
                            isset($orderRecord) &&
                            is_array($orderRecord)
                        ) {
                            $orderRecord['email_notify'] = intval($orderRecord['email_notify']);
                        }
                        $content =
                            $tracking->getTrackingInformation(
                                $orderRow,
                                $trackingTemplateCode,
                                $trackingCode,
                                $updateCode,
                                $orderRecord,
                                $bValidUpdateCode
                            );
                        break;
                    case 'BILL':
                    case 'DELIVERY':
                        $billdeliveryObj = GeneralUtility::makeInstance('tx_ttproducts_billdelivery');
                        $content =
                            $billdeliveryObj->getInformation(
                                $theCode,
                                $orderRow,
                                $trackingTemplateCode,
                                $trackingCode,
                                $type
                            );
                        $absFileName =
                            $billdeliveryObj->getFileAbsFileName(
                                $type,
                                $trackingCode,
                                'html'
                            );
                        $billdeliveryObj->writeFile(
                            $absFileName,
                            $content
                        );
                        $relfilename =
                            $billdeliveryObj->getRelFilename(
                                $trackingCode,
                                $type
                            );

                        $message = $languageObj->getLabel('open_' . $type);
                        $content = '<a href="' . $relfilename . '" >' . $message . '</a>';
                        break;
                    default:
                        debug('error in ' . TT_PRODUCTS_EXT . ' calling function products_tracking with $theCode = "' . $theCode . '"'); // keep this
                }
            } else {	// ... else output error page
                $subpartMarker = '###TRACKING_WRONG_NUMBER###';
            }
        } else {	// No tracking number - show form with tracking number
            $subpartMarker = '###TRACKING_ENTER_NUMBER###';
        }

        if ($subpartMarker) {
            $content =
                $templateService->getSubpart(
                    $trackingTemplateCode,
                    $subpartmarkerObj->spMarker($subpartMarker)
                );
            if ($content == '') {
                $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
                $errorCode[0] = 'no_subtemplate';
                $errorCode[1] = '###' . $subpartMarker . $templateObj->getTemplateSuffix() . '###';
                $errorCode[2] = $templateObj->getTemplateFile();

                return '';
            }

            if (!$bIsAllowed) {
                $content = $templateService->substituteSubpart($content, '###ADMIN_CONTROL###', '');
            }
        }

        $markerArray = $globalMarkerArray;
        $markerArray['###FORM_URL###'] =
            FrontendUtility::getTypoLink_URL(
                $cObj,
                $GLOBALS['TSFE']->id,
                $urlObj->getLinkParams('', [], true)
            );

        $content = $templateService->substituteMarkerArray($content, $markerArray);

        return $content;
    }  // products_tracking

    public function setSingleFromList($bValue)
    {
        $this->bSingleFromList = $bValue;
    }

    public function getSingleFromList()
    {
        return $this->bSingleFromList;
    }

    /**
     * Displaying single products/ the products list / searching.
     */
    public function products_display(
        $cObj,
        $basketExtra,
        $basketRecs,
        $templateCode,
        $theCode,
        &$errorMessage,
        &$errorCode
    ) {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $config = $cnf->getConfig();
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $bSingleFromList = false;
        $piVars = tx_ttproducts_model_control::getPiVars();

        if (
            (
                ($theCode == 'SEARCH') && $conf['listViewOnSearch'] == '1' ||
                (strpos($theCode, 'LIST') !== false)
            ) &&
            $theCode != 'LISTARTICLES' &&
            count($this->tt_product_single) &&
            !$conf['NoSingleViewOnList'] &&
            !$this->getSingleFromList()
        ) {
            $this->setSingleFromList(true);
            $bSingleFromList = true;
        }

        if (
            ($theCode == 'SINGLE') ||
            $bSingleFromList
        ) {
            $extVars = $piVars['variants'] ?? '';
            $extVars = ($extVars ? $extVars : GeneralUtility::_GP('ttp_extvars'));
            $showAmount = $cnf->getBasketConf('view', 'showAmount');

            if (!count($this->tt_product_single)) {
                if ($conf['defaultProductID']) {
                    $this->tt_product_single['product'] = $conf['defaultProductID'];
                } elseif ($conf['defaultArticleID']) {
                    $this->tt_product_single['article'] = $conf['defaultArticleID'];
                }
            }

            if (
                empty($conf['NoSingleViewOnList']) &&
                empty($conf['PIDitemDisplay']) &&
                empty($conf['PIDitemDisplay.'])
            ) {
                if ($this->convertToUserInt($cObj)) {
                    return '';
                }
            }

            if (!is_object($this->singleView)) {
                // List single product:
                $this->singleView = GeneralUtility::makeInstance('tx_ttproducts_single_view');
            }

            $this->singleView->init(
                $this->tt_product_single,
                $extVars,
                $this->pid,
                $conf['useArticles'],
                $config['pid_list'],
                $config['recursive']
            );

            $content = $this->singleView->printView(
                $templateCode,
                $errorCode,
                $this->pageAsCategory,
                $config['templateSuffix']
            );

            $ctrlContent = $cObj->cObjGetSingle($conf['SINGLECTRL'], $conf['SINGLECTRL.']);
            $content .= $ctrlContent;
        } else {
            // page where to usually go
            $pid = ($conf['PIDbasket'] && $conf['clickIntoBasket'] ? $conf['PIDbasket'] : $GLOBALS['TSFE']->id);

            // List all products:
            $listView = GeneralUtility::makeInstance('tx_ttproducts_list_view');
            $listView->init(
                $pid,
                $this->tt_product_single,
                $config['pid_list'],
                $config['recursive']
            );

            if ($theCode == 'LISTARTICLES' && $conf['useArticles']) {
                $templateArea = 'ARTICLE_LIST_TEMPLATE';
            } else {
                $templateArea = 'ITEM_LIST_TEMPLATE';
            }

            if ($theCode == 'LISTARTICLES' && $conf['useArticles']) {
                $functablename = 'tt_products_articles';
            } elseif ($theCode == 'LISTDAM') {
                $functablename = 'tx_dam';
            } else {
                $functablename = 'tt_products';
            }
            $allowedItems = FlexformUtility::get(PluginApi::getFlexform(), 'productSelection');

            $bAllPages = false;
            $templateArea = $templateArea . $config['templateSuffix'];
            $content = $listView->printView(
                $templateCode,
                $theCode,
                $functablename,
                $allowedItems,
                $bAllPages,
                '',
                $errorCode,
                $templateArea,
                $this->pageAsCategory,
                $basketExtra,
                $basketRecs,
                []
            );
        }

        return $content;
    }	// products_display
}
