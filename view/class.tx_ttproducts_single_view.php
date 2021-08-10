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
 * product single view functions
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_single_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $conf;
	public $pibaseClass;
	public $config;
	public $uid; 	// product id
	public $type='product'; 	// 'product' or 'article'
	public $variants; 	// different attributes
	public $urlObj; // url functions
	public $javascript; // JavaScript functions
	public $javaScriptMarker; // JavaScript marker functions
	public $pid; // PID where to go
	public $useArticles;
	public $uidArray=array();
	public $pidListObj;
	public $cObj;


	public function init ($pibaseClass, array $uidArray, $extVars, $pid, $useArticles, $pid_list, $recursive) {

		$this->pibaseClass = $pibaseClass;
		$pibaseObj = GeneralUtility::makeInstance(''.$pibaseClass);
		$this->cObj = $pibaseObj->cObj;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		if (count($uidArray))	{
			$this->uidArray = $uidArray;
			reset($uidArray);
			if (isset($uidArray['product'])) {
				$this->type = 'product';
				$this->uid = $uidArray['product'];
			} else if (isset($uidArray['article']))	{
				$this->uid = $uidArray['article'];
				$this->type = 'article';
			}
		}

		$this->variants = $extVars;
		$this->pid = $pid;
		$this->useArticles = $useArticles;
		$this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
		$this->pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
		$this->pidListObj->init($this->cObj);
		$this->pidListObj->applyRecursive($recursive, $pid_list, true);
		$this->pidListObj->setPageArray();
		$this->javaScriptMarker = GeneralUtility::makeInstance('tx_ttproducts_javascript_marker');
		$this->javaScriptMarker->init($pibaseObj);
	}


	// returns the single view
	public function printView (&$templateCode, &$errorCode, $pageAsCategory, $templateSuffix = '') {

		$pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi1_base');
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$theCode = 'SINGLE';
        $parser = $this->cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		$bUseBackPid = true;
		$viewControlConf = $cnf->getViewControlConf('SINGLE');
		if (is_array($viewControlConf) && count($viewControlConf)) {
			if (isset($viewControlConf['param.']) && is_array($viewControlConf['param.'])) {
				$viewParamConf = $viewControlConf['param.'];
			}

			if (
				isset($viewControlConf['links.']) &&
				is_array($viewControlConf['links.'])
			) {
				$linkConfArray = $viewControlConf['links.'];
			}
		}

		$bUseBackPid = (isset($viewParamConf) && $viewParamConf['use'] == 'backPID' ? true : false);
		$itemTableArray = array();
		$itemTableArray['product'] = $tablesObj->get('tt_products');
		$tableConf=&$itemTableArray['product']->getTableConf('SINGLE');
		$itemTableArray['product']->initCodeConf('SINGLE',$tableConf);
		$itemTableArray['article'] = $tablesObj->get('tt_products_articles');
		$tableConf=&$itemTableArray ['article']->getTableConf('SINGLE');
		$itemTableArray['article']->initCodeConf('SINGLE',$tableConf);
		$itemTableViewArray = array();
		$itemTableViewArray['product'] = $tablesObj->get('tt_products', true);
		$itemTableViewArray['article'] = $tablesObj->get('tt_products_articles', true);

		$rowArray = array('product' => array(), 'article' => array());
		$itemTableConf = $rowArray;
		$itemTableLangFields = $rowArray;
		$content = '';
		$javaScriptObj = GeneralUtility::makeInstance('tx_ttproducts_javascript');


		if ($this->config['displayCurrentRecord'] && $this->type == 'product' && !$this->useArticles)	{
			$rowArray[$this->type] = $this->cObj->data;
		} else if ($this->uid) {
			$where = 'pid IN ('.$this->pidListObj->getPidlist().')';
			$rowArray[$this->type] = $itemTableArray[$this->type]->get($this->uid, 0, true, $where);
			$itemTableConf[$this->type] = $cnf->getTableConf($itemTableArray[$this->type]->getFuncTablename(), 'SINGLE');
			$itemTableLangFields[$this->type] = $cnf->getTranslationFields($itemTableConf[$this->type]);

			if ($this->type == 'product')	{
				if ($this->variants) {
					$itemTableArray[$this->type]->variant->modifyRowFromVariant($rowArray[$this->type], $this->variants);
				}
			} else if ($this->type == 'article') {
				$where = 'pid IN ('.$this->pidListObj->getPidlist().')';
				$rowArray['product'] = $itemTableArray['product']->get(intval($rowArray[$this->type]['uid_product']), 0, true, $where);

				$itemTableConf['product'] = $cnf->getTableConf($itemTableArray['product']->getFuncTablename(), 'SINGLE');
				$itemTableLangFields['product'] = $cnf->getTranslationFields($itemTableConf['product']);
				$itemImageFields['product'] = $cnf->getImageFields($itemTableConf['product']);
				$itemTableArray['article']->mergeAttributeFields($rowArray['product'], $rowArray['article'], false);
			}
		}
		$origRow = $rowArray[$this->type];

		foreach ($itemTableLangFields as $type => $fieldArray)	{
			if (is_array($fieldArray))	{
				foreach ($fieldArray as $field => $langfield)	{
					$rowArray[$type][$field] = $rowArray[$type][$langfield];
				}
			}
		}
		$row = $rowArray[$this->type];
		$tablename = $itemTableArray[$this->type]->getTableObj()->getName();

		if (!empty($row['uid'])) {
			// $this->uid = intval ($row['uid']); // store the uid for later usage here

			$itemTableArray['product']->getTableObj()->transformRow($row, TT_PRODUCTS_EXT);
			$useArticles = $itemTableArray['product']->variant->getUseArticles();
			if ($useArticles == 3)	{
				$itemTableArray['product']->fillVariantsFromArticles($row);
			}
				// add Global Marker Array
			$markerArray = $markerObj->getGlobalMarkerArray();
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$pageObj = $tablesObj->get('pages');

				// Get the subpart code
			$subPartMarker ='';
			$giftNumberArray = tx_ttproducts_gifts_div::getGiftNumbers ($rowArray['product']['uid'], $this->variants);

			if ($this->config['displayCurrentRecord'])	{
				$subPartMarker = 'ITEM_SINGLE_DISPLAY_RECORDINSERT';
			} else if (is_array($giftNumberArray) && count($giftNumberArray)) {
				$subPartMarker = 'ITEM_SINGLE_DISPLAY_GIFT';
			} else if (!$this->conf['alwaysInStock'] && $row['inStock'] <= 0 && $this->conf['showNotinStock'] && is_array($GLOBALS['TCA'][$itemTableArray[$this->type]->getTableObj()->name]['columns']['inStock']) ) {
				$subPartMarker = 'ITEM_SINGLE_DISPLAY_NOT_IN_STOCK';
			} else {
				if ($this->type == 'product')	{
					$subPartMarker = 'ITEM_SINGLE_DISPLAY';
				} else if ($this->type == 'article'){
					$subPartMarker = 'ARTICLE_SINGLE_DISPLAY';
				}
			}

			// get categories
			if (!$pageAsCategory || $pageAsCategory == 1)	{
				if ($this->type == 'product' || $this->type == 'article')	{
					$catTablename = 'tt_products_cat';
				}
			} else {
				$catTablename = 'pages';
			}
			$viewCatTable = $tablesObj->get($catTablename);
			$viewCatViewTable = $tablesObj->get($catTablename, true);

			// Add the template suffix
			$subPartMarker = $subPartMarker . $templateSuffix;
			$itemFrameWork = tx_div2007_core::getSubpart($templateCode, $subpartmarkerObj->spMarker('###' . $subPartMarker . '###'));

			$checkExpression = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['templateCheck'];
			if (!empty($checkExpression)) {
				$wrongPounds = preg_match_all($checkExpression, $itemFrameWork, $matches);

				if ($wrongPounds) {
					$errorCode[0] = 'template_invalid_marker_border';
					$errorCode[1] = '###' . $subPartMarker . '###';
					$errorCode[2] = htmlspecialchars(implode('|', $matches['0']));

					return '';
				}
			}

			$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray,array(),'',$bUseBackPid);

			$excludeList = '';

			if (isset($viewParamConf) && is_array($viewParamConf))	{
				if ($viewParamConf['ignore'])	{
					$excludeList = $viewParamConf['ignore'];
				}
				$categoryPivar = $viewCatViewTable->getPivar();
				if (GeneralUtility::inList($viewParamConf['item'], $categoryPivar))	{
					// nothing
				} else {
					$prefixId = tx_ttproducts_model_control::getPrefixId();
					$excludeList .= ($excludeList != '' ? ',' : '') . $prefixId . '[' . $categoryPivar . ']';
				}
			}
			$pidMemo = ($this->conf['PIDmemo'] ? $this->conf['PIDmemo'] : $GLOBALS['TSFE']->id);
			tx_ttproducts_control_memo::getWrappedSubpartArray (
				$wrappedSubpartArray,
				$pidMemo,
				$row['uid'],
				$this->cObj,
				$this->urlObj,
				$excludeList,
				array(),
				'',
				$bUseBackPid
			);

			if (!$itemFrameWork) {
				$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
				$errorCode[0] = 'no_subtemplate';
				$errorCode[1] = '###'.$subPartMarker.'###';
				$errorCode[2] = $templateObj->getTemplateFile();

				return '';
			}

			$viewTagArray = $markerObj->getAllMarkers($itemFrameWork);
			$tablesObj->get('fe_users', true)->getWrappedSubpartArray(
				$viewTagArray,
				$bUseBackPid,
				$subpartArray,
				$wrappedSubpartArray
			);

			$itemFrameWork = tx_div2007_core::substituteMarkerArrayCached(
				$itemFrameWork,
				$markerArray,
				$subpartArray,
				$wrappedSubpartArray
			);

			$markerFieldArray = array(
				'BULKILY_WARNING' => 'bulkily',
				'PRODUCT_SPECIAL_PREP' => 'special_preparation',
				'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
				'PRODUCT_LINK_DATASHEET' => 'datasheet');
			$viewTagArray = array();
			$parentArray = array();

			$fieldsArray = $markerObj->getMarkerFields(
				$itemFrameWork,
				$itemTableArray[$this->type]->getTableObj()->tableFieldArray,
				$itemTableArray[$this->type]->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$itemTableArray[$this->type]->marker,
				$viewTagArray,
				$parentArray
			);

			$articleViewTagArray = array();
			if ($this->type == 'product' && in_array($useArticles,array(1,3))) {
				$markerFieldArray = array();
				$articleParentArray = array();
				$articleFieldsArray = $markerObj->getMarkerFields(
					$itemFrameWork,
					$itemTableArray[$this->type]->getTableObj()->tableFieldArray,
					$itemTableArray[$this->type]->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$itemTableViewArray ['article']->marker,
					$articleViewTagArray,
					$articleParentArray
				);

				$prodUidField = $cnf->getTableDesc($itemTableArray['article']->getTableObj()->name, 'uid_product');
				$fieldsArray = array_merge($fieldsArray, $articleFieldsArray);
				$uidKey = array_search($prodUidField, $fieldsArray);
				if ($uidKey != '')	{
					unset($fieldsArray[$uidKey]);
				}
			}

			if (is_array($giftNumberArray) && count($giftNumberArray)) {
				$personDataFrameWork = tx_div2007_core::getSubpart($itemFrameWork, '###PERSON_DATA###');
				// the itemFramework is a smaller part here
				$itemFrameWork = tx_div2007_core::getSubpart($itemFrameWork, '###PRODUCT_DATA###');
			}
			$backPID = $pibaseObj->piVars['backPID'];
			$backPID = ($backPID ? $backPID : GeneralUtility::_GP('backPID'));
			$basketPID = $this->conf['PIDbasket'];
			$bNeedSingleParams = false;

			if ($this->conf['clickIntoList'])	{
				$pid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $row);
			} else if ($this->conf['clickIntoBasket'] && ($basketPID || $backPID))	{
				$pid = ($basketPID ? $basketPID : $backPID);
			} else {
				$pid = $GLOBALS['TSFE']->id;
				$bNeedSingleParams = true;
			}

			if ($this->type == 'product')	{

				$viewTextTable = $tablesObj->get('tt_products_texts');
				$viewTextViewTable = $tablesObj->get('tt_products_texts', true);
				$textTagArray = $viewTextViewTable->getTagMarkerArray($viewTagArray, $itemTableArray['product']->marker);
				$itemArray = $viewTextTable->getChildUidArray($theCode, $this->uid, $textTagArray, 'tt_products');
				$viewTextViewTable->getRowsMarkerArray($itemArray, $markerArray, $itemTableArray['product']->marker, $textTagArray);
			}

			// $variant = $itemTableArray[$this->type]->variant->getFirstVariantRow();

			$forminfoArray = array('###FORM_NAME###' => 'item_'.$this->uid);

			if ($this->type == 'product' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('taxajax'))	{
				$selectableVariantFieldArray = $itemTableArray[$this->type]->variant->getSelectableFieldArray();
				$param = array($itemTableArray[$this->type]->getFuncTablename() => $selectableVariantFieldArray);
				$javaScriptObj->set('fetchdata', $param, $this->cObj->currentRecord);
			}

			$viewCatTagArray = array();
			$catParentArray = array();
			$catfieldsArray = $markerObj->getMarkerFields(
				$itemFrameWork,
				$viewCatTable->getTableObj()->tableFieldArray,
				$viewCatTable->getTableObj()->requiredFieldArray,
				$tmp = array(),
				$viewCatTable->marker,
				$viewCatTagArray,
				$catParentArray
			);

			$mergeTagArray = array_merge($viewTagArray, $viewCatTagArray);
			$cat = $row['category'];
			$itemTableConf['category'] = $cnf->getTableConf($viewCatTable->getFuncTablename(), 'SINGLE');
			$catArray = $viewCatTable->getCategoryArray($row['uid'],$itemTableConf['category']['orderBy']);

			if (is_array($catArray) && count($catArray))	{
				reset($catArray);
				$cat = current($catArray);
			}

			$categoryMarkerArray = array();
			$viewCatViewTable->getMarkerArray(
				$categoryMarkerArray,
				'',
				$cat,
				$row['pid'],
				$this->config['limitImage'],
				'listcatImage',
				$viewCatTagArray,
				array(),
				$pageAsCategory,
				'SINGLE',
				tx_ttproducts_control_basket::getBasketExtra(),
				'',
				'',
				''
			);

			$categoryJoin  = '';
			$whereCat = '';
			if ($cat) {
				$currentCat = $pibaseObj->piVars[$viewCatViewTable->getPivar()];
				if ($currentCat != '')	{
					$currentCatArray = GeneralUtility::trimExplode(',', $currentCat);
				}
				if (isset($currentCatArray) && is_array($currentCatArray))	{
					$inArray = $GLOBALS['TYPO3_DB']->fullQuoteArray($currentCatArray, 'tt_products');
					$inCat = implode(',', $inArray);
					$catMMTable = $viewCatTable->getMMTablename();
					if ($currentCat!='')	{
						// $bUseBackPid = false;
						$cat = $currentCat;
						if ($catMMTable)	{
							$categoryJoin = $itemTableArray[$this->type]->getTablename().' '.$itemTableArray[$this->type]->getAlias().' INNER JOIN '.$viewCatTable->getMMTablename().' M ON '.$itemTableArray[$this->type]->getAlias().'.uid=M.uid_local';
							$whereCat = ' AND M.uid_foreign IN (' . $inCat . ') ';
						} else {
							$whereCat = ' AND category IN (' . $inCat . ') ';
						}
					}
				}
			}

			if (isset($this->conf['PIDlistDisplay'])) {
				$linkPid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $row);
			} else {
				$linkPid = $pid;
			}

			if ($bUseBackPid && $backPID)	{
				$linkPid = $backPID;
			}

			if (isset($viewTagArray['LINK_ITEM'])) {
				$addQueryString = array();
				$excludeListLinkItem = '';

				if (
					(
						(
							$linkPid == $GLOBALS['TSFE']->id && !$bUseBackPid
						)
					) &&
					$this->conf['NoSingleViewOnList']
				) {
					// if the page remains the same then the product parameter will still be needed
					$excludeListLinkItem = '';
				} else {
					$excludeListLinkItem = $itemTableViewArray[$this->type]->getPivar();
				}

				$sword = $this->pibase->piVars['sword'];
				if ($sword) 	{
					$addQueryString['sword'] = $sword;
				}

				$queryString =
					$this->urlObj->getLinkParams(
						$excludeListLinkItem,
						$addQueryString,
						true,
						$bUseBackPid,
						'',
						$viewCatViewTable->getPivar()
					);

				$linkUrl = tx_div2007_alpha5::getPageLink_fh003(
					$this->cObj,
					$linkPid,
					'', // no product parameter if it returns to the list view
					$queryString,
					array('useCacheHash' => true)
				);
				$linkUrl = htmlspecialchars($linkUrl);
				$wrappedSubpartArray['###LINK_ITEM###'] = array('<a class="listlink" href="' . $linkUrl . '">', '</a>');
			}
			if (isset($viewCatTagArray['LINK_CATEGORY'])) {
				$catRow = $viewCatTable->get($cat);
				$catListPid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $catRow);
				$viewCatViewTable->getSubpartArrays($this->urlObj, $catRow, $subpartArray, $wrappedSubpartArray, $viewCatTagArray, $catListPid, 'LINK_CATEGORY');
			}

			$catTitle = $viewCatViewTable->getMarkerArrayCatTitle($categoryMarkerArray);
			$viewParentCatTagArray = array();
			$viewCatViewTable->getParentMarkerArray(
				$parentArray,
				$row,
				$catParentArray,
				$row['category'],
				$row['pid'],
				$this->config['limitImage'],
				'listcatImage',
				$viewParentCatTagArray,
				array(),
				$pageAsCategory,
				'SINGLE',
				tx_ttproducts_control_basket::getBasketExtra(),
				'',
				''
			);

			if (isset($viewCatTagArray['LINK_PARENT1_CATEGORY'])) {
				$catRow = $viewCatTable->getParent($cat);
				$catListPid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $catRow);
				$viewCatTable->getSubpartArrays($this->urlObj, $catRow, $subpartArray, $wrappedSubpartArray, $viewCatTagArray, $catListPid, 'LINK_PARENT1_CATEGORY');
			}

			if ($this->type == 'product' && in_array($useArticles,array(1,2,3))) {
				// get the article uid with these colors, sizes and gradings

				$articleRow = $itemTableArray['product']->getArticleRow($row, 'SINGLE');

				if (is_array($articleRow) && isset($articleRow['inStock']))	{
					$row['inStock'] = $articleRow['inStock'];
				}
			}
    
            if (
                !empty($articleRow) &&
                isset($prodVariantRow['ext']) &&
                !isset($prodVariantRow['ext']['tt_products_articles'])
            ) {
                $prodVariantRow['ext']['tt_products_articles'][] = $articleRow;
            }

			$itemTableViewArray[$this->type]->getModelMarkerArray(
				$row,
				$itemTableViewArray[$this->type]->getMarker(),
				$markerArray,
				$catTitle,
				$this->config['limitImageSingle'],
				'image',
				$viewTagArray,
				$forminfoArray,
				'SINGLE',
				tx_ttproducts_control_basket::getBasketExtra(),
				'',
				'',
				'',
				true,
				'UTF-8'
			);

			$prodVariantRow = $row;

			if ($this->type == 'product') {
				$prodRow = $row;

				if (in_array($useArticles,array(1,2,3)))	{

						// use the product if no article row has been found
					if ($articleRow)	{
						$itemTableArray['product']->mergeAttributeFields($prodVariantRow, $articleRow, false);
					}
						// use the fields of the article instead of the product
					$itemTableViewArray['product']->getModelMarkerArray(
						$prodVariantRow,
						$itemTableViewArray['article']->getMarker(),
						$markerArray,
						$catTitle,
						$this->config['limitImage'],
						'image',
						$articleViewTagArray,
						array(),
						'SINGLE',
						tx_ttproducts_control_basket::getBasketExtra(),
						'from-tt-products-articles',
						'',
						'',
						true,
						'UTF-8'
					);

					$itemTableViewArray['article']->getItemSubpartArrays(
						$itemFrameWork,
						'tt_products_articles',
						$prodVariantRow,
						$subpartArray,
						$wrappedSubpartArray,
						$markerArray,
						$articleViewTagArray,
						$theCode,
						tx_ttproducts_control_basket::getBasketExtra(),
						$iCount
					);
				}
			} else if ($this->type == 'article')	{
				$articleRow = $row;
				$prodRow = $itemTableArray['product']->get($row['uid_product']);
				$itemTableViewArray['product']->getModelMarkerArray(
					$prodRow,
					$itemTableViewArray['product']->getMarker(),
					$markerArray,
					$catTitle,
					$this->config['limitImage'],
					'listImage',
					$viewTagArray,
					array(),
					'SINGLE',
					tx_ttproducts_control_basket::getBasketExtra(),
					1,
					'',
					'',
					true,
					'UTF-8'
				);
			}

			if ($this->type == 'product' || $this->type == 'article')	{
				$item =
					$basketObj->getItem(
						tx_ttproducts_control_basket::getBasketExtra(),
						$prodRow,  // $prodVariantRow,
						'firstVariant'
					);
                if (!empty($item)) {
                    $row = $item['rec'];
                }

                if ($this->type != 'article')	{
                    $prodRow = $row;
                }
                $prodVariantRow = $row;
                if ($this->type == 'product') {
                    $prodVariantRow = $prodRow;
                }

				$basketItemView = GeneralUtility::makeInstance('tx_ttproducts_basketitem_view');
				$basketItemView->init($this->pibaseClass,$basketObj->basketExt,$basketObj->getItemObj());

				$basketItemView->getItemMarkerArray(
					$itemTableArray[$this->type]->getFuncTablename(),
					$item,
					$markerArray,
					$viewTagArray,
					$tmpHidden,
					'SINGLE',
					1,
					true
				);

				$itemTableViewArray[$this->type]->getItemMarkerSubpartArrays(
					$itemFrameWork,
					'tt_products',
					$row,
					$markerArray,
					$subpartArray,
					$wrappedSubpartArray,
					$viewTagArray,
					'SINGLE',
					tx_ttproducts_control_basket::getBasketExtra(),
					1
				);

				$currPriceMarkerArray = array();
				$itemTableViewArray[$this->type]->getCurrentPriceMarkerArray(
					$currPriceMarkerArray,
					$itemTableArray['product']->getTablename(),
					$prodRow,
					$itemTableArray['article']->getTablename(),
					$prodVariantRow,
					'',
					'SINGLE',
					tx_ttproducts_control_basket::getBasketExtra()
				);

				$markerArray = array_merge($markerArray, $currPriceMarkerArray);
			}

			$linkMemoConf = array();
			if (
				isset($linkConfArray) &&
				is_array($linkConfArray) &&
				isset($linkConfArray['FORM_MEMO.'])
			) {
				$linkMemoConf = $linkConfArray['FORM_MEMO.'];
			}

			$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'];
			$markerArray['###FORM_MEMO###'] = htmlspecialchars(
				tx_div2007_alpha5::getPageLink_fh003(
					$this->cObj,
					$pidMemo,
					'',
					$this->urlObj->getLinkParams(
						$excludeList,
						array(),
						true,
						$bUseBackPid,
						$itemTableViewArray[$this->type]->getPivar()
					),
					$linkMemoConf
				)
			);

			$addQueryString = array();

			if ($bNeedSingleParams)	{
				// if the page remains the same then the product parameter will still be needed if there is no list view
				$addQueryString[$this->type] = $this->uid;
			}
			if ($bUseBackPid && $backPID)	{
				$addQueryString['backPID'] = $backPID;
			}

			$markerArray = $this->urlObj->addURLMarkers(
				$pid,
				$markerArray,
				$addQueryString,
				$excludeList,
				$bUseBackPid
			); // Applied it here also...

			$queryPrevPrefix = '';
			$queryNextPrefix = '';
			$prevOrderby = '';
			$nextOrderby = '';
			$bDefaultOrder = true;

			if ($this->conf['orderByItemNumberSg']) {
				$itemnumberField = $itemTableArray[$this->type]->fieldArray['itemnumber'];
				$queryPrevPrefix = $itemnumberField.' < '.$GLOBALS['TYPO3_DB']->fullQuoteStr($origRow[$itemnumberField],$tablename);
				$queryNextPrefix = $itemnumberField.' > '.$GLOBALS['TYPO3_DB']->fullQuoteStr($origRow[$itemnumberField],$tablename);
				$prevOrderby = $itemnumberField.' DESC';
				$nextOrderby = $itemnumberField.' ASC';
				$bDefaultOrder = false;
			} else {

				if(is_array($itemTableConf[$this->type]) && isset($itemTableConf[$this->type]['orderBy']))	{
					$orderByFieldArray = GeneralUtility::trimExplode(',',$itemTableConf[$this->type]['orderBy']);
					$count = count($orderByFieldArray);

					if ($count)	{
						$bDefaultOrder = false;
						$queryPrevPrefixArray = array();
						$queryNextPrefixArray = array();
						$prevOrderbyArray = array();
						$nextOrderbyArray = array();
						$limitArray = array();

						foreach($orderByFieldArray as $i => $orderByFieldLine)	{
							$bIsDesc = (stripos($orderByFieldLine,'DESC') !== false);
							$bIsLast = ($i == $count - 1);
							$orderByField = str_ireplace('ASC','',$orderByFieldLine);
							$orderByField = trim(str_ireplace('DESC','',$orderByField));
							$comparatorPrev = ($bIsDesc ? '>' : '<');
							$comparatorNext = ($bIsDesc ? '<' : '>');
							$comparand = $GLOBALS['TYPO3_DB']->fullQuoteStr($origRow[$orderByField],$tablename);
							$newPrevPrevix = $orderByField . ' '. $comparatorPrev . ' ' . $comparand;
							$newNextPrevix = $orderByField . ' ' . $comparatorNext . ' ' . $comparand;

							$ascOperatorPrev = ($bIsDesc ? 'ASC' : 'DESC');
							$ascOperatorNext = ($bIsDesc ? 'DESC' : 'ASC');
							$prevOrderbyArray[] = $orderByField . ' ' . $ascOperatorPrev;
							$nextOrderbyArray[] = $orderByField . ' ' . $ascOperatorNext;

							if ($bIsLast)	{
								$lastPrevPrevix = implode(' AND ',$limitArray) . (count($limitArray) > 0 ? ' AND ' : '') . $newPrevPrevix;
								$lastNextPrevix = implode(' AND ',$limitArray) . (count($limitArray) > 0 ? ' AND ' : '') .  $newNextPrevix;
							} else {
								$limitArray[] = $orderByField . '=' . $comparand;
								$queryPrevPrefixArray[] = $newPrevPrevix;
								$queryNextPrefixArray[] = $newNextPrevix;
							}
						}
						$queryNextPrefix = '(' . implode(' AND ',$queryNextPrefixArray) . (count($queryNextPrefixArray) > 0 ? ' OR ' : '') . $lastNextPrevix . ')';
						$queryPrevPrefix = '(' . implode(' AND ',$queryPrevPrefixArray) . (count($queryNextPrefixArray) > 0 ? ' OR ' : '') . $lastPrevPrevix . ')';
						$prevOrderby = implode(',', $prevOrderbyArray);
						$nextOrderby = implode(',', $nextOrderbyArray);
					}
				}
			}
			if ($bDefaultOrder)	{
				$queryPrevPrefix = 'uid < '.intval($this->uid);
				$queryNextPrefix = 'uid > '.intval($this->uid);

				$prevOrderby = 'uid DESC';
				$nextOrderby = 'uid ASC';
			}

			$prevOrderby = $itemTableArray[$this->type]->getTableObj()->transformOrderby($prevOrderby);
			$nextOrderby = $itemTableArray[$this->type]->getTableObj()->transformOrderby($nextOrderby);
			$whereFilter = '';
			if (is_array($itemTableConf[$this->type]['filter.']) && is_array($itemTableConf[$this->type]['filter.']['regexp.']))	{
				if (is_array($itemTableConf[$this->type]['filter.']['regexp.']['field.']))	{
					foreach ($itemTableConf[$this->type]['filter.']['field.'] as $field => $value)	{
						$whereFilter .= ' AND ' . $field . ' REGEXP ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(quotemeta($value), $itemTableArray[$this->type]->getTableObj()->name);
					}
				}
			}
			$queryprev = '';
			$wherePid = ' AND pid IN (' . $this->pidListObj->getPidlist() . ')';
			$wherestock = ($this->conf['showNotinStock'] || !is_array($GLOBALS['TCA'][$itemTableArray[$this->type]->getTableObj()->name]['columns']['inStock']) ? '' : ' AND (inStock <> 0) ').$whereFilter;
			$queryprev = $queryPrevPrefix . $whereCat . $wherePid . $wherestock . $itemTableArray[$this->type]->getTableObj()->enableFields();

			$resprev = $itemTableArray[$this->type]->getTableObj()->exec_SELECTquery('*', $queryprev, '', $GLOBALS['TYPO3_DB']->stripOrderBy($prevOrderby),'1',$categoryJoin);

			if ($rowprev = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resprev) )	{
				$addQueryString = array();
				$addQueryString[$this->type] = $rowprev['uid'];

				if ($bUseBackPid) 	{
					$addQueryString['backPID'] = $backPID;
				} else if ($cat)	{
					$addQueryString[$viewCatViewTable->getPivar()] = $cat;
				}

				$prevUrl = tx_div2007_alpha5::getPageLink_fh003(
					$this->cObj,
					$GLOBALS['TSFE']->id,
					'',
					$this->urlObj->getLinkParams(
						$excludeList,
						$addQueryString,
						true,
						$bUseBackPid,
						$itemTableViewArray[$this->type]->getPivar(),
						$viewCatViewTable->getPivar()
					),
					array(
						'useCacheHash' => true
					)
				);
				$wrappedSubpartArray['###LINK_PREV_SINGLE###'] = array('<a href="'. htmlspecialchars($prevUrl) .'">','</a>');
			} else	{
				$subpartArray['###LINK_PREV_SINGLE###']='';
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($resprev);
			$querynext = $queryNextPrefix . $whereCat . $wherePid . $wherestock . $itemTableArray[$this->type]->getTableObj()->enableFields();
			$resnext = $itemTableArray[$this->type]->getTableObj()->exec_SELECTquery('*', $querynext, '', $GLOBALS['TYPO3_DB']->stripOrderBy($nextOrderby),'1',$categoryJoin);

			if ($rownext = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resnext) )	{
				$addQueryString=array();
				$addQueryString[$this->type] = $rownext['uid'];
				if ($bUseBackPid) 	{
					$addQueryString['backPID'] = $backPID;
				} else if ($cat)	{
					$addQueryString[$viewCatViewTable->getPivar()] = $cat;
				}
				$nextUrl = tx_div2007_alpha5::getPageLink_fh003(
					$this->cObj,
					$GLOBALS['TSFE']->id,
					'',
					$this->urlObj->getLinkParams(
						$excludeList,
						$addQueryString,
						true,
						$bUseBackPid,
						$itemTableViewArray[$this->type]->getPivar(),
						$viewCatViewTable->getPivar()
					),
					array(
						'useCacheHash' => true
					)
				);
				$wrappedSubpartArray['###LINK_NEXT_SINGLE###'] = array('<a href="' . htmlspecialchars($nextUrl) . '">','</a>');
			} else {
				$subpartArray['###LINK_NEXT_SINGLE###'] = '';
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($resnext);

			if ($this->type == 'product')	{
				$itemTableViewArray[$this->type]->variant->removeEmptyMarkerSubpartArray(
					$markerArray,
					$subpartArray,
					$wrappedSubpartArray,
					$row,
					$this->conf,
					$itemTableArray[$this->type]->hasAdditional($row,'isSingle'),
					!$itemTableArray[$this->type]->hasAdditional($row,'noGiftService')
				);
			}
			$relatedListView = GeneralUtility::makeInstance('tx_ttproducts_relatedlist_view');
			$relatedListView->init($this->cObj, $this->pidListObj->getPidlist(), $this->pidListObj->getRecursive());
			$listMarkerArray = $relatedListView->getListMarkerArray('SINGLE',$this->pibaseClass,$templateCode, $markerArray, $viewTagArray, $itemTableArray[$this->type]->getFuncTablename(), $this->uid, $this->uidArray, $useArticles, $pageAsCategory, $this->pid, $errorCode);

			if (
				$this->type == 'product' &&
				$listMarkerArray !== false &&
				is_array($listMarkerArray) &&
				$this->uidArray['article']
			) {
				$uid = $this->uidArray['article'];
				$listArticleMarkerArray = $relatedListView->getListMarkerArray(
					'SINGLE',
					$this->pibaseClass,
					$templateCode,
					$markerArray,
					$articleViewTagArray,
					$itemTableArray['article']->getFuncTablename(),
					$uid,
					$this->uidArray,
					$useArticles,
					$pageAsCategory,
					$this->pid,
					$errorCode
				);

				if (
					$listArticleMarkerArray !== false &&
					is_array($listArticleMarkerArray)
				) {
					$listMarkerArray = array_merge($listMarkerArray, $listArticleMarkerArray);
				}
			}

			if ($listMarkerArray && is_array($listMarkerArray)) {
				$quantityMarkerArray = array();

				foreach ($listMarkerArray as $marker => $markerValue) {
					$markerValue = $parser->substituteMarkerArray($markerValue, $markerArray);
					$markerValue = $parser->substituteMarkerArray($markerValue, $quantityMarkerArray);
					$markerArray[$marker] = $markerValue;
				}
			}

			$jsMarkerArray = array();
			$this->javaScriptMarker->getMarkerArray($jsMarkerArray, $markerArray);
			$markerArray = array_merge($categoryMarkerArray, $jsMarkerArray, $markerArray);
			$markerArray['###HIDDENFIELDS###'] = $hiddenText; // TODO

			// edit jf begin
			// Rootline bis Shop-Root holen
			// Breadcrumb aufbauen
			// Seiten <title> aendern
			$listID = $row['pid'];
			$str = '';
			// Hole rootline, ausgehend von Kategorie des aktuellen Produktes
			// Speichere uids bis Shop-Root
			$breadcrumbArray = array();
			$rootlineArray = array();
			$rootlineArray[] = $row['title'];
			$rootline = array();
			$parent = $row['pid'];
			do {
				$res_parent = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,title', 'pages', 'uid='.$parent.' AND hidden=0 AND deleted=0');
				$row_parent = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_parent);
				$GLOBALS['TYPO3_DB']->sql_free_result($res_parent);
				$parent = $row_parent['pid'];
				$rootlineArray[] = $row_parent['title'];
				$breadcrumbArray[] = $pibaseObj->pi_linkToPage(
					$row_parent['title'],
					$row_parent['uid'],
					'',
					array()
				);
			} while($row_parent['uid'] != $this->conf['id_shop']);
			$markerArray['###LINK_BACK2LIST###'] = implode(' &laquo; ', array_reverse($breadcrumbArray));
			// edit jf end
			$markerArray = $markerObj->reduceMarkerArray($itemFrameWork, $markerArray);
				// Substitute
			$content = tx_div2007_core::substituteMarkerArrayCached($itemFrameWork, $markerArray, $subpartArray, $wrappedSubpartArray);

			if ($personDataFrameWork) {
				$subpartArray = array();
				$wrappedSubpartArray = array();
				foreach ($giftNumberArray as $giftnumber) {
					$markerArray = tx_ttproducts_gifts_div::addGiftMarkers($markerArray, $giftnumber);
					$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'].'_'.$giftnumber;
					$markerArray['###FORM_ONSUBMIT###']='return checkParams (document.'.$markerArray['###FORM_NAME###'].')';
					$addQueryString = array();
					$addQueryString[$this->type] = intval($row['uid']);
					$addQueryString['variants'] = htmlspecialchars($this->variants);

					$markerArray = $this->urlObj->addURLMarkers($pid, $markerArray, $addQueryString, '',$bUseBackPid); // Applied it here also...

					$markerArray['###FIELD_NAME###'] = 'ttp_gift[item]['.$row['uid'].']['.$this->variants.']'; // here again, because this is here in ITEM_LIST view
					$markerArray['###FIELD_QTY###'] = $basketObj->basketExt['gift'][$giftnumber]['item'][$row['uid']][$this->variants];
					$content .= tx_div2007_core::substituteMarkerArrayCached($personDataFrameWork, $markerArray, $subpartArray, $wrappedSubpartArray);
				}
				$javaScriptObj->set('email');  // other JavaScript checks can come here
			}

			if ($content == '') {
				$errorCode[0] = 'internal_error';
				$errorCode[1] = 'TTP_3';
				$errorCode[2] = 'TYPO3 function';
			}
		} else {
			$errorCode[0] = 'wrong_parameter';
			$errorCode[1] = ($this->type ? $this->type : 'product');
			$errorCode[2] = intval($this->uidArray[$this->type]);
			$errorCode[3] = $this->pidListObj->getPidlist();
		}

		return $content;
	} // printView
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_single_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_single_view.php']);
}



