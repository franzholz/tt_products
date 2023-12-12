<?php

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
 * payment, shipping, handling and basket extra functions
 *
 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author  René Fritz <r.fritz@colorcube.de>
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @author  Klaus Zierer <zierer@pz-systeme.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */

use JambageCom\Div2007\Utility\ExtensionUtility;
use JambageCom\Div2007\Utility\FrontendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class PaymentShippingHandling
{
    public static $priceObj;	// price functions
    protected static $typeArray = ['handling', 'shipping', 'payment'];
    protected static $voucher;

    public static function init($priceObj, $voucher)
    {
        self::setPriceObj($priceObj);	// new independant price object
        self::setVoucher($voucher);
    }

    public static function getTypeArray()
    {
        return self::$typeArray;
    }

    public static function setVoucher($voucher)
    {
        self::$voucher = $voucher;
    }

    public static function getVoucher()
    {
        self::$voucher;
    }

    public static function setPriceObj($value)
    {
        self::$priceObj = $value;
    }

    public static function getPriceObj()
    {
        return self::$priceObj;
    }

    public static function getScriptPrices(
        &$calculatedArray,
        &$itemArray,
        $basketExtra,
        $pskey = 'shipping'
    ) {
        $hookVar = 'scriptPrices';
        if (
            $hookVar &&
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar]) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar]) &&
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar][$pskey]) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar][$pskey])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar][$pskey] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'init')) {
                    $hookObj->init();
                }
                if (method_exists($hookObj, 'getScriptPrices')) {
                    $tmpArray =
                        $hookObj->getScriptPrices(
                            $calculatedArray,
                            $itemArray,
                            $basketExtra,
                            $pskey
                        );
                }
            }
        }
    }

    protected static function helperSubpartArray(
        $markerPrefix,
        $bActive,
        $keyMarker,
        $confRow,
        $framework,
        $markerArray,
        &$subpartArray,
        &$wrappedSubpartArray
    ) {
        $theMarker = '###' . $markerPrefix . '_' . $keyMarker . '###';

        if ($bActive) {
            $wrappedSubpartArray[$theMarker] = '';
        } else {
            $subpartArray[$theMarker] = '';
        }
    }

    /**
     * Template marker substitution
     * Fills in the subpartArray with data depending on payment and shipping.
     *
     * @param	array		reference to an item array with all the data of the item
     *
     * @access private
     */
    public static function getSubpartArrays(
        $basketExtra,
        $markerArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        $framework
    ) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
        $cObj = FrontendUtility::getContentObjectRenderer();
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();

        $typeArray = self::getTypeArray();
        $psArray = ['payment', 'shipping'];
        $psMessageArray = [];
        $tmpSubpartArray = [];

        $handleLib = $basketExtra['payment.']['handleLib'] ?? '';

        if (
            strpos($handleLib, 'transactor') !== false &&
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($handleLib)
        ) {
            $languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
            // Payment Transactor
            \tx_transactor_api::init($languageObj, '', $conf);

            \tx_transactor_api::getItemMarkerSubpartArrays(
                $basketExtra['payment.']['handleLib.'] ?? '',
                $subpartArray,
                $wrappedSubpartArray
            );
        } else {	// markers for the missing payment transactor extension
            $wrappedSubpartArray['###MESSAGE_PAYMENT_TRANSACTOR_NO###'] = '';
            $subpartArray['###MESSAGE_PAYMENT_TRANSACTOR_YES###'] = '';
        }

        foreach ($typeArray as $k => $pskey) {
            if (in_array($pskey, $psArray)) {
                $marker = strtoupper($pskey);
                $markerPrefix = 'MESSAGE_' . $marker;
                $keyArray = $basketExtra[$pskey] ?? [];
                if (!is_array($keyArray)) {
                    $keyArray = [$keyArray];
                }
                $psKey = '';
                $psMessageArray[$pskey] = '';

                foreach ($keyArray as $k => $value) {
                    if ($psKey) {
                        $psKey .= '_';
                    }
                    $psKey .= $value;
                    $subFrameWork = $templateService->getSubpart($framework, '###' . $markerPrefix . '###');

                    if ($subFrameWork != '') {
                        $tmpSubpartArray[$pskey] = $templateService->getSubpart($subFrameWork, '###MESSAGE_' . $marker . '_' . $psKey . '###');
                        $psMessageArray[$pskey] .= $templateService->substituteMarkerArray($tmpSubpartArray[$pskey], $markerArray);
                    }
                    $subpartArray['###MESSAGE_' . $marker . '_NE_' . $psKey . '###'] = '';
                }
            }
        }
        $tagArray = $markerObj->getAllMarkers($framework);

        foreach ($typeArray as $k => $pskey) {
            $marker = strtoupper($pskey);
            $markerPrefix = 'MESSAGE_' . $marker;

            if (isset($conf[$pskey . '.']) && is_array($conf[$pskey . '.'])) {
                foreach ($conf[$pskey . '.'] as $k2 => $v2) {
                    $k2int = substr($k2, 0, -1);

                    if (
                        !MathUtility::canBeInterpretedAsInteger($k2int)
                    ) {
                        continue;
                    }

                    if ($pskey == 'handling') {
                        if (is_array($v2)) {
                            foreach ($v2 as $k3 => $v3) {
                                $k3int = substr($k3, 0, -1);
                                if (
                                    !MathUtility::canBeInterpretedAsInteger($k3int)
                                ) {
                                    continue;
                                }
                                $bActive = isset($basketExtra[$pskey . '.'][$k3int]['0']) && ($k3int == $basketExtra[$pskey . '.'][$k3int]['0']);
                                self::helperSubpartArray(
                                    $markerPrefix . '_' . $k2int,
                                    $bActive,
                                    $k3int,
                                    $v3,
                                    $framework,
                                    $markerArray,
                                    $subpartArray,
                                    $wrappedSubpartArray
                                );
                            }
                        }
                    } else {
                        $bActive = isset($basketExtra[$pskey][0]) && ($k2int == $basketExtra[$pskey][0]);
                        self::helperSubpartArray(
                            $markerPrefix,
                            $bActive,
                            $k2int,
                            $v2,
                            $framework,
                            $markerArray,
                            $subpartArray,
                            $wrappedSubpartArray
                        );
                    }
                }
            }
            $bCheckNE = in_array($pskey, $psArray);

            foreach ($tagArray as $k3 => $v3) {
                if (strpos($k3, $markerPrefix) === 0 && !isset($subpartArray['###' . $k3 . '###'])) {
                    if ($bCheckNE && strpos($k3, '_NE_') !== false) {
                        $wrappedSubpartArray['###' . $k3 . '###'] = '';
                        $tmpSubpartArray[$pskey] = $templateService->getSubpart($framework, '###' . $k3 . '###');
                        $psMessageArray[$pskey] .=
                            $templateService->substituteMarkerArrayCached(
                                $tmpSubpartArray[$pskey],
                                $markerArray
                            );
                    } elseif (!isset($wrappedSubpartArray['###' . $k3 . '###'])) {
                        $subpartArray['###' . $k3 . '###'] = '';
                    }
                }
            }

            $subpartArray['###' . $markerPrefix . '###'] = $psMessageArray[$pskey] ?? [];
        }
    }

    protected static function getTypeMarkerArray(
        $theCode,
        &$markerArray,
        $pskey,
        $subkey,
        $pid,
        $useBackPid,
        $calculatedArray,
        $basketExtra
    ) {
        $cObj = FrontendUtility::getContentObjectRenderer();
        $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');

        if ($subkey != '') {
            $theCalculateArray = $calculatedArray[$pskey][$subkey];
        } else {
            $theCalculateArray = $calculatedArray[$pskey];
        }
        if (!is_array($theCalculateArray)) {
            $theCalculateArray = [];
        }

        $markerkey = strtoupper($pskey) . ($subkey != '' ? '_' . $subkey : '');
        $markerArray['###PRICE_' . $markerkey . '_TAX###'] = $priceViewObj->priceFormat($theCalculateArray['priceTax']);
        $markerArray['###PRICE_' . $markerkey . '_NO_TAX###'] = $priceViewObj->priceFormat($theCalculateArray['priceNoTax']);
        $markerArray['###PRICE_' . $markerkey . '_ONLY_TAX###'] = $priceViewObj->priceFormat($theCalculateArray['priceTax'] - $theCalculateArray['priceNoTax']);
        $markerArray['###' . $markerkey . '_SELECTOR###'] =
            self::generateRadioSelect(
                $theCode,
                $pskey,
                $subkey,
                $calculatedArray,
                $pid,
                $useBackPid,
                $basketExtra
            );

        $imageCode = '';
        $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');
        if ($subkey != '') {
            $imageCode =
                $imageObj->getImageCode(
                    $basketExtra[$pskey . '.'][$subkey . '.']['image.'] ?? [],
                    $theCode
                );
            $markerArray['###' . $markerkey . '_TITLE###'] = $basketExtra[$pskey . '.'][$subkey . '.']['title'];
        } else {
            $imageCode =
                $imageObj->getImageCode(
                    $basketExtra[$pskey . '.']['image.'] ?? [],
                    $theCode
                );
            $markerArray['###' . $markerkey . '_TITLE###'] = $basketExtra[$pskey . '.']['title'];
        }

        $markerArray['###' . $markerkey . '_IMAGE###'] = $imageCode;
    }

    public static function getMarkerArray(
        $theCode,
        &$markerArray,
        $pid,
        $useBackPid,
        $calculatedArray,
        $basketExtra
    ) {
        $cObj = FrontendUtility::getContentObjectRenderer();
        $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
        $urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');

        // payment
        self::getTypeMarkerArray(
            $theCode,
            $markerArray,
            'payment',
            '',
            $pid,
            $useBackPid,
            $calculatedArray,
            $basketExtra
        );

        // shipping
        self::getTypeMarkerArray(
            $theCode,
            $markerArray,
            'shipping',
            '',
            $pid,
            $useBackPid,
            $calculatedArray,
            $basketExtra
        );

        $markerArray['###SHIPPING_WEIGHT###'] = doubleval($calculatedArray['weight']);
        $markerArray['###DELIVERYCOSTS###'] = $priceViewObj->priceFormat(self::getDeliveryCosts($calculatedArray));

        if (isset($basketExtra['handling.'])) {
            // 			foreach ($basketExtra['handling.'] as $k => $confArray)	{
            // 				$this->getTypeMarkerArray($markerArray, 'handling', $basketUrl);
            // 			}

            foreach ($basketExtra['handling.'] as $k => $confArray) {
                if (strpos($k, '.') == strlen($k) - 1) {
                    $k1 = substr($k, 0, strlen($k) - 1);
                    if (
                        MathUtility::canBeInterpretedAsInteger($k1)
                    ) {
                        self::getTypeMarkerArray(
                            $theCode,
                            $markerArray,
                            'handling',
                            $k1,
                            $pid,
                            $useBackPid,
                            $calculatedArray,
                            $basketExtra
                        );
                    }
                }
            }
        }
    }

    /**
     * Template marker substitution
     * Fills in the markerArray with data for a product.
     *
     * @param	array		reference to an item array with all the data of the item
     * @param	array		marker array
     *
     * @return	array
     *
     * @access private
     */
    public static function getModelMarkerArray(
        $theCode,
        $title,
        $value,
        $imageCode,
        $activeArray,
        &$markerArray
    ) {
        // Returns a markerArray ready for substitution with information for the tt_producst record, $row

        $markerArray['###VALUE###'] = $value;
        $markerArray['###CHECKED###'] = ($value == implode('-', $activeArray) ? ' checked="checked"' : '');
        $markerArray['###TITLE###'] = $title;
        $markerArray['###IMAGE###'] = $imageCode;
    }

    /**
     * Generates a radio or selector box for payment shipping.
     */
    public static function generateRadioSelect(
        $theCode,
        $pskey,
        $subkey,
        $calculatedArray,
        $pid,
        $useBackPid,
        &$basketExtra
    ) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();

        /*
                    The conf-array for the payment/shipping/handling configuration has numeric keys for the elements
                    But there are also these properties:
                    .radio	  [boolean]	Enables radiobuttons instead of the default, selector-boxes
                    .wrap		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is false. See default value below
                    .template	[string]	Template string for the display of radiobuttons.  Only if .radio is true. See default below
                    */
        $cObj = FrontendUtility::getContentObjectRenderer();
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $active = $basketExtra[$pskey] ?? '';
        $activeArray = is_array($active) ? $active : (empty($active) ? [] : [$active]);
        $bUseXHTML = !empty($GLOBALS['TSFE']->config['config']['xhtmlDoctype']);
        $selectedText = ($bUseXHTML ? 'selected="selected"' : 'selected');
        $type = 0;
        $wrap = '';
        $confArray = [];
        $htmlInputAddition = '';

        if ($subkey != '') {
            $confArray = $conf[$pskey . '.'][$subkey . '.'] ?? '';
            $htmlInputAddition = '[' . $subkey . ']';
        } else {
            $confArray = $conf[$pskey . '.'] ?? '';
        }

        if (
            is_array($confArray)
        ) {
            if (isset($confArray['radio'])) {
                $type = $confArray['radio'];
            }
            if (isset($confArray['wrap'])) {
                $wrap = $confArray['wrap'];
            }
            if (isset($confArray['PIDlink'])) {
                $pid = $confArray['PIDlink'];
            }
        }

        if (
            !MathUtility::canBeInterpretedAsInteger($type)
        ) {
            $type = 0;
        }

        $urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
        $linkConf = [];
        $linkUrl = htmlspecialchars(
            FrontendUtility::getTypoLink_URL(
                $cObj,
                $pid,
                $urlObj->getLinkParams(
                    '',
                    [],
                    true,
                    $useBackPid
                ),
                '',
                $linkConf
            )
        );
        $out = '';
        $submitCode = 'this.form.action=\'' . $linkUrl . '\';this.form.submit();';

        $template = '';
        if (
            isset($confArray['template']) &&
            $confArray['template'] != ''
        ) {
            $template =
                preg_replace(
                    ['/###PSKEY###/', '/###INPUTADDITION###/', '/###SUBMIT###/'],
                    [$pskey, $htmlInputAddition, $submitCode],
                    $confArray['template']
                );
        } else {
            $template =
                '<input type="radio" name="recs[tt_products][' . $pskey . ']' . $htmlInputAddition . '" onClick="' . $submitCode . '" value="###VALUE###"###CHECKED###> ###TITLE### &nbsp;&nbsp;&nbsp; ###IMAGE###<br>';
        }

        $wrap = $wrap ? $wrap : '<select id="' . $pskey . ($subkey != '' ? '-' . $subkey : '') . '-select" name="recs[tt_products][' . $pskey . ']' . $htmlInputAddition . '" onChange="' . $submitCode . '">|</select>';
        $t = [];
        $localBasketExtra = [];
        if ($subkey != '') {
            $localBasketExtra = $basketExtra[$pskey . '.'][$subkey . '.'] ?? [];
        } else {
            $localBasketExtra = $basketExtra[$pskey . '.'] ?? [];
        }

        $actTitle = $localBasketExtra['title'] ?? '';
        $confArray = \tx_ttproducts_control_basket::cleanConfArr($confArray);
        $bWrapSelect = (count($confArray) > 1);

        if (is_array($confArray)) {
            foreach ($confArray as $key => $item) {
                if (
                    (!isset($item['show']) || $item['show']) &&
                    (
                        !isset($item['showLimit']) ||
                        doubleval($item['showLimit']) >= doubleval($calculatedArray['count']) ||
                        intval($item['showLimit']) == 0
                    )
                ) {
                    if (empty($activeArray)) {
                        // TODO: Make it configurable if the first item of the payment/shipping setup shall automatically be made active.
                        $activeArray = [$key];
                    }
                    $addItems = [];
                    $itemTable = '';
                    $itemTableView = '';
                    $tableName = '';

                    if (
                        isset($item['where.']) &&
                        $item['where.'] != '' &&
                        (strpos($item['title'], '###') !== false)
                    ) {
                        $tableName = key($item['where.']);
                        $itemTableView = $tablesObj->get($tableName, true);
                        $itemTable = $itemTableView->getModelObj();

                        if (($tableName == 'static_countries') && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables')) {
                            $viewTagArray = [];

                            if (is_object($itemTable)) {
                                $markerFieldArray = [];
                                $parentArray = [];
                                $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
                                $fieldsArray = $markerObj->getMarkerFields(
                                    $item['title'],
                                    $itemTable->getTableObj()->tableFieldArray,
                                    $itemTable->getTableObj()->requiredFieldArray,
                                    $markerFieldArray,
                                    $itemTable->marker,
                                    $viewTagArray,
                                    $parentArray
                                );

                                $addItems =
                                    $itemTable->get(
                                        '',
                                        0,
                                        false,
                                        $item['where.'][$tableName],
                                        '',
                                        '',
                                        '',
                                        implode(',', $fieldsArray)
                                    );
                            }
                        }
                    }

                    if (empty($addItems)) {
                        $addItems = ['0' => ''];
                    }

                    if (
                        isset($addItems) &&
                        is_array($addItems)
                    ) {
                        if ($type) {	// radio
                            foreach ($addItems as $k1 => $row) {
                                $image = '';
                                if (isset($item['image.'])) {
                                    $image = $item['image.'];
                                }
                                $title = $item['title'];

                                if (is_array($row) && $tableName != '') {
                                    if (
                                        isset($itemTableView) &&
                                        is_object($itemTableView)
                                    ) {
                                        $markerArray = [];
                                        $itemTableView->getRowMarkerArray(
                                            $tableName,
                                            $row,
                                            $markerArray,
                                            $fieldsArray
                                        );
                                        if (strpos($title, '###') !== false) {
                                            $title = $templateService->substituteMarkerArrayCached($title, $markerArray);
                                        }
                                    }

                                    $value = $key . '-' . $row['uid'];
                                    if ($value == implode('-', $activeArray)) {
                                        $actTitle = $title;
                                    }
                                    if (isset($row['image.'])) {
                                        $image = $row['image.'];
                                    }
                                } else {
                                    $value = $key;
                                }
                                $markerArray = [];
                                $imageCode = '';

                                if ($image != '') {
                                    $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');
                                    $imageCode =
                                        $imageObj->getImageCode(
                                            $image,
                                            $theCode
                                        );
                                }

                                self::getModelMarkerArray(
                                    $theCode,
                                    $title,
                                    $value,
                                    $imageCode,
                                    $activeArray,
                                    $markerArray
                                );

                                $out .= $templateService->substituteMarkerArrayCached($template, $markerArray) . chr(10);
                            }
                        } else {
                            foreach ($addItems as $k1 => $row) {
                                if (is_array($row) && $tableName != '') {
                                    $markerArray = [];
                                    $itemTableView->getRowMarkerArray(
                                        $tableName,
                                        $row,
                                        $markerArray,
                                        $fieldsArray
                                    );
                                    $title = $templateService->substituteMarkerArrayCached($item['title'], $markerArray);
                                    $title = htmlentities($title, ENT_QUOTES, 'UTF-8');
                                    $value = $key . '-' . $row['uid'];
                                    if ($value == implode('-', $activeArray)) {
                                        $actTitle = $item['title'];
                                    }
                                } else {
                                    $value = $key;
                                    $title = $item['title'];
                                }

                                if ($bWrapSelect) {
                                    $out .= '<option value="' . $value . '"' . ($value == implode('-', $activeArray) ? ' ' . $selectedText : '') . '>' . $title . '</option>' . chr(10);
                                } else {
                                    $out .= $title;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (strpos($actTitle, '###')) {
            $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
            $markerArray = [];
            $viewTagArray = [];
            $parentArray = [];
            $tmp = [];
            $fieldsArray = $markerObj->getMarkerFields(
                $actTitle,
                $tmp,
                $tmp,
                $tmp,
                $itemTable->marker,
                $viewTagArray,
                $parentArray
            );

            $markerArray = [];
            foreach ($viewTagArray as $tag => $v) {
                $markerArray['###' . $tag . '###'] = '?';
            }
            $actTitle = $templateService->substituteMarkerArrayCached($actTitle, $markerArray);
        }

        if ($subkey != '') {
            $basketExtra[$pskey . '.'][$subkey . '.']['title'] = $actTitle;
        } else {
            $basketExtra[$pskey . '.']['title'] = $actTitle;
        }

        if (!$type && $bWrapSelect) {
            $out = $cObj->wrap($out, $wrap);
        }

        return $out;
    } // generateRadioSelect

    protected static function matchCondition($confArray, $calculatedArray, $key)
    {
        $result = true;

        if (
            isset($confArray[$key . '.']) &&
            isset($confArray[$key . '.']['type']) &&
            $confArray[$key . '.']['type'] == 'sql' &&
            isset($confArray[$key . '.']['where'])
        ) {
            $row = ['amount' => $calculatedArray['priceTax']['goodstotal']['ALL']];
            $result = \tx_ttproducts_sql::isValid($row, $confArray[$key . '.']['where']);
        }

        return $result;
    }

    public static function getConfiguredPrice(
        $pskey,
        $subkey,
        $row,
        $itemArray,
        $calculatedArray,
        $basketExtra,
        $basketRecs,
        &$confArray,
        &$countTotal,
        &$priceTotalTax,
        &$priceTax,
        &$priceNoTax,
        &$resetPrice,
        &$funcParams = ''
    ) {
        $resetPrice = false;

        if (
            is_array($confArray) &&
            isset($confArray['type'])
        ) {
            $minPrice = 0;
            $priceNew = 0;
            if (isset($confArray['WherePIDMinPrice.'])) {
                // compare PIDList with values set in priceTaxWherePIDMinPrice in the SETUP
                // if they match, get the min. price
                // if more than one entry for priceTaxWherePIDMinPrice exists, the highest is value will be taken into account
                foreach ($confArray['WherePIDMinPrice.'] as $minPricePID => $minPriceValue) {
                    foreach ($itemArray as $sort => $actItemArray) {
                        foreach ($actItemArray as $k1 => $actItem) {
                            $tmpRow = $actItem['rec'];
                            $pid = intval($tmpRow['pid']);
                            if ($pid == $minPricePID) {
                                $minPrice = $minPriceValue;
                            }
                        }
                    }
                }
            }
            krsort($confArray);

            if (
                $confArray['type'] == 'count'
            ) {
                foreach ($confArray as $k1 => $price1) {
                    if (
                        MathUtility::canBeInterpretedAsInteger($k1) &&
                        $countTotal >= $k1
                    ) {
                        $priceNew = $price1;
                        break;
                    }
                }
            } elseif ($confArray['type'] == 'weight') {
                foreach ($confArray as $k1 => $price1) {
                    if (
                        MathUtility::canBeInterpretedAsInteger($k1) &&
                        self::matchCondition($confArray, $calculatedArray, $k1) &&
                        ($calculatedArray['weight'] * 1000 >= $k1)
                    ) {
                        $priceNew = $price1;
                        break;
                    }
                }
            } elseif ($confArray['type'] == 'price') {
                foreach ($confArray as $k1 => $price1) {
                    if (
                        MathUtility::canBeInterpretedAsInteger($k1) &&
                        $priceTotalTax >= $k1
                    ) {
                        $priceNew = $price1;
                        break;
                    }
                }
            } elseif (
                $confArray['type'] == 'objectMethod' &&
                isset($confArray['class'])
            ) {
                $obj = GeneralUtility::makeInstance($confArray['class']);
                if (method_exists($obj, 'getConfiguredPrice')) {
                    $funcParams1 = $confArray['method.'];
                    $priceNew =
                        $obj->getConfiguredPrice(
                            $pskey,
                            $subkey,
                            $row,
                            $itemArray,
                            $calculatedArray,
                            $basketExtra,
                            $basketRecs,
                            $confArray,
                            $countTotal,
                            $priceTotalTax,
                            $priceTax,
                            $priceNoTax,
                            $funcParams1
                        );
                } else {
                    $priceNew = '0';
                }
            }

            if (is_array($funcParams)) {
                $hookObj = GeneralUtility::makeInstance($funcParams['class']);
                if (method_exists($hookObj, 'init')) {
                    $hookObj->init();
                }

                if (method_exists($hookObj, 'getConfiguredPrice')) {
                    $priceNew = $hookObj->getConfiguredPrice(
                        $pskey,
                        $subkey,
                        $row,
                        $itemArray,
                        $calculatedArray,
                        $confArray,
                        $basketExtra,
                        $basketRecs,
                        $countTotal,
                        $priceTotalTax,
                        $priceTax,
                        $priceNoTax,
                        $funcParams
                    );
                }
            }

            // compare the price to the min. price
            if ($minPrice > $priceNew) {
                $priceNew = $minPrice;
            }

            $resetPrice = false;

            if (isset($confArray['noCostsAmount'])) {
                // the total products price as from the payment/shipping is free
                $noCostsAmount = (float)$confArray['noCostsAmount'];
                if ($priceTotalTax >= $noCostsAmount) {
                    $resetPrice = true;
                }
            }

            if (
                isset($confArr['noCostsVoucher']) &&
                is_object($voucher = self::getVoucher()) &&
                $voucher->getValid() &&
                GeneralUtility::inList($confArr['noCostsVoucher'], $voucher->getCode())
            ) {
                $resetPrice = true;
            }

            if (isset($confArray['noCostsAmount.']) && isset($confArray['noCostsAmount.']['upTo'])) {
                $noCostsAmount = (float)$confArray['noCostsAmount.']['upTo'];
                if ($priceTotalTax <= $noCostsAmount) {
                    $resetPrice = true;
                }
            }

            if ($resetPrice) {
                $priceNew = 0;
                $priceTax = $priceNoTax = 0;
            }

            if (
                isset($confArray['maximum']) &&
                $priceTax > $confArray['maximum']
            ) {
                $priceNew = 0;
                $priceTax = $priceNoTax = $confArray['maximum'];
            }

            $taxIncluded = self::$priceObj->getTaxIncluded();
            $priceTax +=
                self::$priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $priceNew,
                    1,
                    $row,
                    $taxIncluded,
                    true
                );
            $priceNoTax +=
                self::$priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $priceNew,
                    0,
                    $row,
                    $taxIncluded,
                    true
                );
        }
    }

    public static function getDiscountPrices(
        $pskey,
        $confArray,
        $row,
        &$itemArray,
        $basketExtra,
        $basketRecs,
        $taxIncluded,
        $priceTotalTax,
        &$discountArray,
        &$priceTax,
        &$priceNoTax
    ) {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();

        if ($pskey == 'shipping') {
            $calcSetup = 'shippingcalc';
        } elseif ($pskey == 'handling') {
            $calcSetup = 'handlingcalc';
        }

        if (
            $calcSetup != '' &&
            isset($confArray['price.']) &&
            is_array($confArray['price.']) &&
            isset($confArray['price.']['calc.']) &&
            isset($confArray['price.']['calc.']['use']) &&
            isset($conf[$calcSetup . '.']) &&
            is_array($conf[$calcSetup . '.'])
        ) {
            $useArray = GeneralUtility::trimExplode(',', $confArray['price.']['calc.']['use']);
            $specialCalc = [];

            foreach ($conf[$calcSetup . '.'] as $k => $v) {
                $kInt = trim($k, '.'); // substr($k, 0, strlen($k) - 1);
                if (in_array($kInt, $useArray)) {
                    $specialCalc[$k] = $v;
                }
            }
            $discountPriceObj = GeneralUtility::makeInstance('tx_ttproducts_discountprice');
            $priceReduction = [];
            $extMergeArray = ['tt_products_articles'];
            $discountPriceObj->getCalculatedData(
                $itemArray,
                $specialCalc,
                $pskey,
                $priceReduction,
                $discountArray,
                $priceTotalTax,
                false,
                $taxIncluded,
                true
            );

            if (is_array($discountArray) && count($discountArray)) {
                $localPriceTotal = 0;
                foreach ($discountArray as $uid => $price) {
                    $localPriceTotal += $price;
                }
                $priceTax =
                    $priceTax +
                    self::$priceObj->getPrice(
                        $basketExtra,
                        $basketRecs,
                        $localPriceTotal,
                        true,
                        $row,
                        $taxIncluded,
                        true
                    );
                $priceNoTax =
                    $priceNoTax +
                    self::$priceObj->getPrice(
                        $basketExtra,
                        $basketRecs,
                        $localPriceTotal,
                        false,
                        $row,
                        $taxIncluded,
                        true
                    );
            }
        }
    }

    public static function addItemShippingPrices(
        &$priceShippingTax,
        &$priceShippingNoTax,
        $row,
        $basketExtra,
        $basketRecs,
        $taxIncluded,
        $itemArray
    ) {
        foreach ($itemArray as $sort => $actItemArray) {
            // $actItemArray = all items array
            foreach ($actItemArray as $k2 => $actItem) {
                $row = $actItem['rec'];
                // 				$shippingPrice = $actItem['shipping'] + $row['shipping'];
                // 				$row['tax'] = $actItem['tax'];

                // 				if ($shippingPrice)	{
                // 					$priceShippingTax += $this->priceObj->getPrice($shippingPrice,true,$row,$taxIncluded,true);
                // 					$priceShippingNoTax += $this->priceObj->getPrice($shippingPrice,false,$row,$taxIncluded,true);
                // 				}

                if (!empty($row['bulkily'])) {
                    $value = floatval($basketExtra['shipping.']['bulkilyAddition'] ?? '') * $actItem['count'];
                    $row['tax'] = floatval($basketExtra['shipping.']['bulkilyFeeTax'] ?? 0);
                    $priceShippingTax +=
                        self::$priceObj->getPrice(
                            $basketExtra,
                            $basketRecs,
                            $value,
                            true,
                            $row,
                            $taxIncluded,
                            true
                        );

                    $priceShippingNoTax +=
                        self::$priceObj->getPrice(
                            $basketExtra,
                            $basketRecs,
                            $value,
                            false,
                            $row,
                            $taxIncluded,
                            true
                        );
                }
            }
        }
    }

    public static function getPrices(
        $basketExtra,
        $basketRecs,
        $taxIncluded,
        $iso3Seller,
        $iso3Buyer,
        $pskey,
        $subkey,
        $row,
        $pskeyConf,
        $countTotal,
        $priceTotalTax,
        $itemArray,
        &$calculatedArray,
        &$priceTax,
        &$priceNoTax,
        &$resetPrice
    ) {
        $cObj = FrontendUtility::getContentObjectRenderer();

        if (!empty($basketExtra[$pskey . '.'])) {
            if (
                $subkey != '' &&
                !empty($basketExtra[$pskey . '.'][$subkey . '.'])
            ) {
                $basketConf = $basketExtra[$pskey . '.'][$subkey . '.'];
            } else {
                $basketConf = $basketExtra[$pskey . '.'];
            }
        } else {
            $basketConf = [];
        }

        if (isset($basketConf['TAXincluded'])) {
            $taxIncluded = $basketConf['TAXincluded'];
        }
        $confArray = [];
        if (isset($basketConf['price.'])) {
            $confArray = $basketConf['price.'];
        }

        self::$priceObj->init($cObj, $pskeyConf, 0);
        if ($confArray) {
            $tmp = '';
            self::getConfiguredPrice(
                $pskey,
                $subkey,
                $row,
                $itemArray,
                $calculatedArray,
                $basketExtra,
                $basketRecs,
                $confArray,
                $countTotal,
                $priceTotalTax,
                $priceTax,
                $priceNoTax,
                $resetPrice,
                $tmp
            );
        } elseif (isset($basketConf['price'])) {
            $priceAdd = doubleval($basketConf['price']);

            if ($priceAdd) {
                $priceTaxAdd =
                    self::$priceObj->getPrice(
                        $basketExtra,
                        $basketRecs,
                        $priceAdd,
                        true,
                        $row,
                        $taxIncluded,
                        true
                    );
            } else {
                $priceTaxAdd = doubleval($basketConf['priceTax']);
            }
            $priceTax += $priceTaxAdd;
            $priceNoTaxAdd = doubleval($basketConf['priceNoTax']);

            if (!$priceNoTaxAdd) {
                $priceNoTaxAdd =
                    self::$priceObj->getPrice(
                        $basketExtra,
                        $basketRecs,
                        $priceTaxAdd,
                        false,
                        $row,
                        true,
                        true
                    );
            }
            $priceNoTax += $priceNoTaxAdd;
        }

        switch ($pskey) {
            case 'payment':
                $handleLib = '';
                if (
                    !empty($basketConf) &&
                    isset($basketConf['handleLib']) &&
                    isset($basketConf['handleLib.'])
                ) {
                    $handleLib = $basketConf['handleLib'];
                }

                if (
                    $handleLib &&
                    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($handleLib) &&
                    isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$handleLib]) &&
                    isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$handleLib]['api'])
                ) {
                    $callingClassName = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$handleLib]['api'];
                    $price = $priceTotalTax;
                    if (
                        isset($calculatedArray['handling']) &&
                        is_array($calculatedArray['handling'])
                    ) {
                        foreach ($calculatedArray['handling'] as $handling) {
                            $price += $handling['priceTax'];
                        }
                    }

                    if (
                        isset($calculatedArray['shipping']) &&
                        is_array($calculatedArray['shipping'])
                    ) {
                        $price += $calculatedArray['shipping']['priceTax'];
                    }

                    if (
                        isset($basketConf['handleLib.']['costs']) &&
                        $basketConf['handleLib.']['costs'] == 'auto' &&
                        class_exists($callingClassName) &&
                        method_exists($callingClassName, 'getCosts')
                    ) {
                        $priceTax = $priceNoTax = call_user_func(
                            $callingClassName . '::getCosts',
                            $basketConf['handleLib.'],
                            $price,
                            $iso3Seller,
                            $iso3Buyer
                        );
                    }
                }
                break;
            case 'shipping':
                self::addItemShippingPrices(
                    $priceTax,
                    $priceNoTax,
                    $row,
                    $basketExtra,
                    $basketRecs,
                    $taxIncluded,
                    $itemArray
                );
                break;
        }
    }

    public static function getBasketConf(
        $basketExtra,
        $pskey,
        $subkey = ''
    ) {
        $basketConf = [];
        if (isset($basketExtra[$pskey . '.'])) {
            if ($subkey != '' && !empty($basketExtra[$pskey . '.'][$subkey . '.'])) {
                $basketConf = $basketExtra[$pskey . '.'][$subkey . '.'];
            } elseif (
                isset($basketExtra[$pskey . '.'])
            ) {
                $basketConf = $basketExtra[$pskey . '.'];
            }
        }

        return $basketConf;
    }

    public static function getSpecialPrices(
        $basketExtra,
        $basketRecs,
        $pskey,
        $subkey,
        $row,
        $calculatedArray,
        &$priceShippingTax,
        &$priceShippingNoTax
    ) {
        $basketConf = self::getBasketConf($basketExtra, $pskey, $subkey);

        $perc = doubleval($basketConf['percentOfGoodstotal'] ?? 0);
        if ($perc) {
            $priceShipping = doubleval(($calculatedArray['priceTax']['goodstotal']['ALL'] / 100) * $perc);
            $dum = self::$priceObj->getPrice(
                $basketExtra,
                $basketRecs,
                $priceShipping,
                true,
                $row
            );
            $taxIncluded = self::$priceObj->getTaxIncluded();
            $priceShippingTax = $priceShippingTax +
                self::$priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $priceShipping,
                    true,
                    $row,
                    $taxIncluded,
                    true
                );
            $priceShippingNoTax = $priceShippingNoTax +
                self::$priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $priceShipping,
                    false,
                    $row,
                    $taxIncluded,
                    true
                );
        }

        $calculationScript = $basketConf['calculationScript'] ?? '';
        if ($calculationScript != '') {
            $calcScript = '';
            $sanitizer = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class);
            $calcScript = $sanitizer->sanitize($calculationScript);

            if ($calcScript) {
                $confScript = $basketConf['calculationScript.'];
                include $calcScript;
            }
        }
    }

    public static function getPaymentShippingData(
        $basketExtra,
        $basketRecs,
        $conf,
        $iso3Seller,
        $iso3Buyer,
        $countTotal,
        $priceTotalTax,
        $shippingRow,
        $paymentRow,
        $itemArray,
        &$calculatedArray,
        &$priceShippingTax,
        &$priceShippingNoTax,
        &$pricePaymentTax,
        &$pricePaymentNoTax
    ) {
        $row = $shippingRow;
        $taxIncluded = self::$priceObj->getTaxIncluded();

        $weigthFactor = doubleval($basketExtra['shipping.']['priceFactWeight'] ?? 0);
        if ($weigthFactor > 0) {
            $priceShipping = $calculatedArray['weight'] * $weigthFactor;
            $priceShippingTax +=
                self::$priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $priceShipping,
                    true,
                    $row,
                    $taxIncluded,
                    true
                );
            $priceShippingNoTax +=
                self::$priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $priceShipping,
                    false,
                    $row,
                    $taxIncluded,
                    true
                );
        }

        $countFactor = doubleval($basketExtra['shipping.']['priceFactCount'] ?? 0);
        if ($countFactor > 0) {
            $priceShipping = $countTotal * $countFactor;
            $priceShippingTax +=
                self::$priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $priceShipping,
                    true,
                    $row,
                    $taxIncluded,
                    true
                );
            $priceShippingNoTax +=
                self::$priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $priceShipping,
                    false,
                    $row,
                    $taxIncluded,
                    true
                );
        }

        self::getSpecialPrices(
            $basketExtra,
            $basketRecs,
            'shipping',
            '',
            $row,
            $calculatedArray,
            $priceShippingTax,
            $priceShippingNoTax
        );

        $pskeyConf = [];
        if (isset($conf['shipping.'])) {
            $pskeyConf = $conf['shipping.'];
        }
        $resetPrice = false;
        self::getPrices(
            $basketExtra,
            $basketRecs,
            $taxIncluded,
            $iso3Seller,
            $iso3Buyer,
            'shipping',
            '',
            $row,
            $pskeyConf,
            $countTotal,
            $priceTotalTax,
            $itemArray,
            $calculatedArray,
            $priceShippingTax,
            $priceShippingNoTax,
            $resetPrice
        );

        if (!$resetPrice) {
            $discountArray = [];
            $basketConf = self::getBasketConf($basketExtra, 'shipping');

            self::getDiscountPrices(
                'shipping',
                $basketConf,
                $row,
                $itemArray,
                $basketExtra,
                $basketRecs,
                $taxIncluded,
                $priceTotalTax,
                $discountArray,
                $priceShippingTax,
                $priceShippingNoTax
            );
        }

        // Payment
        $pricePayment = $pricePaymentTax = $pricePaymentNoTax = 0;
        $taxpercentage = '';
        $row = $paymentRow;
        $payment = 0;
        $perc = doubleval($basketExtra['payment.']['percentOfTotalShipping'] ?? 0);
        if ($perc) {
            $payment = ($calculatedArray['priceTax']['goodstotal']['ALL'] + $calculatedArray['shipping']['priceTax']) * doubleval($perc);
        }

        if ($payment) {
            $pricePaymentTax +=
                self::$priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $payment,
                    true,
                    $row,
                    $taxIncluded,
                    true
                );
            $pricePaymentNoTax +=
                self::$priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $payment,
                    false,
                    $row,
                    $taxIncluded,
                    true
                );
        }

        self::getSpecialPrices(
            $basketExtra,
            $basketRecs,
            'payment',
            '',
            $row,
            $calculatedArray,
            $pricePaymentTax,
            $pricePaymentNoTax
        );

        $pskeyConf = [];
        if (!empty($conf['payment.'])) {
            $pskeyConf = $conf['payment.'];
        }
        self::getPrices(
            $basketExtra,
            $basketRecs,
            $taxIncluded,
            $iso3Seller,
            $iso3Buyer,
            'payment',
            '',
            $row,
            $pskeyConf,
            $countTotal,
            $priceTotalTax,
            $itemArray,
            $calculatedArray,
            $pricePaymentTax,
            $pricePaymentNoTax,
            $resetPrice
        );
    } // getPaymentShippingData

    public static function getHandlingData(
        $basketExtra,
        $basketRecs,
        $conf,
        $iso3Seller,
        $iso3Buyer,
        $countTotal,
        $priceTotalTax,
        &$calculatedArray,
        $itemArray
    ) {
        $taxIncluded = self::$priceObj->getTaxIncluded();

        if (
            isset($basketExtra['handling.']) &&
            is_array($basketExtra['handling.'])
        ) {
            $pskey = 'handling';
            $pskeyConf = [];
            if (isset($conf[$pskey . '.'])) {
                $pskeyConf = $conf[$pskey . '.'];
            }

            foreach ($basketExtra[$pskey . '.'] as $k => $handlingRow) {
                if (strpos($k, '.') == strlen($k) - 1) {
                    $k1 = substr($k, 0, strlen($k) - 1);
                    if (
                        MathUtility::canBeInterpretedAsInteger($k1)
                    ) {
                        $tax =
                            self::getTaxPercentage($basketExtra, $pskey, $k1);
                        $row = [];
                        if ($tax != '') {
                            $row[] = ['tax' => $tax];
                        }

                        $priceTax = '';
                        $priceNoTax = '';

                        $discountArray = [];
                        $basketConf =
                            self::getBasketConf($basketExtra, $pskey, $k1);

                        self::getDiscountPrices(
                            $pskey,
                            $basketConf,
                            $row,
                            $itemArray,
                            $basketExtra,
                            $basketRecs,
                            $taxIncluded,
                            $priceTotalTax,
                            $discountArray,
                            $priceTax,
                            $priceNoTax
                        );
                        self::getSpecialPrices(
                            $basketExtra,
                            $basketRecs,
                            $pskey,
                            $k1,
                            $row,
                            $calculatedArray,
                            $priceTax,
                            $priceNoTax
                        );
                        self::getPrices(
                            $basketExtra,
                            $basketRecs,
                            $taxIncluded,
                            $iso3Seller,
                            $iso3Buyer,
                            $pskey,
                            $k1,
                            $row,
                            $pskeyConf,
                            $countTotal,
                            $priceTotalTax,
                            $itemArray,
                            $calculatedArray,
                            $priceTax,
                            $priceNoTax,
                            $resetPrice
                        );
                        $calculatedArray[$pskey][$k1]['priceTax'] = $priceTax;
                        $calculatedArray[$pskey][$k1]['priceNoTax'] = $priceNoTax;
                    }
                }
            }
        }
    } // getHandlingData

    /**
     * Include handle script.
     */
    public static function includeHandleScript(
        $handleScript,
        $confScript,
        $activity,
        &$bFinalize,
        $pibase,
        $infoViewObj
    ) {
        $content = '';

        include $handleScript;

        return $content;
    } // includeHandleScript

    /**
     * get the TAXpercentage from the shipping if available.
     */
    public static function getTaxPercentage(
        $basketExtra,
        $pskey = 'shipping',
        $subkey = ''
    ) {
        $result = 0;
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();

        if (
            $subkey == '' &&
            !empty($basketExtra[$pskey . '.']) &&
            isset($basketExtra[$pskey . '.']['TAXpercentage'])
        ) {
            $result = doubleval($basketExtra[$pskey . '.']['TAXpercentage']);
        } elseif (
            $subkey != '' &&
            !empty($basketExtra[$pskey . '.']) &&
            isset($basketExtra[$pskey . '.'][$subkey . '.']) &&
            is_array($basketExtra[$pskey . '.'][$subkey . '.']) &&
            isset($basketExtra[$pskey . '.'][$subkey . '.']['TAXpercentage'])
        ) {
            $result = doubleval($basketExtra[$pskey . '.'][$subkey . '.']['TAXpercentage']);
        } else {
            if (
                $subkey == '' &&
                isset($conf[$pskey . '.']['TAXpercentage'])
            ) {
                $result = doubleval($conf[$pskey . '.']['TAXpercentage']);
            } elseif (
                isset($conf[$pskey . '.']) &&
                isset($conf[$pskey . '.'][$subkey . '.']) &&
                isset($conf[$pskey . '.'][$subkey . '.']['TAXpercentage'])
            ) {
                $result = doubleval($conf[$pskey . '.'][$subkey . '.']['TAXpercentage']);
            }
        }

        return $result;
    }

    /**
     * get the replaceTAXpercentage from the shipping if available.
     */
    public static function getReplaceTaxPercentage(
        $basketExtra,
        $pskey = 'shipping',
        $itemTax = ''
    ) {
        $result = '';

        if (
            !empty($basketExtra[$pskey . '.']) &&
            isset($basketExtra[$pskey . '.']['replaceTAXpercentage'])
        ) {
            $result = doubleval($basketExtra[$pskey . '.']['replaceTAXpercentage']);
        }

        if (
            $itemTax != '' &&
            !empty($basketExtra[$pskey . '.']) &&
            isset($basketExtra[$pskey . '.']['replaceTAXpercentage.']) &&
            is_array($basketExtra[$pskey . '.']['replaceTAXpercentage.'])
        ) {
            $itemTax = doubleval($itemTax);
            if (isset($basketExtra[$pskey . '.']['replaceTAXpercentage.'][$itemTax])) {
                $result = doubleval($basketExtra[$pskey . '.']['replaceTAXpercentage.'][$itemTax]);
            }
        }

        return $result;
    }

    /**
     * get the delivery costs.
     */
    public static function getDeliveryCosts($calculatedArray)
    {
        $result = $calculatedArray['shipping']['priceTax'] + $calculatedArray['payment']['priceTax'];

        return $result;
    }

    /**
     * get the where condition for a shipping entry
     * E.g.:  30.where.static_countries = cn_short_local = 'Deutschland'.
     */
    public static function getWhere($basketExtra, $tablename)
    {
        $result = '';

        if (
            isset($basketExtra['shipping.']) &&
            !empty($basketExtra['shipping.']['where.'])
        ) {
            switch ($tablename) {
                case 'static_countries':
                    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables')) {
                        $eInfo = ExtensionUtility::getExtensionInfo('static_info_tables');
                        $sitVersion = $eInfo['version'];
                    }
                    if (version_compare($sitVersion, '2.0.1', '>=')) {
                        $result = $basketExtra['shipping.']['where.'][$tablename];
                    }
                    break;
            }
        }

        return $result;
    }

    public static function getAddRequiredInfoFields($type, $basketExtra)
    {
        $resultArray = [];
        $pskeyArray = self::getTypeArray();
        foreach ($pskeyArray as $pskey) {
            if (
                isset($basketExtra[$pskey . '.']) &&
                is_array($basketExtra[$pskey . '.'])
            ) {
                $tmp = '';
                if (
                    isset($basketExtra[$pskey . '.']['addRequiredInfoFields.']) &&
                    isset($basketExtra[$pskey . '.']['addRequiredInfoFields.'][$type])
                ) {
                    $tmp = $basketExtra[$pskey . '.']['addRequiredInfoFields.'][$type];
                } elseif (
                    isset($basketExtra[$pskey . '.']['addRequiredInfoFields'])
                ) {
                    $tmp = $basketExtra[$pskey . '.']['addRequiredInfoFields'];
                }

                if ($tmp != '') {
                    $resultArray[] = trim($tmp);
                }
            }
        }
        $result = implode(',', $resultArray);

        return $result;
    }

    public static function get($pskey, $setup, $basketExtra)
    {
        $result = '';
        $tmp = $basketExtra[$pskey . '.'][$setup] ?? '';
        if ($tmp != '') {
            $result = trim($tmp);
        }

        return $result;
    }

    public static function useCreditcard($basketExtra)
    {
        $result = false;
        $payConf = $basketExtra['payment.'] ?? [];
        if (is_array($payConf) && !empty($payConf['creditcards'])) {
            $result = true;
        }

        return $result;
    }

    public static function useAccount($basketExtra)
    {
        $result = false;
        $payConf = $basketExtra['payment.'] ?? [];
        if (is_array($payConf) && !empty($payConf['accounts'])) {
            $result = true;
        }

        return $result;
    }

    public static function getHandleLib($request, $basketExtra)
    {
        $result = false;
        $payConf = $basketExtra['payment.'] ?? [];
        $handleLib = '';

        if (is_array($payConf)) {
            $handleLib = $payConf['handleLib'] ?? '';
        }

        if (
            (
                strpos($handleLib, 'transactor') !== false ||
                strpos($handleLib, 'paymentlib') !== false
            ) &&
            isset($payConf['handleLib.']) &&
            is_array($payConf['handleLib.']) &&
            isset($payConf['handleLib.']['gatewaymode']) &&
            $payConf['handleLib.']['gatewaymode'] == $request &&
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($handleLib)
        ) {
            $result = $handleLib;
        }

        return $result;
    }
}
