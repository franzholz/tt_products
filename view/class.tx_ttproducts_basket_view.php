<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Kasper Skårhøj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * basket functions for a basket object
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Renè Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_basket_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $pibase; // reference to object of pibase
	public $pibaseClass;
	public $cObj;
	public $conf;
	public $config;
	public $price; // price object
	public $urlObj; // url functions
	public $urlArray; // overridden url destinations
	public $funcTablename;
	public $useArticles;


	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basketObj is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param	string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	public function init (
		$pibaseClass,
		$urlArray = array(),
		$useArticles
    )	{
		$this->pibaseClass = $pibaseClass;
		$this->pibase =GeneralUtility::makeInstance('' . $pibaseClass);
		$this->cObj = $this->pibase->cObj;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
		$this->useArticles = $useArticles;

		$this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view'); // a copy of it
		$this->urlObj->setUrlArray($urlArray);
	} // init


	public function getMarkerArray ($calculatedArray)	{
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
		$markerArray = array();

			// This is the total for the goods in the basket.
		$markerArray['###PRICE_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceNoTax']['goodstotal']);
		$markerArray['###PRICE_GOODSTOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['goodstotal']-$calculatedArray['priceNoTax']['goodstotal']);

		$markerArray['###PRICE2_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($calculatedArray['price2Tax']['goodstotal']);
		$markerArray['###PRICE2_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['price2NoTax']['goodstotal']);
		$markerArray['###PRICE2_GOODSTOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($calculatedArray['price2Tax']['goodstotal']-$calculatedArray['price2NoTax']['goodstotal']);

		$markerArray['###PRICE_DISCOUNT_GOODSTOTAL_TAX###']    = $priceViewObj->priceFormat($calculatedArray['noDiscountPriceTax']['goodstotal']-$calculatedArray['priceTax']['goodstotal']);
		$markerArray['###PRICE_DISCOUNT_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['noDiscountPriceNoTax']['goodstotal']-$calculatedArray['priceNoTax']['goodstotal']);

		$taxRateArray = GeneralUtility::trimExplode(',', $this->conf['TAXrates']);

		if (isset($taxRateArray) && is_array($taxRateArray))	{
			foreach ($taxRateArray as $k => $taxrate)	{
				$taxstr = strval(number_format(floatval($taxrate), 2));
				$label = chr(ord('A')+$k);
				$markerArray['###PRICE_TAXRATE_NAME'.($k+1).'###'] = $label;
				$markerArray['###PRICE_TAXRATE_TAX'.($k+1).'###'] = $taxrate;
				if (isset($calculatedArray['priceNoTax']['sametaxtotal'][$taxstr])) {
                    $label = $calculatedArray['priceNoTax']['sametaxtotal'][$taxstr];
                    $markerArray['###PRICE_TAXRATE_TOTAL' . ($k+1) . '###'] = $priceViewObj->priceFormat($label);
                    $label = $calculatedArray['priceNoTax']['goodssametaxtotal'][$taxstr];
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL' . ($k+1) . '###'] = $priceViewObj->priceFormat($label);

                    $label = $priceViewObj->priceFormat($calculatedArray['priceNoTax']['sametaxtotal'][$taxstr] * ($taxrate / 100));
                    $markerArray['###PRICE_TAXRATE_ONLY_TAX' . ($k+1) . '###'] = $label;
                    $label = $priceViewObj->priceFormat($calculatedArray['priceNoTax']['goodssametaxtotal'][$taxstr] * ($taxrate / 100));
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL_ONLY_TAX' . ($k+1) . '###'] = $label;
                } else {
                    $zeroPrice = $priceViewObj->priceFormat(0);
                    $markerArray['###PRICE_TAXRATE_TOTAL' . ($k + 1) . '###'] = $zeroPrice;
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL' . ($k + 1) . '###'] = $zeroPrice;
                    $markerArray['###PRICE_TAXRATE_ONLY_TAX' . ($k + 1) . '###'] = $zeroPrice;
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL_ONLY_TAX' . ($k + 1) . '###'] = $zeroPrice;
                }
			}
		}

		// This is for the Basketoverview
		$markerArray['###NUMBER_GOODSTOTAL###'] = $calculatedArray['count'];
        $fileresource =  \JambageCom\Div2007\Utility\FrontendUtility::fileResource($this->conf['basketPic']);
		$markerArray['###IMAGE_BASKET###'] = $fileresource;

		return $markerArray;
	}


	/**
	 * This generates the shopping basket layout and also calculates the totals. Very important function.
	 */
	public function getView (
        &$errorCode,
		$templateCode,
		$theCode,
		$infoViewObj,
		$bSelectSalutation,
		$bSelectVariants,
		$calculatedArray,
		$bHtml = true,
		$subpartMarker = 'BASKET_TEMPLATE',
		$mainMarkerArray = array(),
		$templateFilename = '',
		$itemArray = array(),
		$orderArray = array(),
		$basketExtra = array()
	)	{
			/*
				Very central function in the library.
				By default it extracts the subpart, ###BASKET_TEMPLATE###, from the $templateCode 
				and substitutes a lot of fields and subparts.
				Any pre-preparred fields can be set in $mainMarkerArray, which is substituted in the subpart before the item-and-categories part is substituted.
			*/
		$out = '';
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);

		$articleViewTagArray = array();

		if (empty($itemArray))	{
			$itemArray = $basketObj->getItemArray();
		}
		if (empty($orderArray))	{
			$orderArray = $basketObj->order;
		}

		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$billdeliveryObj = GeneralUtility::makeInstance('tx_ttproducts_billdelivery');
		$viewControlConf = $cnf->getViewControlConf($theCode);
		$parser = $this->cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		if (!empty($viewControlConf))	{
			if (isset($viewControlConf['param.']) && is_array($viewControlConf['param.']))	{
				$viewParamConf = $viewControlConf['param.'];
			}
		}
		$bUseBackPid = (isset($viewControlConf['param.']) && $viewControlConf['param.']['use'] == 'backPID' ? true : false);

		$funcTablename = $basketObj->getFuncTablename();
		$itemTableView = $tablesObj->get($funcTablename, true);
		$itemTable = $itemTableView->getModelObj();
		$tableConf = &$itemTable->getTableConf ($theCode);
		$itemTable->initCodeConf($theCode,$tableConf);
		$minQuantityArray = array();

		$articleViewObj = $tablesObj->get('tt_products_articles', true);
		$articleTable = $articleViewObj->getModelObj();

		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
		$this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view'); // a copy of it


		if ($templateCode == '') {
            $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
            $errorCode = [];
            $errorCode[0] = 'empty_template';
            $errorCode[1] = ($templateFilename ? $templateFilename : $templateObj->getTemplateFile());
            return '';
		}

			// Getting subparts from the template code.
		$t = array();
		$feuserSubpartArray = array();
		$feuserWrappedSubpartArray = array();
		$tempContent = tx_div2007_core::getSubpart($templateCode, $subpartmarkerObj->spMarker('###' . $subpartMarker . $this->config['templateSuffix'] . '###'));

		$viewTagArray = $markerObj->getAllMarkers($tempContent);
		$feUsersViewObj = $tablesObj->get('fe_users', true);
		$feUsersViewObj->getWrappedSubpartArray(
			$viewTagArray,
			$bUseBackPid,
			$feuserSubpartArray,
			$feuserWrappedSubpartArray
		);

		if (!$tempContent)	{
			$tempContent =
                tx_div2007_core::getSubpart(
                    $templateCode,
                    $subpartmarkerObj->spMarker('###' . $subpartMarker . '###')
                );
        }

		$markerArray = array();
		if (isset($mainMarkerArray) && is_array($mainMarkerArray))	{
			$markerArray = array_merge($markerArray, $mainMarkerArray);
		}
			// add Global Marker Array
		$globalMarkerArray = &$markerObj->getGlobalMarkerArray();
		$markerArray = array_merge($markerArray, $globalMarkerArray);

		$t['basketFrameWork'] =
			tx_div2007_core::substituteMarkerArrayCached(
				$tempContent,
				$markerArray,
				$feuserSubpartArray,
				$feuserWrappedSubpartArray
			);

		$subpartEmptyArray = array('EMAIL_PLAINTEXT_TEMPLATE_SHOP', 'BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE');
		if (!$t['basketFrameWork'] && !in_array($subpartMarker, $subpartEmptyArray)) {
			$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
			$errorCode = [];
			$errorCode[0] = 'no_subtemplate';
			$errorCode[1] = '###'.$subpartMarker.$templateObj->getTemplateSuffix().'###';
			$errorCode[2] = ($templateFilename ? $templateFilename : $templateObj->getTemplateFile());
			return '';
		}

		if ($t['basketFrameWork'])	{
			$checkExpression = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['templateCheck'];
			if (!empty($checkExpression)) {
				$wrongPounds = preg_match_all($checkExpression, $t['basketFrameWork'], $matches);
				if ($wrongPounds) {
                    $errorCode = [];
					$errorCode[0] = 'template_invalid_marker_border';
					$errorCode[1] = '###' . $subpartMarker . '###';
					$errorCode[2] = htmlspecialchars(implode('|', $matches['0']));
					return '';
				}
			}

			if (!$bHtml)	{
				$t['basketFrameWork'] = html_entity_decode($t['basketFrameWork'], ENT_QUOTES, 'UTF-8');
			}

				// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
			if (trim(tx_div2007_core::getSubpart($t['basketFrameWork'], '###BILLING_ADDRESS_LOGIN###')))	{
                if (
                    \JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn()
                    && 
                    $this->conf['lockLoginUserInfo']
                ) {
					$t['basketFrameWork'] = $parser->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS###', '');
				} else {
					$t['basketFrameWork'] = $parser->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS_LOGIN###', '');
				}
			}

			$t['categoryFrameWork'] = tx_div2007_core::getSubpart($t['basketFrameWork'],  '###ITEM_CATEGORY###');
			$t['itemFrameWork'] = tx_div2007_core::getSubpart($t['basketFrameWork'], '###ITEM_LIST###');
			$t['item'] = tx_div2007_core::getSubpart($t['itemFrameWork'], '###ITEM_SINGLE###');

			$currentP='';
			$itemsOut='';
			$viewTagArray = array();
			$markerFieldArray = array('BULKILY_WARNING' => 'bulkily',
				'PRODUCT_SPECIAL_PREP' => 'special_preparation',
				'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
				'PRODUCT_LINK_DATASHEET' => 'datasheet');
			$parentArray = array();
			$fieldsArray = $markerObj->getMarkerFields(
				$t['item'],
				$itemTable->getTableObj()->tableFieldArray,
				$itemTable->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$itemTable->marker,
				$viewTagArray,
				$parentArray
			);
			$count = 0;
			$basketItemView = '';
			$bCopyProduct2Article = false;

			if ($this->useArticles == 0)	{
				if (strpos($t['item'], $articleViewObj->getMarker()) !== false)	{
					$bCopyProduct2Article = true;
				}
			}

			$checkMinPrice = false;

			if ($this->useArticles == 1 || $this->useArticles == 3) {
				$markerFieldArray = array();
				$articleParentArray = array();
				$articleFieldsArray = $markerObj->getMarkerFields(
					$t['item'],
					$itemTable->getTableObj()->tableFieldArray,
					$itemTable->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$articleTable->marker,
					$articleViewTagArray,
					$articleParentArray
				);
				$prodUidField = $cnf->getTableDesc($articleTable->getTableObj()->name, 'uid_product');
				$fieldsArray = array_merge($fieldsArray, $articleFieldsArray);
				$uidKey = array_search($prodUidField, $fieldsArray);
				if ($uidKey != '')	{
					unset($fieldsArray[$uidKey]);
				}
			}

			$hiddenFields = '';

			// loop over all items in the basket indexed by sorting text
			foreach ($itemArray as $sort=>$actItemArray) {

				foreach ($actItemArray as $k1=>$actItem) {

					$row = $actItem['rec'];
					if (!$row)	{	// avoid bug with missing row
						continue;
					}

					$extArray = $row['ext'];
					$pid = intval($row['pid']);
					if (!$basketObj->getPidListObj()->getPageArray($pid))	{
						// product belongs to another basket
						continue;
					}
					$quantity = $basketObj->getItemObj()->getQuantity($actItem);
					$minQuantity = $basketObj->getItemObj()->getMinQuantity($actItem);

					if ($minQuantity != '0.00' && $quantity < $minQuantity) {
						$minQuantityArray[] = array('rec' => $row, 'minQuantity' => $minQuantity, 'quantity' => $quantity);
					}
					$count++;
					$actItem['rec'] = $row;	// fix bug with PHP 5.2.1

					$bIsNoMinPrice = $itemTable->hasAdditional($row, 'noMinPrice');
					if (!$bIsNoMinPrice)	{
						$checkMinPrice = true;
					}
					$pidcategory = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] == 1 ? $pid : '');
					$currentPnew = $pidcategory . '_' . $actItem['rec']['category'];

						// Print Category Title
					if ($currentPnew != $currentP)	{
						if ($itemsOut)	{
							$out .= $parser->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
						}
						$itemsOut='';		// Clear the item-code var
						$currentP = $currentPnew;

						if ($this->conf['displayBasketCatHeader'])	{
							$markerArray=$globalMarkerArray;
							$pageCatTitle = '';
							if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] == 1) {
								$page = $tablesObj->get('pages');
								$pageTmp = $page->get($pid);
								$pageCatTitle = $pageTmp['title'].'/';
							}
							$catTmp = '';
							if ($actItem['rec']['category']) {
								$catTmp = $tablesObj->get('tt_products_cat')->get($actItem['rec']['category']);
								$catTmp = $catTmp['title'];
							}
							$catTitle = $pageCatTitle.$catTmp;
							$this->cObj->setCurrentVal($catTitle);
							$markerArray['###CATEGORY_TITLE###']=$this->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');

							$categoryQuantity = $basketObj->getCategoryQuantity();

								// compatible with bill/delivery
							$currentCategory = $row['category'];
							$markerArray['###CATEGORY_QTY###'] = $categoryQuantity[$currentCategory];

 							$categoryPriceTax = $calculatedArray['categoryPriceTax']['goodstotal'][$currentCategory];
 							$markerArray['###PRICE_GOODS_TAX###'] = $priceViewObj->priceFormat($categoryPriceTax);
 							$categoryPriceNoTax = $calculatedArray['categoryPriceNoTax']['goodstotal'][$currentCategory];
 							$markerArray['###PRICE_GOODS_NO_TAX###'] = $priceViewObj->priceFormat($categoryPriceNoTax);
 							$markerArray['###PRICE_GOODS_ONLY_TAX###'] = $priceViewObj->priceFormat($categoryPriceTax - $categoryPriceNoTax);

							$out .= $parser->substituteMarkerArray($t['categoryFrameWork'], $markerArray);
						}
					}
						// Fill marker arrays
					$wrappedSubpartArray = array();
					$subpartArray = array();
					$markerArray = $globalMarkerArray;

					if (!is_object($basketItemView))	{
						$basketItemView = GeneralUtility::makeInstance('tx_ttproducts_basketitem_view');
						$basketItemView->init($this->pibaseClass,$basketObj->basketExt,$basketObj->getItemObj());
					}

					// $extRow = array('extTable' => $row['extTable'], 'extUid' => $row['extUid']);
					$basketItemView->getItemMarkerArray(
						$funcTablename,
						$actItem,
						$markerArray,
						$viewTagArray,
						$hiddenFields,
						$theCode,
						$count,
						false,
						'UTF-8'
					);

					$catRow = $row['category'] ? $tablesObj->get('tt_products_cat')->get($row['category']) : array();
					// $catTitle= $actItem['rec']['category'] ? $this->tt_products_cat->get($actItem['rec']['category']) : '';
					$catTitle = $catRow['title'];
					$tmp = array();

						// use the product if no article row has been found
					$prodVariantRow = $row;

					if (isset($actItem['calc']))	{
						$prodVariantRow['calc'] = $actItem['calc'];
					}

					$prodMarkerRow = $prodVariantRow;
					$itemTable->tableObj->substituteMarkerArray($prodMarkerRow);
					$itemTableView->getModelMarkerArray(
						$prodMarkerRow,
						'',
						$markerArray,
						$catTitle,
						$this->config['limitImage'],
						'basketImage',
						$viewTagArray,
						$tmp,
						$theCode,
						$basketExtra,
						$count,
						'',
						'',
						'',
						$bHtml,
						'UTF-8'
					);

					if ($this->useArticles == 1 || $this->useArticles == 3 || $bCopyProduct2Article) {

						$articleRows = array();

						if (!$bCopyProduct2Article)	{
							// get the article uid with these colors, sizes and gradings
							if (is_array($extArray) && is_array($extArray[$articleTable->getFuncTablename()]))	{
								$articleExtArray = $extArray[$articleTable->getFuncTablename()];
								foreach($articleExtArray as $k => $articleData) {
									$articleRows[$k] = $articleTable->get($articleData['uid']);
								}
							} else {
								$articleRow = $itemTable->getArticleRow($row, $theCode);
								if ($articleRow) {
									$articleRows['0'] = $articleRow;
								}
							}
						}

						if (is_array($articleRows) && count($articleRows)) {
							$bKeepNotEmpty = $this->conf['keepProductData']; // auskommentieren nicht möglich wenn mehrere Artikel dem Produkt zugewiesen werden
							if ($this->useArticles == 3)	{
								$itemTable->fillVariantsFromArticles($prodVariantRow);
								$itemTable->variant->modifyRowFromVariant($prodVariantRow);
							}
							foreach ($articleRows as $articleRow) {
								$itemTable->mergeAttributeFields($prodVariantRow, $articleRow, $bKeepNotEmpty, true);
							}
						} else {
							$variant = $itemTable->variant->getVariantFromRow($row);
							$itemTable->variant->modifyRowFromVariant($prodVariantRow, $variant);
						}
						// use the fields of the article instead of the product
						//

						$prodMarkerRow = $prodVariantRow;
						$itemTable->tableObj->substituteMarkerArray($prodMarkerRow);
						$articleViewObj->getModelMarkerArray(
							$prodMarkerRow,
							'',
							$markerArray,
							$catTitle,
							$this->config['limitImage'],
							'basketImage',
							$articleViewTagArray,
							$tmp=array(),
							$theCode,
							$basketExtra,
							$count,
							'',
							'',
							'',
							$bHtml,
							'UTF-8'
						);
						$articleViewObj->getItemMarkerSubpartArrays(
							$t['item'],
							$articleViewObj->getModelObj()->getFuncTablename(),
							$prodVariantRow,
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray,
							$articleViewTagArray,
							$theCode,
							$basketExtra
						);
					}

					$itemTableView->getItemMarkerSubpartArrays(
						$t['item'],
						$itemTableView->getModelObj()->getFuncTablename(),
						$prodVariantRow,
						$markerArray,
						$subpartArray,
						$wrappedSubpartArray,
						$viewTagArray,
						$theCode,
						$basketExtra,
						$count
					);

					$this->cObj->setCurrentVal($catTitle);
					$markerArray['###CATEGORY_TITLE###'] =
						$this->cObj->cObjGetSingle(
							$this->conf['categoryHeader'],
							$this->conf['categoryHeader.'],
							'categoryHeader'
						);
					$markerArray['###PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($actItem['totalTax']);
					$markerArray['###PRICE_TOTAL_NO_TAX###'] = $priceViewObj->priceFormat($actItem['totalNoTax']);
					$markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($actItem['totalTax'] - $actItem['totalNoTax']);

					if ($row['category'] == $this->conf['creditsCategory']) {
						// creditpoint system start
						$pricecredits_total_totunits_no_tax = $actItem['totalNoTax']*$row['unit_factor'];
						$pricecredits_total_totunits_tax = $actItem['totalTax']*$row['unit_factor'];
					} else if (doubleval($row['price']) && doubleval($row['price2'])) {
						$pricecredits_total_totunits_no_tax = 0;
						$pricecredits_total_totunits_tax = 0;
					}
					$markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###'] = $priceViewObj->priceFormat($pricecredits_total_totunits_no_tax);
					$markerArray['###PRICE_TOTAL_TOTUNITS_TAX###'] = $priceViewObj->priceFormat($pricecredits_total_totunits_tax);
					$sum_pricecredits_total_totunits_no_tax += $pricecredits_total_totunits_no_tax;
					$sum_price_total_totunits_no_tax += $pricecredits_total_totunits_no_tax;
					$sum_pricecreditpoints_total_totunits += $pricecredits_total_totunits_no_tax;

					// creditpoint system end
					$page = $tablesObj->get('pages');
					$pid = $page->getPID(
						$this->conf['PIDitemDisplay'],
						$this->conf['PIDitemDisplay.'],
						$row,
						$GLOBALS['TSFE']->rootLine[1]
					);
					$addQueryString=array();
					$addQueryString[$itemTable->type] = intval($row['uid']);

					if (is_array($extArray) && is_array($extArray[$basketObj->getFuncTablename()]))	{
						$addQueryString['variants'] = htmlspecialchars($extArray[$basketObj->getFuncTablename()][0]['vars']);
					}

					$tempUrl = htmlspecialchars(
						$this->pibase->pi_getPageLink(
							$pid,
							'',
							$this->urlObj->getLinkParams(
								'',
								$addQueryString,
								true,
								$bUseBackPid,
								''
							),
							array('useCacheHash' => true)
						)
					);
					$wrappedSubpartArray['###LINK_ITEM###'] = array('<a href="'.$tempUrl .'"'.$css_current.'>','</a>');
					if (is_object($itemTableView->variant))	{
						$itemTableView->variant->removeEmptyMarkerSubpartArray(
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray,
							$prodVariantRow,
							$this->conf,
							$itemTable->hasAdditional($row,'isSingle'),
							!$itemTable->hasAdditional($row,'noGiftService')
						);
					}

					// Substitute
					$feUsersViewObj->getModelObj()->setCondition($row, $funcTablename);
					$feUsersViewObj->getWrappedSubpartArray(
						$viewTagArray,
						$bUseBackPid,
						$subpartArray,
						$wrappedSubpartArray
					);

					$tempContent = tx_div2007_core::substituteMarkerArrayCached(
						$t['item'],
						array(),
						$subpartArray,
						$wrappedSubpartArray
					);
					$tempContent = $parser->substituteMarkerArray(
						$tempContent,
						$markerArray
					);

#### Core: Error handler (FE): PHP Warning: preg_split(): Compilation failed: regular expression is too large at offset 33260 in  /typo3_src-6.2.11/typo3/sysext/frontend/Classes/ContentObject/ContentObjectRenderer.php line 1880
					$itemsOut .= $tempContent;
				}

				if ($itemsOut)	{
					$tempContent = $parser->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
					$out .= $tempContent;
					$itemsOut = '';	// Clear the item-code var
				}
			}

			$subpartArray = array();
			$wrappedSubpartArray = array();
			$basketMarkerArray = $this->getMarkerArray($calculatedArray);

				// Initializing the markerArray for the rest of the template
			$markerArray = $basketMarkerArray;

			$activityArray = tx_ttproducts_model_activity::getActivityArray();

			if (is_array($activityArray))	{
				$activity = '';
				if ($activityArray['products_payment'])	{
					$activity = 'payment';
				} else if ($activityArray['products_info']) {
					$activity = 'info';
				}
				if ($activity)	{
					$bUseXHTML = $GLOBALS['TSFE']->config['config']['xhtmlDoctype'] != '';
					$hiddenFields .= '<input type="hidden" name="' . TT_PRODUCTS_EXT . '[activity]['. $activity . ']" value="1" ' . ($bUseXHTML ? '/' : '') . '>';
				}
			}
			$markerArray['###HIDDENFIELDS###'] = $hiddenFields;
			$pid = ($this->conf['PIDbasket'] ? $this->conf['PIDbasket'] : $GLOBALS['TSFE']->id);

			$linkConf = array('useCacheHash' => false);
			$excludeList = '';

			if (isset($viewParamConf) && is_array($viewParamConf) && $viewParamConf['ignore'])	{
				$excludeList = $viewParamConf['ignore'];
			}

			$url = tx_div2007_alpha5::getTypoLink_URL_fh003(
				$this->pibase->cObj,
				$pid,
				$this->urlObj->getLinkParams(
					$excludeList,
					array(),
					true,
					$bUseBackPid,
					''
				),
				$target = '',
				$linkConf
			);
			$htmlUrl = htmlspecialchars(
					$url,
					ENT_NOQUOTES,
					'UTF-8'
				);
			$wrappedSubpartArray['###LINK_BASKET###'] = array('<a href="'. $htmlUrl .'">', '</a>');
			$paymentshippingObj->getMarkerArray($theCode, $markerArray, $pid, $bUseBackPid, $calculatedArray, $basketExtra);

			// for receipt from DIBS script
			$markerArray['###TRANSACT_CODE###'] = GeneralUtility::_GP('transact');
            $markerArray['###CUR_SYM###'] = ' ' . ($bHtml ? htmlentities($this->conf['currencySymbol'], ENT_QUOTES) : $this->conf['currencySymbol']);

			$markerArray['###PRICE_TAX_DISCOUNT###'] = $markerArray['###PRICE_DISCOUNT_TAX###'] = $priceViewObj->priceFormat($calculatedArray['price0Tax']['goodstotal']-$calculatedArray['priceTax']['goodstotal']);
			$markerArray['###PRICE_VAT###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['goodstotal']-$calculatedArray['priceNoTax']['goodstotal']);

			$orderViewObj = $tablesObj->get('sys_products_orders', true);
			$orderViewObj->getBasketRecsMarkerArray($markerArray, $orderArray);
			$billdeliveryObj->getMarkerArray($markerArray, $orderArray['orderTrackingNo'], 'bill');
			$billdeliveryObj->getMarkerArray($markerArray, $orderArray['orderTrackingNo'], 'delivery');

				// URL
			$bUseBackPid = true;
			$bUseBackPid = (isset($viewParamConf) && $viewParamConf['use'] == 'backPID' ? true : false);
			$markerArray = $this->urlObj->addURLMarkers(
				0,
				$markerArray,
				array(),
				'',
				$bUseBackPid,
				0
			); // Applied it here also...

			$taxFromShipping = $paymentshippingObj->getReplaceTaxPercentage($basketExtra);
			$taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && $taxFromShipping == 0 ? 'tax_zero' : 'tax_included');
			$markerArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? $languageObj->getLabel($taxInclExcl) : '');

			if ($subpartMarker != 'BASKET_OVERVIEW_TEMPLATE') {

	// Added Franz: GIFT CERTIFICATE
				$markerArray['###GIFT_CERTIFICATE_UNIQUE_NUMBER_NAME###']='recs[tt_products][giftcode]'; // deprecated
				$markerArray['###FORM_NAME###']='BasketForm';
				$markerArray['###FORM_NAME_GIFT_CERTIFICATE###']='BasketGiftForm';

	/* Added els5: markerarrays for gift certificates */
	/* Added Els6: routine for redeeming the gift certificate (other way then proposed by Franz */
				$markerArray['###INSERT_GIFTCODE###'] = 'recs[tt_products][giftcode]';
				$markerArray['###VALUE_GIFTCODE###'] = htmlspecialchars($basketObj->recs['tt_products']['giftcode']);
				$cpArray = $GLOBALS['TSFE']->fe_user->getKey('ses','cp');
				$creditpointsGifts = '';
				if (
					isset($cpArray['gift']) &&
					is_array($cpArray['gift']) &&
					isset($cpArray['gift']['amount'])
				) {
					$creditpointsGifts = $cpArray['gift']['amount'];
				}
				$markerArray['###CREDITPOINTS_GIFTS###'] = htmlspecialchars($creditpointsGifts);

				if ($basketObj->recs['tt_products']['giftcode'] == '') {
					$subpartArray['###SUB_GIFTCODE_DISCOUNT###'] = '';
					$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###'] = '';
					if ($creditpointsGifts == '') {
						$subpartArray['###SUB_GIFTCODE_DISCOUNT_true###'] = '';
					}
				} else {
					$uniqueId = GeneralUtility::trimExplode ('-', $basketObj->recs['tt_products']['giftcode'], true);
					$query='uid=\'' . intval($uniqueId[0]) . '\' AND crdate=\'' . intval($uniqueId[1]) . '\'';
					$giftRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products_gifts', $query);
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($giftRes);
					$GLOBALS['TYPO3_DB']->sql_free_result($giftRes);
					$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);
					$creditpointsDiscount = intval($creditpointsGifts) * $pricefactor;
					$markerArray['###GIFT_DISCOUNT###'] = $creditpointsDiscount;
					$markerArray['###VALUE_GIFTCODE_USED###'] = htmlspecialchars($basketObj->recs['tt_products']['giftcode']);

					if ($row && $creditpointsGifts && $pricefactor > 0) {
						$subpartArray['###SUB_GIFTCODE_DISCOUNTWRONG###']= '';
						if ($creditpointsGifts == '') {
							$subpartArray['###SUB_GIFTCODE_DISCOUNT_true###'] = '';
						}
					} else {
						$markerArray['###VALUE_GIFTCODE_USED###'] = '**********';
						if (GeneralUtility::_GP('creditpoints_gifts') == '') {
							$subpartArray['###SUB_GIFTCODE_DISCOUNT_true###'] = '';
						}
					}
				}
			}
			$amountCreditpoints = $GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'] + intval($creditpointsGifts);
			$markerArray['###AMOUNT_CREDITPOINTS###'] = $amountCreditpoints;

			$pricefactor = doubleval($this->conf['creditpoints.']['priceprod']);
 			$autoCreditpointsTotal = $creditpointsObj->getBasketTotal();
			$creditpoints = $autoCreditpointsTotal + $sum_pricecreditpoints_total_totunits * tx_ttproducts_creditpoints_div::getCreditPoints($sum_pricecreditpoints_total_totunits, $this->conf['creditpoints.']);
 			$markerArray['###AUTOCREDITPOINTS_TOTAL###'] = number_format($autoCreditpointsTotal,'0');
 			$markerArray['###AUTOCREDITPOINTS_PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($autoCreditpointsTotal * $pricefactor);
			$remainingCreditpoints = 0;
			$creditpointsObj->getBasketMissingCreditpoints(0, $tmp, $remainingCreditpoints);
 			$markerArray['###AUTOCREDITPOINTS_REMAINING###'] = number_format($remainingCreditpoints,'0');
			$markerArray['###CREDITPOINTS_AVAILABLE###'] = number_format($GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'],'0');
 			$markerArray['###USERCREDITPOINTS_PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat(($autoCreditpointsTotal < $amountCreditpoints ? $autoCreditpointsTotal : $amountCreditpoints) * $pricefactor);

			// maximum1 amount of creditpoint to change is amount on account minus amount already spended in the credit-shop
			$max1_creditpoints = $GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'] + intval($creditpointsGifts);
			// maximum2 amount of creditpoint to change is amount bought multiplied with creditpointfactor
			$max2_creditpoints = 0;
			$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);
			if ($pricefactor > 0) {
				$max2_creditpoints = intval (($calculatedArray['priceTax']['total'] - $calculatedArray['priceTax']['vouchertotal']) / $pricefactor );
			}
			// real maximum amount of creditpoint to change is minimum of both maximums
			$markerArray['###AMOUNT_CREDITPOINTS_MAX###'] = number_format(min($max1_creditpoints,$max2_creditpoints),0);

			// if quantity is 0 than
			if ($amountCreditpoints == '0') {
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
				$wrappedSubpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
                $subpartArray['###SUB_CREDITPOINTS_AMOUNT_EMPTY###'] = '';
				$subpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
			} else {
				$wrappedSubpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
				$wrappedSubpartArray['###SUB_CREDITPOINTS_AMOUNT_EMPTY###'] = '';
				$wrappedSubpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
			}
			$markerArray['###CHANGE_AMOUNT_CREDITPOINTS###'] = 'recs[tt_products][creditpoints]';
			if ($basketObj->recs['tt_products']['creditpoints'] == '') {
				$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = 0;
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
	/* Added Els8: put credit_discount 0 for plain text email */
				$markerArray['###CREDIT_DISCOUNT###'] = '0.00';
			} else {
				// quantity chosen can not be larger than the maximum amount, above calculated
				if ($basketObj->recs['tt_products']['creditpoints'] > min ($max1_creditpoints,$max2_creditpoints))	{
					$basketObj->recs['tt_products']['creditpoints'] = min ($max1_creditpoints,$max2_creditpoints);
				}
				$markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = number_format($basketObj->recs['tt_products']['creditpoints'], 0);
				$subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
				$markerArray['###CREDIT_DISCOUNT###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['creditpoints']);
			}

	/* Added els5: CREDITPOINTS_SPENDED: creditpoint needed, check if user has this amount of creditpoints on his account (winkelwagen.tmpl), only if user has logged in */
			$markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
			if ($sum_pricecredits_total_totunits_no_tax <= $amountCreditpoints) {
				$subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
				$markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
				// new saldo: creditpoints
				$markerArray['###AMOUNT_CREDITPOINTS###'] = $amountCreditpoints - $markerArray['###CREDITPOINTS_SPENDED###'];
			} else {
				if (!$markerArray['###FE_USER_UID###']) {
					$subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
				} else {
					$markerArray['###CREDITPOINTS_SPENDED_ERROR###'] = 'Wijzig de artikelen in de kurkenshop: onvoldoende kurken op uw saldo ('.$amountCreditpoints.').'; // TODO
					$markerArray['###CREDITPOINTS_SPENDED###'] = '&nbsp;';
				}
			}

			// check the basket limits
			$basketConf = $cnf->getBasketConf('minPrice');
			$minPriceSuccess = true;

			if ($checkMinPrice && $basketConf['type'] == 'price')	{
				$value = $calculatedArray['priceTax'][$basketConf['collect']];

				if (isset($value) && isset($basketConf['collect']) && $value < doubleval($basketConf['value']))	{
					$subpartArray['###MESSAGE_MINPRICE###'] = '';
					$tmpSubpart = tx_div2007_core::getSubpart($t['basketFrameWork'],  '###MESSAGE_MINPRICE_ERROR###');
					$subpartArray['###MESSAGE_MINPRICE_ERROR###'] = $parser->substituteMarkerArray($tmpSubpart, $markerArray);
					$minPriceSuccess = false;
				}
			}

			if ($minPriceSuccess)	{
				$subpartArray['###MESSAGE_MINPRICE_ERROR###'] = '';
				$tmpSubpart = tx_div2007_core::getSubpart($t['basketFrameWork'], '###MESSAGE_MINPRICE###');
				$subpartArray['###MESSAGE_MINPRICE###'] = $parser->substituteMarkerArray($tmpSubpart, $markerArray);
			}

			if (is_array($minQuantityArray) && count($minQuantityArray))	{
				$subpartArray['###MESSAGE_MINQUANTITY###'] = '';
				$tmpSubpart = tx_div2007_core::getSubpart($t['basketFrameWork'], '###MESSAGE_MINQUANTITY_ERROR###');
				$errorObj = GeneralUtility::makeInstance('tx_ttproducts_model_error');
				$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
				$quantityErrorCode = [];
				$quantityErrorCode[0] = 'error_minquantity';
				$quantityErrorCode[1] = '';

				foreach ($minQuantityArray as $minQuantityRow)	{
					$quantityErrorCode[1] .= $minQuantityRow['rec']['title'] . ':' . $minQuantityRow['quantity'] . '&lt;' . $minQuantityRow['minQuantity'];
				}
				$errorOut = $errorObj->getMessage($quantityErrorCode, $languageObj);
				$markerArray['###ERROR_MINQUANTITY###'] = $errorOut;
				$subpartArray['###MESSAGE_MINQUANTITY_ERROR###'] = $parser->substituteMarkerArray($tmpSubpart, $markerArray);
			} else {
				$subpartArray['###MESSAGE_MINQUANTITY_ERROR###'] = '';
				$tmpSubpart = tx_div2007_core::getSubpart($t['basketFrameWork'],  '###MESSAGE_MINQUANTITY###');
				$subpartArray['###MESSAGE_MINQUANTITY###'] = $parser->substituteMarkerArray($tmpSubpart, $markerArray);
			}

			if (is_array($minQuantityArray) && count($minQuantityArray) || !$minPriceSuccess)	{
				$subpartArray['###MESSAGE_NO_ERROR###'] = '';
			} else {
				$subpartArray['###MESSAGE_ERROR###'] = '';
			}
			$voucherView = $tablesObj->get('voucher', true);
			$voucherView->getsubpartMarkerArray($subpartArray, $wrappedSubpartArray);
			$voucherView->getMarkerArray($markerArray);
			$markerArray['###CREDITPOINTS_SAVED###'] = number_format($creditpoints,'0');
			$agb_url = array();
			$pidagb = intval($this->conf['PIDagb']);
			$addQueryString = array();

			$pointerExcludeArray = array_keys(tx_ttproducts_model_control::getPointerParamsCodeArray());
			$singleExcludeList = $this->urlObj->getSingleExcludeList(implode(',', $pointerExcludeArray));

			if ($GLOBALS['TSFE']->type)	{
				$addQueryString['type'] = $GLOBALS['TSFE']->type;
			}
			$wrappedSubpartArray['###LINK_AGB###'] = array(
				'<a href="' . htmlspecialchars(
					$this->pibase->pi_getPageLink(
						$pidagb,
						'',
						$this->urlObj->getLinkParams(
							$singleExcludeList,
							$addQueryString,
							true,
							$bUseBackPid,
							''
						)
					)
				) . '" target="' . $this->conf['AGBtarget'] . '">',
				'</a>'
			);

            $pidPrivacy = intval($this->conf['PIDprivacy']);
            $tempUrl =
                tx_div2007_alpha5::getPageLink_fh003(
                    $this->cObj,
                    $pidPrivacy,
                    '',
                    $this->urlObj->getLinkParams(
                        $singleExcludeList,
                        $addQueryString,
                        true,
                        $bUseBackPid,
                        0,
                        ''
                    )
                );
            $wrappedSubpartArray['###LINK_PRIVACY###'] = array(
                '<a href="' . htmlspecialchars($tempUrl) . '" target="' . $this->conf['AGBtarget'] . '">',
                '</a>'
            );

			$pidRevocation = intval($this->conf['PIDrevocation']);
			$wrappedSubpartArray['###LINK_REVOCATION###'] = array(
				'<a href="' . htmlspecialchars(
					$this->pibase->pi_getPageLink(
						$pidRevocation,
						'',
						$this->urlObj->getLinkParams(
							$singleExcludeList,
							$addQueryString,
							true,
							$bUseBackPid,
							''
						)
					)
				) . '" target="' . $this->conf['AGBtarget'] . '">',
				'</a>'
			);

				// Final substitution:
			if (!\JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn()) {		// Remove section for FE_USERs only, if there are no fe_user
				$subpartArray['###FE_USER_SECTION###'] = '';
			}
			if (is_object($infoViewObj))	{
				$infoViewObj->getRowMarkerArray($basketExtra, $markerArray, $bHtml, $bSelectSalutation);
			}

			$fieldsTempArray = $markerObj->getMarkerFields(
				$t['basketFrameWork'],
				$itemTable->getTableObj()->tableFieldArray,
				$itemTable->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$itemTable->marker,
				$viewTagArray,
				$parentArray
			);

			$priceCalcMarkerArray = array(
				'PRICE_TOTAL_TAX' => $calculatedArray['priceTax']['total'],
				'PRICE_TOTAL_NO_TAX' => $calculatedArray['priceNoTax']['total'],
				'PRICE_TOTAL_0_TAX' => $calculatedArray['price0Tax']['total'],
				'PRICE_TOTAL_ONLY_TAX' => $calculatedArray['priceTax']['total'] - $calculatedArray['priceNoTax']['total'],
				'PRICE_VOUCHERTOTAL_TAX' => $calculatedArray['priceTax']['vouchertotal'],
				'PRICE_VOUCHERTOTAL_NO_TAX' => $calculatedArray['priceNoTax']['vouchertotal'],
				'PRICE_VOUCHERGOODSTOTAL_TAX' => $calculatedArray['priceTax']['vouchergoodstotal'],
				'PRICE_VOUCHERGOODSTOTAL_NO_TAX' => $calculatedArray['priceNoTax']['vouchergoodstotal'],
				'PRICE_TOTAL_TAX_WITHOUT_PAYMENT' => $calculatedArray['priceTax']['total'] - $calculatedArray['priceTax']['payment'],
				'PRICE_TOTAL_NO_TAX_WITHOUT_PAYMENT' => $calculatedArray['priceNoTax']['total'] - $calculatedArray['priceNoTax']['payment'],
				'PRICE_TOTAL_TAX_CENT' => intval(round(100 * $calculatedArray['priceTax']['total'])),
				'PRICE_VOUCHERTOTAL_TAX_CENT' => intval(round(100 * $calculatedArray['priceTax']['vouchertotal']))
			);

			foreach ($priceCalcMarkerArray as $markerKey => $value)	{
				$markerArray['###'.$markerKey.'###'] = (is_int($value) ? $value : $priceViewObj->priceFormat($value));
			}

			$variantFieldArray = array();
			$variantMarkerArray = array();

				// Call all getBasketView hooks at the end of this method
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView'])) {
				foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView'] as $classRef) {
					$hookObj= GeneralUtility::makeInstance($classRef);
					if (method_exists($hookObj, 'getMarkerArrays')) {
						$hookObj->getMarkerArrays($this, $templateCode, $theCode, $markerArray,$subpartArray, $wrappedSubpartArray, $mainMarkerArray, $count);
					}
				}
			}

			$pidListObj = $basketObj->getPidListObj();
			$relatedListView = GeneralUtility::makeInstance('tx_ttproducts_relatedlist_view');
			$relatedListView->init($this->cObj, $pidListObj->getPidlist(), $pidListObj->getRecursive());
			$relatedMarkerArray = $relatedListView->getListMarkerArray(
				$theCode,
				$this->pibaseClass,
				$templateCode,
				$markerArray,
				$viewTagArray,
				$funcTablename,
				$basketObj->getUidArray(),
				array(),
				$this->useArticles,
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
				$GLOBALS['TSFE']->id,
				$errorCode
			);
			if ($relatedMarkerArray && is_array($relatedMarkerArray)) {
				$markerArray = array_merge($markerArray, $relatedMarkerArray);
			}

			$frameWork = $parser->substituteSubpart($t['basketFrameWork'], '###ITEM_CATEGORY_AND_ITEMS###', $out);

			$paymentshippingObj->getSubpartArrays($subpartArray, $wrappedSubpartArray, $basketExtra, $markerArray, $frameWork);
			$feUsersViewObj->getWrappedSubpartArray(
				$viewTagArray,
				$bUseBackPid,
				$subpartArray,
				$wrappedSubpartArray
			);

				// This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
			$externalCObject = tx_div2007_alpha5::getExternalCObject_fh003($this, 'externalProcessing');
			$markerArray['###EXTERNAL_COBJECT###'] = $externalCObject . '';  // adding extra preprocessing CObject

			$frameWork =
				$parser->substituteMarkerArray(
					$frameWork,
					$markerArray
				); // workaround for TYPO3 bug

				// substitute the main subpart with the rendered content.
			$out =
				tx_div2007_core::substituteMarkerArrayCached(
					$frameWork,
					array(),
					$subpartArray,
					$wrappedSubpartArray
				);
		} // if ($t['basketFrameWork'])
		return $out;
	} // getView
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basket_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_basket_view.php']);
}


