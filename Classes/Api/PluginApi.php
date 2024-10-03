<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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
 * functions for the view
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 */

use Psr\EventDispatcher\EventDispatcherInterface;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

use JambageCom\Div2007\Utility\ExtensionUtility;
use JambageCom\Div2007\Utility\FlexformUtility;
use JambageCom\Div2007\Utility\ErrorUtility;

use JambageCom\TtProducts\Api\CustomerApi;
use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\View\RelatedList;

abstract class RelatedProductsTypes
{
    public const SystemCategory = 1;
}

class PluginApi
{
    private static bool $bHasBeenInitialised = false;
    private static $flexformArray = [];

    public static function init($conf): void
    {
        $piVarsDefault = [];
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $prefixId = $parameterApi->getPrefixId();
        $defaults = $conf['_DEFAULT_PI_VARS.'] ?? '';

        if (
            isset($defaults) &&
            is_array($defaults)
        ) {
            if (isset($defaults[$prefixId . '.'])) {
                $piVarsDefault = $defaults[$prefixId . '.'];
            } else {
                $piVarsDefault = $defaults;
            }
            $parameterApi->setPiVarDefaults($piVarsDefault);
        }

        $piVars = $parameterApi->getParameterMerged($prefixId);

        if (!empty($piVarsDefault)) {
            $tmp = $piVarsDefault;
            if (is_array($piVars)) {
                ArrayUtility::mergeRecursiveWithOverrule(
                    $tmp,
                    $piVars
                );
            }
            $piVars = $tmp;
        }

        $parameterApi->setPiVars(
            $piVars
        );
    }

    public static function init2(
        &$conf,
        &$config,
        $cObj,
        &$errorCode,
        $bRunAjax = false
    ) {
        $errorCode = [];

        self::initUrl(
            $urlObj,
            $cObj,
            $conf
        );

        // initialise AJAX at the beginning because the AJAX functions can set piVars
        if (!$bRunAjax) {
            $result = self::initAjax(
                $ajaxObj,
                $urlObj,
                $cObj,
                $conf['ajaxDebug']
            );
            if (!$result) {
                return false;
            }
        }

        $result = self::initConfig(
            $config,
            $conf,
            $pid,
            $pageAsCategory,
            $errorCode,
            $piVars['backPID']
        );

        if (!$result) {
            return false;
        }

        // ### central initialization ###

        if (!$bRunAjax) {
            $db = GeneralUtility::makeInstance(\tx_ttproducts_db::class);
            $result =
                $db->init(
                    $conf,
                    $config,
                    $ajaxObj,
                    $pibaseObj,
                    $cObj,
                    $errorCode
                ); // this initializes tx_ttproducts_config inside of creator class tx_ttproducts_model_creator
        }

        if ($result) {
            self::$bHasBeenInitialised = true;
        }

        return $result;
    }

    public static function initFlexform(
        $cObj
    ): void {
        if (!empty($cObj->data['pi_flexform'])) {
            self::$flexformArray = GeneralUtility::xml2array($cObj->data['pi_flexform']);
        } else {
            self::$flexformArray = [];
        }
    }

    public static function getFlexform()
    {
        return self::$flexformArray;
    }

    public static function isRelatedCode($code)
    {
        $result = false;
        if (
            substr($code, 0, 11) == 'LISTRELATED'
        ) {
            $result = true;
        }

        return $result;
    }

    public static function initUrl(
        &$urlObj,
        $cObj,
        $conf
    ): void {
        if (!isset($urlObj)) {
            // FHO neu Hier Teile aus init von main hinein verschoben
            $urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
            $urlObj->init($conf);
        }
    }

    public static function initAjax(
        &$ajaxObj,
        $urlObj,
        $cObj,
        $debug
    ) {
        $result = true;
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);

        if (ExtensionManagementUtility::isLoaded('taxajax')) {
            $ajaxObj = GeneralUtility::makeInstance(\tx_ttproducts_ajax::class);
            $result = $ajaxObj->init();
            if (!$result) {
                return false;
            }

            $ajaxObj->main(
                $cObj,
                $urlObj,
                $debug,
                $parameterApi->getPivar('tt_products'),
                $parameterApi->getPivar('tt_products_cat')
            );
        }

