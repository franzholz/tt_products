<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Franz Holzinger (franz@ttproducts.de)
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
 * payment shipping and basket extra functions
 *
 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author  René Fritz <r.fritz@colorcube.de>
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @author  Klaus Zierer <zierer@pz-systeme.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;


class tx_ttproducts_paymentshipping implements \TYPO3\CMS\Core\SingletonInterface {
	public $cObj;
	public $conf;
	public $config;
	public $basketView;
	public $priceObj;	// price functions
	protected $typeArray = array('handling','shipping','payment');
    protected $voucher;

	public function init ($cObj, $priceObj) {
		$this->cObj = $cObj;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->priceObj = clone $priceObj;	// new independant price object
        $voucher = GeneralUtility::makeInstance('tx_ttproducts_voucher');
        $this->setVoucher($voucher);
	}


	public function getTypeArray () {
		return $this->typeArray;
	}

    public function setVoucher ($voucher) {
        $this->voucher = $voucher;
    }

    public function getVoucher () {
        return $this->voucher;
    }

	public function getScriptPrices ($pskey='shipping', $basketExtra, &$calculatedArray, &$itemArray)	{
		$hookVar = 'scriptPrices';
		if ($hookVar && isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar]) &&
			is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar]) &&
			isset ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar][$pskey]) &&
			is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar][$pskey])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar][$pskey] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this);
				}
				if (method_exists($hookObj, 'getScriptPrices')) {
					$tmpArray = $hookObj->getScriptPrices($pskey, $basketExtra, $calculatedArray, $itemArray);
				}
			}
		}
	}


	/**
	 * get basket record for tracking, billing and delivery data row
	 */
	public function getBasketRec ($row) {
		$extraArray = array();
		$tmpArray = GeneralUtility::trimExplode(':', $row['payment']);
		$extraArray['payment'] = $tmpArray['0'];
		$tmpArray = GeneralUtility::trimExplode(':', $row['shipping']);
		$extraArray['shipping'] = $tmpArray['0'];

		$basketRec = array('tt_products' => $extraArray);

		return $basketRec;
	}


	/**
	 * Check if payment/shipping option is available
	 */
	public function checkExtraAvailable ($confArray)	{
		$result = false;

		if (is_array($confArray) && (!isset($confArray['show']) || $confArray['show']))	{
			$result = true;
		}

		return $result;
	} // checkExtraAvailable


	protected function helperSubpartArray ($markerPrefix, $bActive, $keyMarker, $confRow, $framework, $markerArray, &$subpartArray, &$wrappedSubpartArray)	{

		$theMarker = '###' . $markerPrefix . '_' . $keyMarker . '###';

		if ($bActive)	{
			$wrappedSubpartArray[$theMarker] = '';
		} else {
			$subpartArray[$theMarker] = '';
		}
	}


	/**
	 * Template marker substitution
	 * Fills in the subpartArray with data depending on payment and shipping
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @access private
	 */
	public function getSubpartArrays (
		&$subpartArray,
		&$wrappedSubpartArray,
		$basketExtra,
		$markerArray,
		$framework
	)	{
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');

		$typeArray = $this->getTypeArray();
		$psArray = array('payment', 'shipping');
		$psMessageArray = array();
		$tmpSubpartArray = array();
		$parser = $this->cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		$handleLib = $basketExtra['payment.']['handleLib'];

		if (
            strpos($handleLib,'transactor') !== false && ExtensionManagementUtility::isLoaded($handleLib)
        ) {
            $languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
				// Payment Transactor
			tx_transactor_api::init($languageObj, $this->cObj, $this->conf);

			tx_transactor_api::getItemMarkerSubpartArrays(
				$basketExtra['payment.']['handleLib.'],
				$subpartArray,
				$wrappedSubpartArray
			);
		} else {	// markers for the missing payment transactor extension
			$wrappedSubpartArray['###MESSAGE_PAYMENT_TRANSACTOR_NO###'] = '';
			$subpartArray['###MESSAGE_PAYMENT_TRANSACTOR_YES###'] = '';
		}

		foreach($typeArray as $k => $pskey)	{

			if (in_array($pskey, $psArray))	{
				$marker = strtoupper($pskey);
				$markerPrefix = 'MESSAGE_' . $marker;
				$keyArray = $basketExtra[$pskey];
				if (!is_array($keyArray))	{
					$keyArray = array($keyArray);
				}
				$psKey = '';
				$psMessageArray[$pskey] = '';

				foreach ($keyArray as $k => $value)	{
					if ($psKey)	{
						$psKey .= '_';
					}
					$psKey .= $value;
					$subFrameWork = tx_div2007_core::getSubpart($framework, '###' . $markerPrefix . '###');
					if ($subFrameWork != '') {
						$tmpSubpartArray[$pskey] = tx_div2007_core::getSubpart($subFrameWork, '###MESSAGE_' . $marker . '_' . $psKey . '###');
						$psMessageArray[$pskey] .= $parser->substituteMarkerArray($tmpSubpartArray[$pskey], $markerArray);
					}
					$subpartArray['###MESSAGE_' . $marker . '_NE_' . $psKey . '###'] = '';
				}
			}
		}
		$tagArray = $markerObj->getAllMarkers($framework);

		foreach($typeArray as $k => $pskey)	{
			$marker = strtoupper($pskey);
			$markerPrefix = 'MESSAGE_' . $marker;

			if (isset($this->conf[$pskey . '.']) && is_array($this->conf[$pskey . '.']))	{
				foreach($this->conf[$pskey . '.'] as $k2 => $v2)	{

					$k2int = substr($k2,0,-1);
					if (
						!tx_div2007_core::testInt($k2int)
					) {
						continue;
					}

					if ($pskey == 'handling')	{
						if (is_array($v2))	{
							foreach ($v2 as $k3 => $v3)	{
								$k3int = substr($k3,0,-1);
								if (
									!tx_div2007_core::testInt($k3int)
								) {
									continue;
								}
								$bActive = ($k3int == $basketExtra[$pskey . '.'][$k3int]['0']);
								$this->helperSubpartArray($markerPrefix . '_' . $k2int, $bActive, $k3int, $v3, $framework, $markerArray, $subpartArray, $wrappedSubpartArray);
							}
						}
					} else {
						$bActive = ($k2int == $basketExtra[$pskey][0]);
						$this->helperSubpartArray(
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

			foreach($tagArray as $k3 => $v3)	{

				if (strpos($k3, $markerPrefix) === 0 && !isset($subpartArray['###' . $k3 . '###']))	{

					if ($bCheckNE && strpos($k3,'_NE_') !== false)	{
						$wrappedSubpartArray['###' . $k3 . '###'] = '';
						$tmpSubpartArray[$pskey] = tx_div2007_core::getSubpart($framework, '###' . $k3 . '###');
						$psMessageArray[$pskey] .=
							tx_div2007_core::substituteMarkerArrayCached(
								$tmpSubpartArray[$pskey],
								$markerArray
							);
					} else if (!isset($wrappedSubpartArray['###' . $k3 . '###'])) {
						$subpartArray['###' . $k3 . '###'] = '';
					}
				}
			}
			$subpartArray['###' . $markerPrefix . '###'] = $psMessageArray[$pskey];
		}
	}


	protected function getTypeMarkerArray ($theCode, &$markerArray, $pskey, $subkey, $linkUrl, $calculatedArray, $basketExtra)	{
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');

		if ($subkey != '')	{
			$theCalculateArray = $calculatedArray[$pskey][$subkey];
		} else {
			$theCalculateArray = $calculatedArray[$pskey];
		}
		if (!is_array($theCalculateArray))	{
			$theCalculateArray = array();
		}

		$markerkey = strtoupper($pskey) . ($subkey != '' ? '_' . $subkey : '');
		$markerArray['###PRICE_' . $markerkey . '_TAX###'] = $priceViewObj->priceFormat($theCalculateArray['priceTax']);
		$markerArray['###PRICE_' . $markerkey . '_NO_TAX###'] = $priceViewObj->priceFormat($theCalculateArray['priceNoTax']);
		$markerArray['###PRICE_' . $markerkey . '_ONLY_TAX###'] = $priceViewObj->priceFormat($theCalculateArray['priceTax'] - $theCalculateArray['priceNoTax']);
		$markerArray['###' . $markerkey . '_SELECTOR###'] = $this->generateRadioSelect($theCode, $pskey, $subkey, $calculatedArray, $linkUrl, $basketExtra);
		$imageCode = '';
        $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');

		if ($subkey != '')	{
			if (isset($basketExtra[$pskey . '.'][$subkey . '.']['image.'])) {
                $imageCode =
                    $imageObj->getImageCode(
                        $basketExtra[$pskey . '.'][$subkey . '.']['image.'],
                        $theCode
                    ); // neu
			}
			$markerArray['###' . $markerkey . '_TITLE###'] = $basketExtra[$pskey . '.'][$subkey . '.']['title'];
		} else {
			if (isset($basketExtra[$pskey . '.']['image.'])) {
                $imageCode =
                    $imageObj->getImageCode(
                        $basketExtra[$pskey . '.']['image.'],
                        $theCode
                    ); // neu
			}
			$markerArray['###' . $markerkey . '_TITLE###'] = $basketExtra[$pskey . '.']['title'];
		}

		if ($imageCode != '' && $theCode == 'EMAIL') {
			tx_div2007_alpha5::fixImageCodeAbsRefPrefix($imageCode);
		}
		$markerArray['###' . $markerkey . '_IMAGE###'] = $imageCode;
	}


	public function getMarkerArray ($theCode, &$markerArray, $pid, $bUseBackPid, $calculatedArray, $basketExtra) {

        $linkConf = array('useCacheHash' => true);
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
		$urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
		$basketUrl = htmlspecialchars(
			FrontendUtility::getTypoLink_URL(
				$this->cObj,
				$pid,
				$urlObj->getLinkParams(
					'',
					array(),
					true,
					$bUseBackPid
				),
				'',
				$linkConf
			)
		);

		// payment
		$this->getTypeMarkerArray($theCode, $markerArray, 'payment', '', $basketUrl, $calculatedArray, $basketExtra);

		// shipping
		$this->getTypeMarkerArray($theCode, $markerArray, 'shipping', '', $basketUrl, $calculatedArray, $basketExtra);

		$markerArray['###SHIPPING_WEIGHT###'] = doubleval($calculatedArray['weight']);
		$markerArray['###DELIVERYCOSTS###'] = $priceViewObj->priceFormat($this->getDeliveryCosts($calculatedArray));

 		if (isset($basketExtra['handling.']))	{

 			foreach ($basketExtra['handling.'] as $k => $confArray)	{
				if (strpos($k,'.') == strlen($k) - 1)	{

					$k1 = substr($k,0,strlen($k) - 1);
					if (
						tx_div2007_core::testInt($k1)
					) {
						$this->getTypeMarkerArray($theCode, $markerArray, 'handling', $k1, $basketUrl, $calculatedArray, $basketExtra);
					}
				}
			}
 		}
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	array		marker array
	 * @return	array
	 * @access private
	 */
	public function getModelMarkerArray ($theCode, $title, $value, $imageCode, $activeArray, &$markerArray) {

			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$markerArray['###VALUE###'] = $value;
		$markerArray['###CHECKED###'] = ($value == implode('-', $activeArray) ? ' checked="checked"' : '');
		$markerArray['###TITLE###'] = $title;
		$markerArray['###IMAGE###'] = $imageCode;
	}


	/**
	 * Generates a radio or selector box for payment shipping
	 */
	public function generateRadioSelect ($theCode, $pskey, $subkey, $calculatedArray, $basketUrl, &$basketExtra)	{

			/*
			 The conf-array for the payment/shipping/handling configuration has numeric keys for the elements
			 But there are also these properties:
				.radio	  [boolean]	Enables radiobuttons instead of the default, selector-boxes
				.wrap		[string]	<select>|</select> - wrap for the selectorboxes.  Only if .radio is false. See default value below
				.template	[string]	Template string for the display of radiobuttons.  Only if .radio is true. See default below
			 */
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');

		$active = $basketExtra[$pskey];
		$activeArray = is_array($active) ? $active : array($active);
		$bUseXHTML = $GLOBALS['TSFE']->config['config']['xhtmlDoctype'] != '';
		$selectedText = ($bUseXHTML ? 'selected="selected"' : 'selected');
        $type = 0;
		$wrap = '';
		$confArray = [];
        $htmlInputAddition = '';

		if ($subkey != '')	{
			$confArray = $this->conf[$pskey . '.'][$subkey . '.'];

			$htmlInputAddition = '[' . $subkey . ']';
		} else {
			$confArray = $this->conf[$pskey . '.'];
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
			!tx_div2007_core::testInt($type)
		) {
			$type = 0;
		}

		$out = '';
		$submitCode = 'this.form.action=\'' . $basketUrl . '\';this.form.submit();';
		$template = (
			$confArray['template'] ?
				preg_replace('/[[:space:]]*\\.[[:space:]]*' . $pskey . '[[:space:]]*\\.[[:space:]]*/', $confArray['template']) :
				'<input type="radio" name="recs[tt_products][' . $pskey . ']' . $htmlInputAddition . '" onClick="' . $submitCode . '" value="###VALUE###"###CHECKED###>###TITLE###&nbsp;&nbsp;&nbsp; ###IMAGE###<br>'
			);
		$wrap = $wrap ? $wrap : '<select id="' . $pskey . ($subkey != '' ? '-' . $subkey : '') . '-select" name="recs[tt_products][' . $pskey . ']' . $htmlInputAddition . '" onChange="' . $submitCode . '">|</select>';
		$t = array();
		if ($subkey != '')	{
			$localBasketExtra = &$basketExtra[$pskey . '.'][$subkey . '.'];
		} else {
			$localBasketExtra = &$basketExtra[$pskey . '.'];
		}
		$actTitle = $localBasketExtra['title'];
        $confArray = tx_ttproducts_control_basket::cleanConfArr($confArray);
		$bWrapSelect = (count($confArray) > 1);

		if (is_array($confArray))	{
			foreach($confArray as $key => $item)	{
				if (
					($item['show'] || !isset($item['show'])) &&
					(!isset($item['showLimit']) || doubleval($item['showLimit']) >= doubleval($calculatedArray['count']) ||
					intval($item['showLimit']) == 0)
				) {
					$addItems = array();
					$itemTable = '';
					$itemTableView = '';
					$t['title'] = $item['title'];
					if ($item['where.'] && strstr($t['title'], '###'))	{
						$tableName = key($item['where.']);
						$itemTableView = $tablesObj->get($tableName,true);
						$itemTable = $itemTableView->getModelObj();

						if (($tableName == 'static_countries') && ExtensionManagementUtility::isLoaded('static_info_tables')) {
							$viewTagArray = array();
							if (is_object($itemTable))	{
								$markerFieldArray = array();
								$parentArray = array();
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
								$addItems = $itemTable->get('',0,false, $item['where.'][$tableName],'','','', implode(',',$fieldsArray));

								if (isset($addItems) && is_array($addItems))	{
									foreach ($addItems as $k1 => $row)	{
										foreach ($row as $field => $v)	{
											$addItems[$k1][$field] = tx_div2007_core::csConv($v, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['static_info_tables']['charset']);
										}
									}
								}
							}
						}
					}

					if (!count($addItems))	{
						$addItems = array('0' => '');
					}
					if (isset($addItems) && is_array($addItems))	{
						if ($type)  {	// radio

							foreach($addItems as $k1 => $row)	{
								$image = '';
								if (isset($item['image.'])) {
									$image = $item['image.'];
								}
								$title = $item['title'];

								if (is_array($row))	{
									if (
										isset($itemTableView) &&
										is_object($itemTableView)
									) {
										$markerArray = array();
										$itemTableView->getRowMarkerArray($row, $markerArray, $fieldsArray);
										$title = tx_div2007_core::substituteMarkerArrayCached($t['title'], $markerArray);
									}
									$value = $key . '-' . $row['uid'];
									if ($value == implode('-',$activeArray))	{
										$actTitle = $item['title'];
									}
									if (isset($row['image.'])) {
										$image = $row['image.'];
									}
								} else {
									$value = $key;
								}
								$markerArray = array();
								$imageCode = '';
								if ($image != '') {
                                    $imageCode =
                                        $imageObj->getImageCode(
                                            $image,
                                            $theCode
                                        );
								}

								$this->getModelMarkerArray(
									$theCode,
									$title,
									$value,
									$imageCode,
									$activeArray,
									$markerArray
								);

								$out .= tx_div2007_core::substituteMarkerArrayCached($template,  $markerArray) . chr(10);
							}
						} else {
							foreach ($addItems as $k1 => $row)	{
								if (is_array($row))	{
									$markerArray = array();
									$itemTableView->getRowMarkerArray($row, $markerArray, $fieldsArray);
									$title = tx_div2007_core::substituteMarkerArrayCached($t['title'], $markerArray);
									$title = htmlentities($title, ENT_QUOTES, 'UTF-8');
									$value = $key . '-' . $row['uid'];
									if ($value == implode('-',$activeArray))	{
										$actTitle = $item['title'];
									}
								} else {
									$value = $key;
									$title = $item['title'];
								}

								if ($bWrapSelect)	{
									$out .= '<option value="' . $value . '"' . ($value == implode('-',$activeArray) ? ' ' . $selectedText : '') . '>' . $title . '</option>' . chr(10);
								} else {
									$out .= $title;
								}
							}
						}
					}
				}
			}
		}

		if (strstr($actTitle, '###'))	{
			$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
			$markerArray = array();
			$viewTagArray = array();
			$parentArray = array();
			$fieldsArray = $markerObj->getMarkerFields(
				$actTitle,
				$tmp = array(),
				$tmp = array(),
				$tmp = array(),
				$itemTable->marker,
				$viewTagArray,
				$parentArray
			);

			$markerArray = array();
			foreach ($viewTagArray as $tag => $v)	{
				$markerArray['###' . $tag . '###'] = '?';
			}
			$actTitle = tx_div2007_core::substituteMarkerArrayCached($actTitle, $markerArray);
		}
		if ($subkey != '')	{

			$basketExtra[$pskey . '.'][$subkey . '.']['title'] = $actTitle;
		} else {

			$basketExtra[$pskey.'.']['title'] = $actTitle;
		}

		if (!$type && $bWrapSelect) {
			$out = $this->cObj->wrap($out, $wrap);
		}
		return $out;
	} // generateRadioSelect


	public function getConfiguredPrice (
		$pskey,
		$subkey,
		$row,
		$itemArray,
		$calculatedArray,
		$basketExtra,
		&$confArray,
		&$countTotal,
		&$priceTotalTax,
		&$priceTax,
		&$priceNoTax,
		&$funcParams=''
	) {
		if (is_array($confArray))	{

			$minPrice=0;
			$priceNew=0;
			if ($confArray['WherePIDMinPrice.']) {
					// compare PIDList with values set in priceTaxWherePIDMinPrice in the SETUP
					// if they match, get the min. price
					// if more than one entry for priceTaxWherePIDMinPrice exists, the highest is value will be taken into account
				foreach ($confArray['WherePIDMinPrice.'] as $minPricePID=>$minPriceValue) {
					foreach ($itemArray as $sort=>$actItemArray) {
						foreach ($actItemArray as $k1=>$actItem) {
							$tmpRow = &$actItem['rec'];
							$pid = intval($tmpRow['pid']);
							if ($pid == $minPricePID) {
								$minPrice = $minPriceValue;
							}
						}
					}
				}
			}
			krsort($confArray);
			if ($confArray['type'] == 'count') {
				foreach ($confArray as $k1 => $price1)	{
					if (
						tx_div2007_core::testInt($k1) &&
						$countTotal >= $k1
					) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArray['type'] == 'weight') {

				foreach ($confArray as $k1 => $price1)	{
					if (
						tx_div2007_core::testInt($k1) &&
						$calculatedArray['weight'] * 1000 >= $k1
					) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArray['type'] == 'price') {
				foreach ($confArray as $k1 => $price1)	{
					if (
						tx_div2007_core::testInt($k1) &&
						$priceTotalTax >= $k1
					) {
						$priceNew = $price1;
						break;
					}
				}
			} else if ($confArray['type'] == 'objectMethod' && isset($confArray['class'])) {
				$obj= GeneralUtility::makeInstance($confArray['class']);
				if (method_exists($obj,'getConfiguredPrice')){
					$funcParams = $confArray['method.'];
					$priceNew = $obj->getConfiguredPrice($pskey, $subkey, $row, $itemArray, $calculatedArray, $basketExtra, $confArray, $countTotal, $priceTotalTax, $priceTax, $priceNoTax, $funcParams);
				} else {
					$priceNew='0';
				}
			}

			if(is_array($funcParams)){
				$hookObj= GeneralUtility::makeInstance($funcParams['class']);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this);
				}
				if (method_exists($hookObj, 'getConfiguredPrice')) {
					$tmpArray = $hookObj->getConfiguredPrice(
						$pskey,
						$subkey,
						$row,
						$itemArray,
						$calculatedArray,
						$confArray,
						$basketExtra,
						$countTotal,
						$priceTotalTax,
						$priceTax,
						$priceNoTax,
						$funcParams
					);
				};
			}

			// compare the price to the min. price
			if ($minPrice > $priceNew) {
				$priceNew = $minPrice;
			}
            if (
                isset($confArray['noCostsAmount'])
            ) {
                // the total products price as from the payment/shipping is free
                $noCostsAmount = (double) $confArray['noCostsAmount'];
                if ($noCostsAmount && ($priceTotalTax >= $noCostsAmount)) {
                    $priceNew = 0;
                    $priceTax = $priceNoTax = 0;
                }
            }

            if (
                isset($confArray['noCostsVoucher']) &&
                is_object($voucher = $this->getVoucher()) &&
                $voucher->getValid() &&
                GeneralUtility::inList($confArray['noCostsVoucher'], $voucher->getCode())
            ) {
                $priceNew = 0;
                $priceTax = $priceNoTax = 0;
            }
			$taxIncluded = $this->priceObj->getTaxIncluded();
			$priceTax += $this->priceObj->getPrice($basketExtra,$priceNew,1,$row,$taxIncluded,true);
			$priceNoTax += $this->priceObj->getPrice($basketExtra,$priceNew,0,$row,$taxIncluded,true);
		}
	}


	public function getDiscountPrices (
		$pskey,
		$confArray,
		$row,
		$itemArray,
		$basketExtra,
		$taxIncluded,
		$priceTotalTax,
		&$discountArray,
		&$priceTax,
		&$priceNoTax
	)	{
		if ($pskey == 'shipping')	{
			$calcSetup = 'shippingcalc';
		} else if ($pskey == 'handling')	{
			$calcSetup = 'handlingcalc';
		}

		if ($calcSetup != '' && is_array($confArray['price.']) && isset($confArray['price.']['calc.']) && isset($confArray['price.']['calc.']['use']) && isset($this->conf[$calcSetup . '.']) && is_array($this->conf[$calcSetup . '.']))	{
			$useArray = GeneralUtility::trimExplode(',', $confArray['price.']['calc.']['use']);
			$specialCalc = array();

			foreach ($this->conf[$calcSetup . '.'] as $k => $v)	{
				$kInt = trim($k, '.'); // substr($k, 0, strlen($k) - 1);
				if (in_array($kInt, $useArray))	{
					$specialCalc[$k] = $v;
				}
			}
			$discountPriceObj = GeneralUtility::makeInstance('tx_ttproducts_discountprice');
			$priceReduction = array();
			$extMergeArray = array('tt_products_articles');
			$discountPriceObj->getCalculatedData(
				$itemArray,
				$specialCalc,
				$pskey,
				$priceReduction,
				$discountArray,
				$priceTotalTax,
				false,
				true
			);

			if (count($discountArray))	{
				$localPriceTotal = 0;
				foreach ($discountArray as $uid => $price)	{
					$localPriceTotal += $price;
				}
				$priceTax = $priceTax + $this->priceObj->getPrice($basketExtra, $localPriceTotal, true, $row, $taxIncluded, true);
				$priceNoTax = $priceNoTax + $this->priceObj->getPrice($basketExtra,  $localPriceTotal, false, $row, $taxIncluded, true);
			}
		}
	}


	public function addItemShippingPrices (
		&$priceShippingTax,
		&$priceShippingNoTax,
		$row,
		$basketExtra,
		$taxIncluded,
		$itemArray
	)	{

		foreach ($itemArray as $sort=>$actItemArray) {

			// $actItemArray = all items array
			foreach ($actItemArray as $k2=>$actItem) {
				$row = &$actItem['rec'];
// 				$shippingPrice = $actItem['shipping'] + $row['shipping'];
// 				$row['tax'] = $actItem['tax'];

// 				if ($shippingPrice)	{
// 					$priceShippingTax += $this->priceObj->getPrice($shippingPrice,true,$row,$taxIncluded,true);
// 					$priceShippingNoTax += $this->priceObj->getPrice($shippingPrice,false,$row,$taxIncluded,true);
// 				}
				if ($row['bulkily'])	{
					$value = floatval($basketExtra['shipping.']['bulkilyAddition']) * $actItem['count'];
					$row['tax'] = floatval($basketExtra['shipping.']['bulkilyFeeTax']);
					$priceShippingTax += $this->priceObj->getPrice($basketExtra, $value, true, $row, $taxIncluded, true);
					$priceShippingNoTax += $this->priceObj->getPrice($basketExtra, $value, false, $row, $taxIncluded, true);
				}
			}
		}
	}


	public function getPrices ($pskey, $basketExtra, $subkey, $row, $countTotal, $priceTotalTax, $itemArray, $calculatedArray, &$priceTax, &$priceNoTax) {

		if (isset($basketExtra[$pskey.'.'])) {
			if ($subkey != '' && isset($basketExtra[$pskey . '.'][$subkey . '.'])) {
				$basketConf = $basketExtra[$pskey.'.'][$subkey . '.'];
			} else {
				$basketConf = $basketExtra[$pskey.'.'];
			}
		} else {
			$basketConf = array();
		}
		$taxIncluded = $this->conf['TAXincluded'];
		if (isset($basketConf['TAXincluded'])) {
			$taxIncluded = $basketConf['TAXincluded'];
		}
		$confArray = $basketConf['price.'];
		$confArray = ($confArray ? $confArray : $basketConf['priceTax.']);
		$this->priceObj->init($this->cObj, $this->conf[$pskey.'.'], 0);
		if ($confArray) {
			$this->getConfiguredPrice(
				$pskey,
				$subkey,
				$row,
				$itemArray,
				$calculatedArray,
				$basketExtra,
				$confArray,
				$countTotal,
				$priceTotalTax,
				$priceTax,
				$priceNoTax,
				$tmp=''
			);
		} else {
			$priceAdd = doubleVal($basketConf['price']);

			if ($priceAdd) {
				$priceTaxAdd = $this->priceObj->getPrice($basketExtra, $priceAdd, true, $row, $taxIncluded, true);
			} else {
				$priceTaxAdd = doubleVal($basketConf['priceTax']);
			}
			$priceTax += $priceTaxAdd;
			$priceNoTaxAdd = doubleVal($basketConf['priceNoTax']);

			if (!$priceNoTaxAdd) {
				$priceNoTaxAdd = $this->priceObj->getPrice($basketExtra, $priceTaxAdd, false, $row, true, true);
			}
			$priceNoTax += $priceNoTaxAdd;
		}

		if ($pskey == 'shipping') {
			$this->addItemShippingPrices(
				$priceTax,
				$priceNoTax,
				$row,
				$basketExtra,
				$taxIncluded,
				$itemArray
			);
		}
	}


	public function getBasketConf ($basketExtra, $pskey, $subkey='')	{

		if (isset($basketExtra[$pskey.'.']))	{
			if ($subkey != '' && isset($basketExtra[$pskey . '.'][$subkey . '.']))	{
				$basketConf = $basketExtra[$pskey.'.'][$subkey . '.'];
			} else {
				$basketConf = $basketExtra[$pskey.'.'];
			}
		} else {
			$basketConf = array();
		}
		return $basketConf;
	}


	public function getSpecialPrices ($basketExtra, $pskey, $subkey, $row, $calculatedArray, &$priceShippingTax, &$priceShippingNoTax)	{
		$basketConf = $this->getBasketConf($basketExtra, $pskey, $subkey);

		$perc = doubleVal($basketConf['percentOfGoodstotal']);
		if ($perc)  {
			$priceShipping = doubleVal(($calculatedArray['priceTax']['goodstotal']/100) * $perc);
			$dum = $this->priceObj->getPrice($basketExtra, $priceShipping, true, $row);
			$taxIncluded = $this->priceObj->getTaxIncluded();
			$priceShippingTax = $priceShippingTax + $this->priceObj->getPrice($basketExtra, $priceShipping, true, $row, $taxIncluded, true);
			$priceShippingNoTax = $priceShippingNoTax + $this->priceObj->getPrice($basketExtra, $priceShipping, false, $row, $taxIncluded, true);
		}

		$calculationScript = $basketConf['calculationScript'];
		if ($calculationScript) {
            $calcScript = '';
            if (
                version_compare(TYPO3_version, '9.4.0', '>=')
            ) {
                $sanitizer = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class);
                $calcScript = $sanitizer->sanitize($calculationScript);
            } else {          
                $calcScript = $GLOBALS['TSFE']->tmpl->getFileName($calculationScript);
            }

			if ($calcScript)	{
				$confScript = $basketConf['calculationScript.'];
				include($calcScript);
			}
		}
	}


	public function getPaymentShippingData (
			$basketExtra,
			$countTotal,
			$priceTotalTax,
			$shippingRow,
			$paymentRow,
			$itemArray,
			$calculatedArray,
			&$priceShippingTax,
			&$priceShippingNoTax,
			&$pricePaymentTax,
			&$pricePaymentNoTax
		) {
		$row = $shippingRow;
		$taxIncluded = $this->priceObj->getTaxIncluded();

		// Shipping
		$weigthFactor = doubleVal($basketExtra['shipping.']['priceFactWeight']);
		if($weigthFactor > 0) {
			$priceShipping = $calculatedArray['weight'] * $weigthFactor;
			$priceShippingTax += $this->priceObj->getPrice($basketExtra, $priceShipping, true, $row, $taxIncluded, true);
			$priceShippingNoTax += $this->priceObj->getPrice($basketExtra, $priceShipping, false, $row, $taxIncluded, true);
		}
		$countFactor = doubleVal($basketExtra['shipping.']['priceFactCount']);
		if($countFactor > 0) {
			$priceShipping = $countTotal * $countFactor;
			$priceShippingTax += $this->priceObj->getPrice($basketExtra, $priceShipping, true, $row, $taxIncluded, true);
			$priceShippingNoTax += $this->priceObj->getPrice($basketExtra, $priceShipping, false, $row, $taxIncluded, true);
		}
		$this->getSpecialPrices($basketExtra, 'shipping', '', $row, $calculatedArray, $priceShippingTax, $priceShippingNoTax);
		$this->getPrices('shipping', $basketExtra, '', $row, $countTotal, $priceTotalTax, $itemArray, $calculatedArray, $priceShippingTax, $priceShippingNoTax);
		$discountArray = array();
		$basketConf = $this->getBasketConf($basketExtra, 'shipping');

		$this->getDiscountPrices(
			'shipping',
			$basketConf,
			$row,
			$itemArray,
			$basketExtra,
			$taxIncluded,
			$priceTotalTax,
			$discountArray,
			$priceShippingTax,
			$priceShippingNoTax
		);

			// Payment
		$pricePayment = $pricePaymentTax = $pricePaymentNoTax = 0;
		$taxpercentage = '';
		$row = $paymentRow;
		$perc = doubleVal($basketExtra['payment.']['percentOfTotalShipping']);
		if ($perc)  {
			$payment = ($calculatedArray['priceTax']['goodstotal'] + $calculatedArray['shipping']['priceTax'] ) * doubleVal($perc);
			$pricePaymentTax += $this->priceObj->getPrice($basketExtra, $payment, true, $row, $taxIncluded, true);
			$pricePaymentNoTax += $this->priceObj->getPrice($basketExtra, $payment, false, $row, $taxIncluded, true);
		}
		$this->getSpecialPrices($basketExtra, 'payment', '', $row, $calculatedArray, $pricePaymentTax, $pricePaymentNoTax);
		$this->getPrices('payment', $basketExtra, '', $row, $countTotal, $priceTotalTax, $itemArray, $calculatedArray, $pricePaymentTax, $pricePaymentNoTax);
	} // getPaymentShippingData


	public function getHandlingData (
			$basketExtra,
			$countTotal,
			$priceTotalTax,
			&$calculatedArray,
			$itemArray
		)	{
		$taxIncluded = $this->priceObj->getTaxIncluded();
		$rc = '';

		if (isset($basketExtra['handling.']) && is_array($basketExtra['handling.']))	{
			$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
			$pskey = 'handling';

			foreach ($basketExtra[$pskey . '.'] as $k => $handlingRow)	{

				if (strpos($k,'.') == strlen($k) - 1)	{
					$k1 = substr($k,0,strlen($k) - 1);
					if (
						tx_div2007_core::testInt($k1)
					) {
						$tax = $this->getTaxPercentage($basketExtra, $pskey, $k1);
						$row = array();
						if ($tax != '') {
							$row[] = array('tax' => $tax);
						}

						$priceTax = '';
						$priceNoTax = '';

						$discountArray = array();
						$basketConf = $this->getBasketConf($basketExtra, $pskey, $k1);

						$this->getDiscountPrices(
							$pskey,
							$basketConf,
							$row,
							$itemArray,
							$basketExtra,
							$taxIncluded,
							$priceTotalTax,
							$discountArray,
							$priceTax,
							$priceNoTax
						);
						$this->getSpecialPrices($basketExtra, $pskey, $k1, $row, $calculatedArray, $priceTax, $priceNoTax);
						$this->getPrices($pskey, $basketExtra, $k1, $row, $countTotal, $priceTotalTax, $itemArray, $calculatedArray, $priceTax, $priceNoTax);
						$calculatedArray[$pskey][$k1]['priceTax'] = $priceTax;
						$calculatedArray[$pskey][$k1]['priceNoTax'] = $priceNoTax;
					}
				}
			}
		}
	} // getHandlingData


	/**
	 * Include handle script
	 */
	public function includeHandleScript ($handleScript, &$confScript, $activity, &$bFinalize, $pibase, $infoViewObj)	{
		$content = '';

		include($handleScript);
		return $content;
	} // includeHandleScript


	/**
	 * get the TAXpercentage from the shipping if available
	 */
	public function getTaxPercentage ($basketExtra, $pskey='shipping', $subkey) {

		if ($subkey == '' && is_array($basketExtra[$pskey.'.']) && isset($basketExtra[$pskey.'.']['TAXpercentage']))	{
			$rc = doubleval($basketExtra[$pskey.'.']['TAXpercentage']);
		} else if (
			$subkey != '' &&
			is_array($basketExtra[$pskey . '.']) &&
			isset($basketExtra[$pskey . '.'][$subkey . '.']) &&
			is_array($basketExtra[$pskey . '.'][$subkey . '.']) &&
			isset($basketExtra[$pskey . '.'][$subkey . '.']['TAXpercentage'])
		)	{
			$rc = doubleval($basketExtra[$pskey . '.'][$subkey . '.']['TAXpercentage']);
		} else {
			if ($subkey == '')	{
				$rc = $this->conf[$pskey . '.']['TAXpercentage'];
			} else {
				$rc = $this->conf[$pskey . '.'][$subkey . '.']['TAXpercentage'];
			}
		}
		return $rc;
	}


	/**
	 * get the replaceTAXpercentage from the shipping if available
	 */
	public function getReplaceTaxPercentage (
		$basketExtra,
		$pskey = 'shipping',
		$itemTax = ''
	) {
		$result = '';

		if (
			is_array($basketExtra[$pskey . '.']) &&
			isset($basketExtra[$pskey . '.']['replaceTAXpercentage'])
		) {
			$result = doubleval($basketExtra[$pskey . '.']['replaceTAXpercentage']);
		}

		if (
			$itemTax != '' &&
			is_array($basketExtra[$pskey . '.']) &&
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
	 * get the delivery costs
	 */
	public function getDeliveryCosts ($calculatedArray) {
		$rc = $calculatedArray['shipping']['priceTax'] + $calculatedArray['payment']['priceTax'];
		return $rc;
	}


	/**
	 * get the where condition for a shipping entry
	 * E.g.:  30.where.static_countries = cn_short_local = 'Deutschland'
	 */
	public function getWhere ($basketExtra, $tablename)	{

		if (is_array($basketExtra['shipping.']) && isset($basketExtra['shipping.']['where.']))	{
			switch ($tablename) {
				case 'static_countries':
					if (ExtensionManagementUtility::isLoaded('static_info_tables')) {
						$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003('static_info_tables');
						$sitVersion = $eInfo['version'];
					}
					if (version_compare($sitVersion, '2.0.1', '>='))	{
						$rc = $basketExtra['shipping.']['where.'][$tablename];
					}
				break;
			}
		}
		return $rc;
	}


	public function getAddRequiredInfoFields ($type, $basketExtra) {
		$resultArray = array();
		$pskeyArray = $this->getTypeArray();
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
				} else {
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


	public function get ($pskey, $setup, $basketExtra)	{
		$rc = '';
		$tmp = $basketExtra[$pskey.'.'][$setup];
		if ($tmp != '')	{
			$rc = trim($tmp);
		}
		return $rc;
	}


	public function useCreditcard ($basketExtra)	{
		$rc = false;
		$payConf = $basketExtra['payment.'];
		if (is_array($payConf) && $payConf['creditcards'] != '')	{
			$rc = true;
		}
		return $rc;
	}


	public function useAccount ($basketExtra)	{
		$rc = false;
		$payConf = &$basketExtra['payment.'];
		if (is_array($payConf) && $payConf['accounts'] != '')	{
			$rc = true;
		}
		return $rc;
	}


	public function getHandleLib ($request, $basketExtra)	{ // getGatewayRequestExt

		$rc = false;
		$payConf = $basketExtra['payment.'];

		if (is_array($payConf))	{
			$handleLib = $payConf['handleLib'];
		}

		if (
			(strpos($handleLib,'transactor') !== false) &&
			is_array($payConf['handleLib.']) &&
			$payConf['handleLib.']['gatewaymode'] == $request &&
			ExtensionManagementUtility::isLoaded($handleLib)
		)	{
			$rc = $handleLib;
		}
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentshipping.php']);
}