        return $result;
    }

    public static function initConfig(
        &$config,
        &$conf,
        &$pid,
        &$pageAsCategory,
        &$errorCode,
        $backPID
        // 		$piVars
    ): bool {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $eInfo = ExtensionUtility::getExtensionInfo(TT_PRODUCTS_EXT);
        if (!is_array($eInfo)) {
            throw new \RuntimeException('Error in tt_products: Wrong file ext_emconf.php! ' . $eInfo);
        }
        $config['version'] = $eInfo['version'];
        $config['defaultCategoryID'] = FlexformUtility::get(self::getFlexform(), 'categorySelection');

        // get template suffix string

        $config['templateSuffix'] = strtoupper($conf['templateSuffix'] ?? '');

        $templateSuffix = FlexformUtility::get(self::getFlexform(), 'template_suffix');
        $templateSuffix = strtoupper($templateSuffix);
        $config['templateSuffix'] = ($templateSuffix ?: $config['templateSuffix']);
        $config['templateSuffix'] = ($config['templateSuffix'] ? '_' . $config['templateSuffix'] : '');

        $config['limit'] = $conf['limit'] ?: 50;
        $config['limitImage'] = MathUtility::forceIntegerInRange($conf['limitImage'], 0, 50, 1);
        $config['limitImage'] = $config['limitImage'] ?: 1;
        $config['limitImageSingle'] = MathUtility::forceIntegerInRange($conf['limitImageSingle'], 0, 50, 1);
        $config['limitImageSingle'] = $config['limitImageSingle'] ?: 1;

        if (!empty($conf['priceNoReseller'])) {
            $config['priceNoReseller'] = MathUtility::forceIntegerInRange($conf['priceNoReseller'], 2, 10);
        }

        // If the current record should be displayed.
        $config['displayCurrentRecord'] = $conf['displayCurrentRecord'] ?? '';

        if (
            !isset($conf['TAXmode']) ||
            $conf['TAXmode'] == '' ||
            $conf['TAXmode'] == '{$plugin.tt_products.TAXmode}'
        ) {
            $conf['TAXmode'] = 1;
        }

        if ($conf['TAXmode'] < 1 || $conf['TAXmode'] > 2) {
            $errorCode[0] = 'error_range';
            $errorCode[1] = 'TAXmode';
            $errorCode[2] = '1';
            $errorCode[3] = '2';
            $errorCode[4] = $conf['TAXmode'];

            return false;
        }

        $config['backPID'] = $backPID;

        // page where to go usually
        $pid =
            (
                $conf['PIDbasket'] && $conf['clickIntoBasket'] ?
                    $conf['PIDbasket'] :
                        (
                            $backPID ?: $GLOBALS['TSFE']->id
                        )
            );

        $pageAsCategory = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'];

        return true;
    }

    public function getRelatedProductsBySystemCategory($content, $pluginConf)
    {
        $result = '';
        $errorCode = [];

        $result =
            $this->getRelatedProducts(
                $errorCode,
                $content,
                $pluginConf,
                RelatedProductsTypes::SystemCategory
            );

        if ($errorCode[0]) {
            $languageObj = GeneralUtility::makeInstance(Localization::class);
            $result .= ErrorUtility::getMessage($languageObj, $errorCode);
        }

        return $result;
    }

    public function getRelatedProducts(
        &$errorCode,
        $content,
        $pluginConf,
        $type,
        $bRunAjax = false
    ) {
        $result = '';
        $pidListObj = GeneralUtility::makeInstance(\tx_ttproducts_pid_list::class);
        $templateObj = GeneralUtility::makeInstance(\tx_ttproducts_template::class);
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $feUserRecord = CustomerApi::getFeUserRecord();

        if (!self::$bHasBeenInitialised) {
            $typo3VersionArray =
                VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());
            $typo3VersionMain = $typo3VersionArray['version_main'];
            $conf = [];
            if ($typo3VersionMain < 12) {
                $conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][TT_PRODUCTS_EXT . '.'] ?? [];
            } else {
                $conf = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray()['plugin.'][TT_PRODUCTS_EXT . '.'] ?? [];
            }

            ArrayUtility::mergeRecursiveWithOverrule($conf, $pluginConf);
            $config = [];
            $cObj = ControlApi::getCObj();
            self::init2(
                $conf,
                $config,
                $cObj,
                $errorCode,
                $bRunAjax
            );
        }

        $cnfObj = GeneralUtility::makeInstance(\tx_ttproducts_config::class);
        $uid = intval($pluginConf['ref']);

        // 		template' => 'ITEM_LIST_RELATED_TEMPLATE',
        $subtype = '';
        switch ($type) {
            case RelatedProductsTypes::SystemCategory:
                $subtype = 'productsbysystemcategory';
                break;
            default:
                $errorCode[0] = 'wrong_type';
                $errorCode[1] = $type;

                return false;
                break;
        }

        $funcTablename = 'tt_products';

        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $relatedListView = GeneralUtility::makeInstance(RelatedList::class, $eventDispatcher);
        $relatedListView->init(
            $config['pid_list'],
            $config['recursive']
        );
        $tablesObj = GeneralUtility::makeInstance(\tx_ttproducts_tables::class);
        $itemViewObj = $tablesObj->get($funcTablename, true);
        $itemObj = $itemViewObj->getModelObj();

        $addListArray =
            $relatedListView->getAddListArray(
                $theCode,
                $funcTablename,
                // 				$itemViewObj->getMarker(),
                $uid,
                $conf['useArticles'] ?? 3
            );

        $funcArray = $addListArray[$subtype] ?? [];
        $pid = $GLOBALS['TSFE']->id;
        $paramUidArray['product'] = $uid;

        $relatedItemObj = $itemObj;
        $recordFuncTablename = '';
        if (
            !empty($funcArray) &&
            $funcTablename != $funcArray['funcTablename']
        ) {
            $relatedItemObj = $tablesObj->get($funcArray['funcTablename'], false);
            $recordFuncTablename = $funcArray['funcTablename'];
        }
        $recordParentFuncTablename = '';
        if (isset($funcArray['parentFunctablename'])) {
            $recordParentFuncTablename = $funcArray['parentFunctablename'];
        }
        $fieldName = '';
        if (isset($funcArray['fieldName'])) {
            $fieldName = $funcArray['fieldName'];
        }

        $tableWhere = '';
        if (isset($funcArray['where'])) {
            $tableWhere = $funcArray['where'];
        }

        $tableConf = $relatedItemObj->getTableConf($funcArray['code']);
        $orderBy = '';
        if (isset($tableConf['orderBy'])) {
            $orderBy = $tableConf['orderBy'];
        }
        $mergeRow = [];
        $rows = [];
        $relatedIds =
            DatabaseTableApi::getRelated(
                $rows,
                $cnfObj->getTableName($funcTablename),
                $cnfObj->getTableName($recordParentFuncTablename),
                $cnfObj->getTableName($recordFuncTablename),
                $fieldName,
                $tableWhere,
                $multiOrderArray = [],
                $uid,
                $subtype,
                $funcArray['mm'],
                $orderBy
            );

        if (!empty($relatedIds)) {
            $listView = GeneralUtility::makeInstance(\tx_ttproducts_list_view::class);
            $listView->init(
                $pid,
                $paramUidArray,
                $config['pid_list'],
                0
            );

            $listPids = $funcArray['additionalPages'];
            if ($listPids != '') {
                $pidListObj->applyRecursive($config['recursive'], $listPids);
            } else {
                $listPids = $pidListObj->getPidlist();
            }

            $templateCode =
                $templateObj->get(
                    $funcArray['code'],
                    $templateFile,
                    $errorCode
                );
            $notOverwritePriceIfSet = false;
            $callFunctableArray = '';

            $tmpContent = $listView->printView(
                $templateCode,
                $funcArray['code'],
                $funcArray['funcTablename'],
                implode(',', $relatedIds),
                $listPids,
                '',
                $errorCode,
                $pageAsCategory,
                $feUserRecord,
                $funcArray['template'] . $config['templateSuffix'],
                $basketApi->getBasketExtra(),
                $parameterApi->getRecs(),
                $mergeRow,
                1,
                $callFunctableArray,
                $parentDataArray,
                $parentProductRow,
                $parentFuncTablename,
                $rows,
                $notOverwritePriceIfSet,
                $multiOrderArray,
                $productRowArray,
                $bEditableVariants
            );
            $result = $tmpContent;
        }

        return $result;
    }
}
