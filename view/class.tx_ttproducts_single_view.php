<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


use JambageCom\Div2007\Utility\FrontendUtility;


class tx_ttproducts_single_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $conf;
	public $config;
	public $uid; 	// product id
	public $type = 'product'; 	// 'product', 'article' or 'dam'
	public $variants; 	// different attributes
	public $pid; // PID where to go
	public $useArticles;
	public $uidArray = [];
	public $pidListObj;


	public function init (
		$uidArray,
		$extVars,
		$pid,
		$useArticles,
		$pid_list,
		$recursive
	) {
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = $cnf->getConf();
		$this->config = $cnf->getConfig();

		if (count($uidArray)) {
			$this->uidArray = $uidArray;
			reset($uidArray);
			if (isset($uidArray['product'])) {
				$this->type = 'product';
				$this->uid = $uidArray['product'];
			} else if (isset($uidArray['article'])) {
				$this->uid = $uidArray['article'];
				$this->type = 'article';
			} else if (isset($uidArray['dam']) && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dam')) {
				$this->type = 'dam';
				$this->uid = $uidArray['dam'];
			}
		}

		$this->variants = $extVars;
		$this->pid = $pid;
		if ($this->type != 'product') {	// articles are only possible for products
			$useArticles = 0;
		}
		$this->useArticles = $useArticles;
		$this->pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
		$this->pidListObj->applyRecursive($recursive, $pid_list, true);
		$this->pidListObj->setPageArray();
	}


	// returns the single view
	public function printView (
		$templateCode,
		&$errorCode,
		$pageAsCategory,
		$templateSuffix = ''
	) {
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$javaScriptMarker = GeneralUtility::makeInstance('tx_ttproducts_javascript_marker');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
        $cObj = \JambageCom\TtProducts\Api\ControlApi::getCObj();

        $parser = tx_div2007_core::newHtmlParser(false);

		$piVars = tx_ttproducts_model_control::getPiVars();
		$conf = $cnf->getConf();
		$externalRowArray = [];

		$theCode = 'SINGLE';

		$basketExt = tx_ttproducts_control_basket::getBasketExt();
		$basketExtra = tx_ttproducts_control_basket::getBasketExtra();
		$basketRecs =  tx_ttproducts_control_basket::getRecs();
		$prodRow = [];

		$bUseBackPid = true;
		$viewControlConf = $cnf->getViewControlConf('SINGLE');

		if (count($viewControlConf)) {
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

		$itemTableArray = [];
		$itemTableArray['product'] = $tablesObj->get('tt_products');
		$tableConf = $itemTableArray['product']->getTableConf('SINGLE');
		$itemTableArray['product']->initCodeConf('SINGLE', $tableConf);
		$itemTableArray['article'] = $tablesObj->get('tt_products_articles');
		$tableConf = $itemTableArray['article']->getTableConf('SINGLE');
		$itemTableArray['article']->initCodeConf('SINGLE', $tableConf);
		$itemTableViewArray = [];
		$itemTableViewArray['product'] = $tablesObj->get('tt_products', true);
		$itemTableViewArray['article'] = $tablesObj->get('tt_products_articles', true);
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dam')) {
			$itemTableArray['dam'] = $tablesObj->get('tx_dam');
			$itemTableViewArray['dam'] = $tablesObj->get('tx_dam', true);
		}
		$funcTablename = $itemTableArray[$this->type]->getFuncTablename();

		$rowArray = ['product' => [], 'article' => [], 'dam' => []];
		$itemTableConf = $rowArray;
		$itemTableLangFields = $rowArray;
		$content = '';
		$wherePid = '';

		if (
			$this->config['displayCurrentRecord'] &&
			$this->type == 'product' &&
			!$this->useArticles
		) {
			$rowArray[$this->type] = $cObj->data;
		} else if ($this->uid) {
			$pid_list = $this->pidListObj->getPidlist();
			if ($pid_list != '-1') {
				$wherePid = 'pid IN (' . $pid_list . ')';
			}
			$rowArray[$this->type] =
				$itemTableArray[$this->type]->get(
					$this->uid,
					0,
					true,
					$wherePid
				);
			$itemTableConf[$this->type] =
				$cnf->getTableConf(
					$itemTableArray[$this->type]->getFuncTablename(),
					'SINGLE'
				);
			$itemTableLangFields[$this->type] =
				$cnf->getTranslationFields($itemTableConf[$this->type]);
			// TODO: $itemImageFields[$this->type] = $cnf->getImageFields($itemTableConf[$this->type]);

			if ($this->type == 'product' || $this->type == 'dam') {
				if ($this->variants) {
					$itemTableArray[$this->type]->getVariant()->modifyRowFromVariant(
						$rowArray[$this->type],
						$this->variants
					);
				}
			} else if ($this->type == 'article') {
				$where = 'pid IN (' . $this->pidListObj->getPidlist() . ')';
				$rowArray['product'] =
					$itemTableArray['product']->get(
						intval($rowArray[$this->type]['uid_product']),
						0,
						true,
						$wherePid
					);

				$itemTableConf['product'] = $cnf->getTableConf($itemTableArray['product']->getFuncTablename(), 'SINGLE');
				$itemTableLangFields['product'] = $cnf->getTranslationFields($itemTableConf['product']);
				$itemImageFields['product'] = $cnf->getImageFields($itemTableConf['product']);
				$itemTableArray['article']->mergeAttributeFields(
					$rowArray['product'],
					$rowArray['article'],
					false,
					false,
					false,
					'',
					false
				);
			}
		}

		$origRow = $rowArray[$this->type];

		foreach ($itemTableLangFields as $type => $fieldArray) {
			if (is_array($fieldArray)) {
				foreach ($fieldArray as $field => $langfield) {
					$rowArray[$type][$field] = $rowArray[$type][$langfield];
				}
			}
		}
		$row = $rowArray[$this->type];
		$tablename = $itemTableArray[$this->type]->getTableObj()->getName();

		if (!empty($row['uid'])) {
			// $this->uid = intval ($row['uid']); // store the uid for later usage here

			$itemTableArray['product']->getTableObj()->transformRow($row, TT_PRODUCTS_EXT);
			$useArticles = $itemTableArray['product']->getVariant()->getUseArticles();

			if ($useArticles == 3) {
				$itemTableArray['product']->fillVariantsFromArticles($row);
			}
				// add Global Marker Array
			$markerArray = $markerObj->getGlobalMarkerArray();
			$subpartArray = [];
			$wrappedSubpartArray = [];
			$pageObj = $tablesObj->get('pages');

			$bIsGift = false; // no GIFT feature any more!

				// Get the subpart code
			$subPartMarker ='';

			if ($this->config['displayCurrentRecord']) {
				$subPartMarker = 'ITEM_SINGLE_DISPLAY_RECORDINSERT';
			} else if (
				!$this->conf['alwaysInStock'] &&
				$row['inStock'] <= 0 &&
				$this->conf['showNotinStock'] &&
				isset($GLOBALS['TCA'][$itemTableArray[$this->type]->getTableObj()->name]['columns']['inStock']) &&
				is_array($GLOBALS['TCA'][$itemTableArray[$this->type]->getTableObj()->name]['columns']['inStock'])
			) {
				$subPartMarker = 'ITEM_SINGLE_DISPLAY_NOT_IN_STOCK';
			} else {
				if ($this->type == 'product') {
					$subPartMarker = 'ITEM_SINGLE_DISPLAY';
				} else if ($this->type == 'article') {
					$subPartMarker = 'ARTICLE_SINGLE_DISPLAY';
				} else if ($this->type == 'dam') {
					$subPartMarker = 'DAM_SINGLE_DISPLAY';
				}
			}

			// get categories
			if (!$pageAsCategory || $pageAsCategory == 1) {
				if ($this->type == 'product' || $this->type == 'article') {
					$catTablename = 'tt_products_cat';
				} else if ($this->type == 'dam') {
					$catTablename = 'tx_dam_cat';
				}
			} else {
				$catTablename = 'pages';
			}
			$viewCatViewTable = $tablesObj->get($catTablename, true);
			$viewCatTable = $viewCatViewTable->getModelObj();
			$categoryPivar = $viewCatViewTable->getPivar();

			// Add the template suffix
			$subPartMarker = $subPartMarker.$templateSuffix;
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

			$t = [];
			$t['categoryFrameWork'] = tx_div2007_core::getSubpart(
				$itemFrameWork,
				'###ITEM_CATEGORY###'
			);

			$urlObj->getWrappedSubpartArray(
				$wrappedSubpartArray,[],
				'',
				$bUseBackPid
			);

			$excludeList = '';

			if (isset($viewParamConf) && is_array($viewParamConf)) {
				if (!empty($viewParamConf['ignore'])) {
					$excludeList = $viewParamConf['ignore'];
				}
				if (isset($viewParamConf['item']) && GeneralUtility::inList($viewParamConf['item'], $categoryPivar)) {
					// nothing
				} else {
					$prefixId = tx_ttproducts_model_control::getPrefixId();
					$excludeList .= ($excludeList != '' ? ',' : '') . $prefixId . '[' . $categoryPivar . ']';
				}
			}
			$pidMemo = ($this->conf['PIDmemo'] ? $this->conf['PIDmemo'] : $GLOBALS['TSFE']->id);

			tx_ttproducts_control_memo::getWrappedSubpartArray(
				$wrappedSubpartArray,
				$pidMemo,
				$row['uid'],
				$cObj,
				$urlObj,
				$excludeList,
				[],
				'',
				$bUseBackPid
			);

			if (!$itemFrameWork) {
				$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
				$errorCode[0] = 'no_subtemplate';
				$errorCode[1] = '###' . $subPartMarker . '###';
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
			
			$markerFieldArray = [
				'BULKILY_WARNING' => 'bulkily',
				'PRODUCT_SPECIAL_PREP' => 'special_preparation',
				'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
				'PRODUCT_LINK_DATASHEET' => 'datasheet'];
			$viewTagArray = [];
			$parentArray = [];

			$fieldsArray = $markerObj->getMarkerFields(
				$itemFrameWork,
				$itemTableArray[$this->type]->getTableObj()->tableFieldArray,
				$itemTableArray[$this->type]->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$itemTableArray[$this->type]->marker,
				$viewTagArray,
				$parentArray
			);

			$articleViewTagArray = [];
			if ($this->type == 'product' && in_array($useArticles, [1, 3])) {
				$markerFieldArray = [];
				$articleParentArray = [];
				$articleFieldsArray = $markerObj->getMarkerFields(
					$itemFrameWork,
					$itemTableArray[$this->type]->getTableObj()->tableFieldArray,
					$itemTableArray[$this->type]->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$itemTableViewArray['article']->marker,
					$articleViewTagArray,
					$articleParentArray
				);

				$prodUidField = $cnf->getTableDesc($itemTableArray['article']->getTableObj()->name, 'uid_product');
				$fieldsArray = array_merge($fieldsArray, $articleFieldsArray);
				$uidKey = array_search($prodUidField, $fieldsArray);
				if ($uidKey != '') {
					unset($fieldsArray[$uidKey]);
				}
			}

			$backPID = $piVars['backPID'] ?? '';
			$backPID = ($backPID ? $backPID : GeneralUtility::_GP('backPID'));
			$basketPID = $this->conf['PIDbasket'];
			$bNeedSingleParams = false;

			if ($this->conf['clickIntoList']) {
				$pid =
					$pageObj->getPID(
						$this->conf['PIDlistDisplay'] ?? '',
						$this->conf['PIDlistDisplay.'] ?? '',
						$row
					);
			} else if (!empty($this->conf['clickIntoBasket']) && ($basketPID || $backPID)) {
				$pid = ($basketPID ? $basketPID : $backPID);
			} else {
				$pid = $GLOBALS['TSFE']->id;
				$bNeedSingleParams = true;
			}

			if ($this->type == 'product') {

				$viewTextTable = $tablesObj->get('tt_products_texts');
				$viewTextViewTable = $tablesObj->get('tt_products_texts', true);
				$textTagArray =
					$viewTextViewTable->getTagMarkerArray(
						$viewTagArray,
						$itemTableArray['product']->marker
					);

				$itemArray =
					$viewTextTable->getChildUidArray(
						$theCode,
						$this->uid,
						$textTagArray,
						'tt_products'
					);
				$viewTextViewTable->getRowsMarkerArray(
					$itemArray,
					$markerArray,
					$itemTableArray['product']->marker,
					$textTagArray
				);
			}

			// $variant = $itemTableArray[$this->type]->variant->getFirstVariantRow();

			$forminfoArray = ['###FORM_NAME###' => 'item_' . $this->uid];

			if ($this->type == 'product' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('taxajax')) {

				tx_ttproducts_control_product::addAjax(
					$tablesObj,
					$languageObj,
					$theCode,
					$itemTableArray[$this->type]->getFuncTablename()
				);
			}

			$viewCatTagArray = [];
			$catParentArray = [];
			$tmp = [];
			$catfieldsArray = $markerObj->getMarkerFields(
				$itemFrameWork,
				$viewCatTable->getTableObj()->tableFieldArray,
				$viewCatTable->getTableObj()->requiredFieldArray,
				$tmp,
				$viewCatViewTable->getMarker(),
				$viewCatTagArray,
				$catParentArray
			);

			$mergeTagArray = array_merge($viewTagArray, $viewCatTagArray);
			$cat = $row['category'];
			$itemTableConf['category'] = $cnf->getTableConf($viewCatTable->getFuncTablename(), 'SINGLE');
			$catArray = $viewCatTable->getCategoryArray($row, $itemTableConf['category']['orderBy']);

			if (is_array($catArray) && count($catArray)) {
				reset($catArray);
				$cat = current($catArray);
			}
			$allowedCategoryArray = [];

			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] != '2') {
				$allowedCategories = $this->config['defaultCategoryID'];
				if ($allowedCategories != '') {
					$allowedCategoryArray = explode(',', $allowedCategories);
				}
			}

			$show = true;

			if (!empty($allowedCategoryArray)) {
				if (empty($catArray)) {
					$show = false;
				} else {
					$matches = array_intersect($allowedCategoryArray, $catArray);
					if (empty($matches)) {
						$show = false;
					}
				}
			}

			if ($show) {
				if (
					!empty($t['categoryFrameWork'])
				) {
					if (is_array($catArray) && count($catArray)) {
                        $subpartArray['###ITEM_CATEGORY###'] = '';
						foreach ($catArray as $category) {

							$categoryMarkerArray = [];
							$viewCatViewTable->getMarkerArray( // Todo: do not repeat this step for the first category
								$categoryMarkerArray,
								'',
								$category,
								$row['pid'],
								$this->config['limitImage'],
								'listcatImage',
								$viewCatTagArray,
								[],
								$pageAsCategory,
								'SINGLE',
								$basketExtra,
								$basketRecs,
								'',
								'',
								''
							);
							$subpartArray['###ITEM_CATEGORY###'] .=
								tx_div2007_core::substituteMarkerArrayCached(
									$t['categoryFrameWork'],
									$categoryMarkerArray,
									[],
									[]
								);
						}
					} else {
						$wrappedSubpartArray['###ITEM_CATEGORY###'] = '';
					}
				}

				$categoryMarkerArray = [];
				$viewCatViewTable->getMarkerArray(
					$categoryMarkerArray,
					'',
					$cat,
					$row['pid'],
					$this->config['limitImage'],
					'listcatImage',
					$viewCatTagArray,
					[],
					$pageAsCategory,
					'SINGLE',
					$basketExtra,
					$basketRecs,
					'',
					'',
					''
				);

				$categoryJoin = '';
				$whereCat = '';
				if ($cat) {
					$currentCat = $piVars[$viewCatViewTable->getPivar()];
					$currentCatArray = [];

					if ($currentCat != '') {
						$currentCatArray = GeneralUtility::trimExplode(',', $currentCat);
					}
					if (!empty($allowedCategoryArray)) {
						$currentCatArray = array_merge($currentCatArray, $allowedCategoryArray);
					}

					if (isset($currentCatArray) && is_array($currentCatArray)) {
						$inArray = $GLOBALS['TYPO3_DB']->fullQuoteArray($currentCatArray, 'tt_products');
						$inCat = implode(',', $inArray);
						$catMMTable = $viewCatTable->getMMTablename();

						if (!empty($currentCatArray)) {
							// $bUseBackPid = false;
							$cat = $currentCat;
							if ($catMMTable) {
								$categoryJoin = $itemTableArray[$this->type]->getTablename() . ' ' . $itemTableArray[$this->type]->getAlias().' INNER JOIN ' . $viewCatTable->getMMTablename() . ' M ON ' . $itemTableArray[$this->type]->getAlias().'.uid=M.uid_local';
								$whereCat = ' AND M.uid_foreign IN (' . $inCat . ') ';
							} else {
								$whereCat = ' AND category IN (' . $inCat . ') ';
							}
						}
					}
				}

				if (
                    isset($this->conf['PIDlistDisplay']) ||
                    isset($this->conf['PIDlistDisplay.'])
                ) {
					$linkPid =
						$pageObj->getPID(
							$this->conf['PIDlistDisplay'] ?? '',
							$this->conf['PIDlistDisplay.'] ?? '',
							$row
						);
				} else {
					$linkPid = $pid;
				}

				if ($bUseBackPid && $backPID) {
					$linkPid = $backPID;
				}

                $addQueryString = [];

                if ($bNeedSingleParams) {
                    // if the page remains the same then the product parameter will still be needed if there is no list view
                    $addQueryString[$this->type] = $this->uid;
                }
                if ($bUseBackPid && $backPID) {
                    $addQueryString['backPID'] = $backPID;
                }

                $sword = $piVars['sword'] ?? '';
                if ($sword) {
                    $addQueryString['sword'] = $sword;
                }

				if (isset($viewTagArray['LINK_ITEM'])) {
					$excludeListLinkItem = '';

                    if (
                        (
                            (
                                $linkPid == $GLOBALS['TSFE']->id
//                              !$bUseBackPid
                            )
                        ) &&
                        (
                            $this->conf['NoSingleViewOnList'] ||

                            !empty($this->conf['PIDitemDisplay']) &&
                            $this->conf['PIDitemDisplay'] != '{$plugin.tt_products.PIDitemDisplay}'
                        )
                    ) {
						// if the page remains the same then the product parameter will still be needed
						$excludeListLinkItem = '';
					} else {
						$excludeListLinkItem = $itemTableViewArray[$this->type]->getPivar();
					}
					$queryString =
						$urlObj->getLinkParams(
							$excludeListLinkItem,
							$addQueryString,
							true,
							$bUseBackPid,
							0,
							'',
							$viewCatViewTable->getPivar()
						);
                    $linkUrl = FrontendUtility::getTypoLink_URL(
                        $cObj,
                        $linkPid,
                        $queryString,
                        '', // no product parameter if it returns to the list view
                        ['useCacheHash' => true]
                    );
					$linkUrl = htmlspecialchars($linkUrl);
					$wrappedSubpartArray['###LINK_ITEM###'] = ['<a class="singlelink" href="' . $linkUrl . '">', '</a>'];
				}

				if (isset($viewCatTagArray['LINK_CATEGORY'])) {
					$catRow = $viewCatTable->get($cat);
					$catListPid =
						$pageObj->getPID(
							$this->conf['PIDlistDisplay'] ?? '',
							$this->conf['PIDlistDisplay.'] ?? '',
							$catRow
						);
					$viewCatViewTable->getSubpartArrays(
						$urlObj,
						$catRow,
						$subpartArray,
						$wrappedSubpartArray,
						$viewCatTagArray,
						$catListPid,
						'LINK_CATEGORY'
					);
				}

				$catTitle = $viewCatViewTable->getMarkerArrayCatTitle($categoryMarkerArray);
				$viewParentCatTagArray = [];
				$viewCatViewTable->getParentMarkerArray(
					$parentArray,
					$row,
					$catParentArray,
					$row['category'],
					$row['pid'],
					$this->config['limitImage'],
					'listcatImage',
					$viewParentCatTagArray,
					[],
					$pageAsCategory,
					'SINGLE',
					$basketExtra,
					$basketRecs,
					'',
					''
				);

				if (isset($viewCatTagArray['LINK_PARENT1_CATEGORY'])) {
					$catRow = $viewCatTable->getParent($cat);
					$catListPid =
						$pageObj->getPID(
							$this->conf['PIDlistDisplay'] ?? '',
							$this->conf['PIDlistDisplay.'] ?? '',
							$catRow
						);
					$viewCatTable->getSubpartArrays(
						$urlObj,
						$catRow,
						$subpartArray,
						$wrappedSubpartArray,
						$viewCatTagArray,
						$catListPid,
						'LINK_PARENT1_CATEGORY'
					);
				}

				if ($this->type == 'product' && in_array($useArticles, [1, 3])) {
					// get the article uid with these colors, sizes and gradings

					$articleRow = $itemTableArray['product']->getArticleRow($row, 'SINGLE', true);
					if (
						is_array($articleRow) &&
						isset($articleRow['inStock'])
					) {
						$row['inStock'] = $articleRow['inStock'];
					}
				}

				if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('taxajax')) {
					$ajaxObj = GeneralUtility::makeInstance('tx_ttproducts_ajax');
					$storedRecs = $ajaxObj->getStoredRecs();

					if (
						isset($storedRecs) &&
						is_array($storedRecs) &&
						isset($storedRecs['tt_products']) &&
						is_array($storedRecs['tt_products']) &&
						isset($storedRecs['tt_products'][$this->uid]) &&
						is_array($storedRecs['tt_products'][$this->uid])
					) {
						$storedRow = $storedRecs['tt_products'][$this->uid];
						foreach ($storedRow as $field => $value) {
							if ($value != '') {
								$row[$field] = $value;
							}
						}
					}
				}

				$basketExt = tx_ttproducts_control_basket::getBasketExt();
				$basketRecs = tx_ttproducts_control_basket::getRecs();

				$prodVariantRow = $row;
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
					$basketExtra,
					$basketRecs,
					'',
					'',
					'',
					'',
					true,
					'UTF-8',
					'', // $hiddenFields
					'',
					[],
					[],
					$bIsGift
				);

				if ($this->type == 'product') {
					$prodRow = $row;

					if (in_array($useArticles, [1, 3])) {

						// use the product if no article row has been found
						if ($articleRow) {
							$itemTableArray['product']->mergeAttributeFields(
								$prodVariantRow,
								$articleRow,
								false,
								false,
								true,
								'',
								false
							);
						}

						$itemTableViewArray['article']->getItemSubpartArrays(
							$itemFrameWork,
							'tt_products_articles',
							$prodVariantRow,
							$subpartArray,
							$wrappedSubpartArray,
							$articleViewTagArray,
							$theCode,
							$basketExtra,
							$basketRecs,
							0
						);
					}
					$allVariants =
						$basketObj->getAllVariants(
							$funcTablename,
							$row,
							$prodVariantRow
						);

					$currRow =
						$basketObj->getItemRow(
							$prodVariantRow,
							$allVariants,
							$useArticles,
							$itemTableArray[$this->type]->getFuncTablename(),
							false
						);

					$basketExt1 = [];
					if (isset($basketExt) && is_array($basketExt) && count($basketExt)) {
						$basketExt1 = $basketExt;
					} else {
						$basketExt1 =
							tx_ttproducts_control_basket::generatedBasketExtFromRow(
								$currRow,
								'1'
							);
					}
					$itemArray =
						$basketObj->getItemArrayFromRow(
							$currRow,
							$basketExt1,
							$basketExtra,
							$basketRecs,
							$funcTablename,
							$externalRowArray,
							$bIsGift
						);

					$basketObj->calculate($itemArray); // get the calculated arrays
					$prodVariantRow =
						$basketObj->getMergedRowFromItemArray(
							$itemArray,
							$basketExtra
						);

					if (
						!empty($articleRow) &&
						isset($prodVariantRow['ext']) &&
						!isset($prodVariantRow['ext']['tt_products_articles'])
					) {
						$prodVariantRow['ext']['tt_products_articles'][] = $articleRow;
					}

						// use the fields of the article instead of the product
					$itemTableViewArray['product']->getModelMarkerArray(
						$prodVariantRow,
						$itemTableViewArray['article']->getMarker(),
						$markerArray,
						$catTitle,
						$this->config['limitImageSingle'],
						'image',
						$articleViewTagArray,
						[],
						'SINGLE',
						$basketExtra,
						$basketRecs,
						'from-tt-products-articles',
						'',
						'',
						'',
						true,
						'UTF-8',
						'', // $hiddenFields
						'',
						[],
						[],
						$bIsGift
					);
				} else if ($this->type == 'article') {
					$articleRow = $row;
					$prodVariantRow = $prodRow = $itemTableArray['product']->get($row['uid_product']);
					$itemTableViewArray['product']->getModelMarkerArray(
						$prodRow,
						$itemTableViewArray['product']->getMarker(),
						$markerArray,
						$catTitle,
						$this->config['limitImageSingle'],
						'listImage',
						$viewTagArray,
						[],
						'SINGLE',
						$basketExtra,
						$basketRecs,
						1,
						'',
						'',
						'',
						true,
						'UTF-8',
						'', // $hiddenFields
						'',
						[],
						[],
						$bIsGift
					);
				}

				if ($this->type == 'product' || $this->type == 'article') {

					$basketItemView = GeneralUtility::makeInstance('tx_ttproducts_basketitem_view');
					$editConfig = $itemTableArray[$this->type]->editVariant->getValidConfig($prodVariantRow);
					$validEditVariant = true;

					if (is_array($editConfig)) {
						$validEditVariant =
							$itemTableArray[$this->type]->editVariant->checkValid(
								$editConfig,
								$prodVariantRow
							);
					}

					$bInputDisabled =
						(
							$prodVariantRow['inStock'] <= 0 ||
							is_array($validEditVariant)
						);
                    $variantValuesRow =
                        $itemTableArray['product']->getVariant()->getVariantValuesRow(
                            $prodRow,
                            []
                        );
                    $prodVariantValuesRow = array_merge($prodVariantRow, $variantValuesRow);
					$item =
						$basketObj->getItem(
							$basketExt,
							$basketExtra,
							$basketRecs,
                            $prodVariantValuesRow, // $prodVariantRow,  // $prodRow  wiederhergestellt wie früher
							'firstVariant',
							$funcTablename,
							$externalRowArray,
							$bIsGift
						);

					$basketItemView->getItemMarkerArray(
						$itemTableArray[$this->type]->getFuncTablename(),
						true,
						$item,
						$markerArray,
						$viewTagArray,
						$tmpHidden,
						'SINGLE',
						$bInputDisabled,
						1,
						true,
						'UTF-8',
						[],
						'',
						[],
						[],
						[]
					);

					$basketItemView->getItemMarkerSubpartArrays(
						$itemFrameWork,
						$itemTableArray[$this->type]->getFuncTablename(),
						$row,
						'SINGLE',
						$viewTagArray,
						true,
						[],
						$markerArray,
						$subpartArray,
						$wrappedSubpartArray
					);

					$itemTableViewArray[$this->type]->getItemMarkerSubpartArrays(
						$itemFrameWork,
						'tt_products',
						$row,
						$markerArray,
						$subpartArray,
						$wrappedSubpartArray,
						$viewTagArray,
						[],
						[],
						'SINGLE',
						$basketExtra,
						$basketRecs,
						1
					);
					$currPriceMarkerArray = [];
					$itemTableViewArray[$this->type]->getCurrentPriceMarkerArray(
						$currPriceMarkerArray,
						'',
						$itemTableArray['product']->getTablename(),
						$prodRow,
						$itemTableArray['article']->getTablename(),
						$prodVariantRow,
						'',
						'SINGLE',
						$basketExtra,
						$basketRecs,
						$bIsGift,
						false
					);
					$markerArray = array_merge($markerArray, $currPriceMarkerArray);
				}
				$linkMemoConf = [];
				if (
					isset($linkConfArray) &&
					is_array($linkConfArray) &&
					isset($linkConfArray['FORM_MEMO.'])
				) {
					$linkMemoConf = $linkConfArray['FORM_MEMO.'];
				}

				$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'];
                $markerArray['###FORM_MEMO###'] = htmlspecialchars(
                    FrontendUtility::getTypoLink_URL(
                        $cObj,
                        $pidMemo,
                        $urlObj->getLinkParams(
                            $excludeList,
                            [],
                            true,
                            $bUseBackPid,
                            0,
                            $itemTableViewArray[$this->type]->getPivar()
                        ),
                        '',
                        $linkMemoConf
                    )
                );

				$markerArray = $urlObj->addURLMarkers(
					$pid,
					$markerArray,
					$addQueryString,
					$excludeList,
					$bUseBackPid,
					0
				); // Applied it here also...

				$queryPrevPrefix = '';
				$queryNextPrefix = '';
				$prevOrderby = '';
				$nextOrderby = '';
				$bDefaultOrder = true;

				if ($this->conf['orderByItemNumberSg']) {
					$itemnumberField = $itemTableArray[$this->type]->fieldArray['itemnumber'];
					$queryPrevPrefix = $itemnumberField . ' < ' .
						$GLOBALS['TYPO3_DB']->fullQuoteStr(
							$origRow[$itemnumberField],
							$tablename
						);
					$queryNextPrefix = $itemnumberField . ' > ' .
						$GLOBALS['TYPO3_DB']->fullQuoteStr(
							$origRow[$itemnumberField],
							$tablename
						);
					$prevOrderby = $itemnumberField . ' DESC';
					$nextOrderby = $itemnumberField . ' ASC';
					$bDefaultOrder = false;
				} else {
					if(
						isset($itemTableConf[$this->type]) &&
						is_array($itemTableConf[$this->type]) &&
						isset($itemTableConf[$this->type]['orderBy'])
					) {
						$orderByFieldArray =
							GeneralUtility::trimExplode(
								',',
								$itemTableConf[$this->type]['orderBy']
							);
						$count = count($orderByFieldArray);

						if ($count) {
							$bDefaultOrder = false;
							$queryPrevPrefixArray = [];
							$queryNextPrefixArray = [];
							$prevOrderbyArray = [];
							$nextOrderbyArray = [];
							$limitArray = [];

							foreach($orderByFieldArray as $i => $orderByFieldLine) {
								$bIsDesc = (stripos($orderByFieldLine, 'DESC') !== false);
								$bIsLast = ($i == $count - 1);
								$orderByField = str_ireplace('ASC', '', $orderByFieldLine);
								$orderByField = trim(str_ireplace('DESC', '', $orderByField));
								$comparatorPrev = ($bIsDesc ? '>' : '<');
								$comparatorNext = ($bIsDesc ? '<' : '>');
								$comparand = $GLOBALS['TYPO3_DB']->fullQuoteStr($origRow[$orderByField], $tablename);

								$newPrevPrevix = $orderByField . ' '. $comparatorPrev . ' ' . $comparand;
								$newNextPrevix = $orderByField . ' ' . $comparatorNext . ' ' . $comparand;

								$ascOperatorPrev = ($bIsDesc ? 'ASC' : 'DESC');
								$ascOperatorNext = ($bIsDesc ? 'DESC' : 'ASC');
								$prevOrderbyArray[] = $orderByField . ' ' . $ascOperatorPrev;
								$nextOrderbyArray[] = $orderByField . ' ' . $ascOperatorNext;

								if ($bIsLast) {
									$lastPrevPrevix = implode(' AND ',$limitArray) . (count($limitArray) > 0 ? ' AND ' : '') . $newPrevPrevix;
									$lastNextPrevix = implode(' AND ',$limitArray) . (count($limitArray) > 0 ? ' AND ' : '') .  $newNextPrevix;
								} else {
									$limitArray[] = $orderByField . '=' . $comparand;
									$queryPrevPrefixArray[] = $newPrevPrevix;
									$queryNextPrefixArray[] = $newNextPrevix;
								}
							}
							$queryNextPrefix = '(' . implode(' AND ', $queryNextPrefixArray) . (count($queryNextPrefixArray) > 0 ? ' OR ' : '') . $lastNextPrevix . ')';
							$queryPrevPrefix = '(' . implode(' AND ', $queryPrevPrefixArray) . (count($queryNextPrefixArray) > 0 ? ' OR ' : '') . $lastPrevPrevix . ')';
							$prevOrderby = implode(',', $prevOrderbyArray);
							$nextOrderby = implode(',', $nextOrderbyArray);
						}
					}
				}
				if ($bDefaultOrder) {
					$queryPrevPrefix = 'uid < ' . intval($this->uid);
					$queryNextPrefix = 'uid > ' . intval($this->uid);

					$prevOrderby = 'uid DESC';
					$nextOrderby = 'uid ASC';
				}

				$prevOrderby = $itemTableArray[$this->type]->getTableObj()->transformOrderby($prevOrderby);
				$nextOrderby = $itemTableArray[$this->type]->getTableObj()->transformOrderby($nextOrderby);
				$whereFilter = '';
				if (
					isset($itemTableConf[$this->type]['filter.']) &&
					isset($itemTableConf[$this->type]['filter.']['regexp.'])
				) {
					if (
                        isset($itemTableConf[$this->type]['filter.']['regexp.']['field.']) &&
                        is_array($itemTableConf[$this->type]['filter.']['regexp.']['field.'])
                    ) {
						foreach ($itemTableConf[$this->type]['filter.']['field.'] as $field => $value) {
							$whereFilter .= ' AND ' . $field . ' REGEXP ' .
								$GLOBALS['TYPO3_DB']->fullQuoteStr(
									quotemeta($value),
									$itemTableArray[$this->type]->getTableObj()->name
								);
						}
					}
				}
				$queryprev = '';
				if ($wherePid != '') {
					$wherePid = ' AND ' . $wherePid;
				}

				$wherestock = (
					(
						$this->conf['showNotinStock'] ||
						!isset($GLOBALS['TCA'][$itemTableArray[$this->type]->getTableObj()->name]['columns']['inStock']) ||
						!is_array($GLOBALS['TCA'][$itemTableArray[$this->type]->getTableObj()->name]['columns']['inStock'])
					) ?
						'' :
						' AND (inStock <> 0) '
                ) . $whereFilter;
				$queryprev = $queryPrevPrefix . $whereCat . $wherePid . $wherestock . $itemTableArray[$this->type]->getTableObj()->enableFields();

				$resprev =
					$itemTableArray[$this->type]->getTableObj()->exec_SELECTquery(
						'*',
						$queryprev,
						'',
						$GLOBALS['TYPO3_DB']->stripOrderBy($prevOrderby),
						'1',
						$categoryJoin
					);

				if ($rowprev = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resprev) ) {
					$addQueryString=[];
					$addQueryString[$this->type] = $rowprev['uid'];

					if ($bUseBackPid) {
						$addQueryString['backPID'] = $backPID;
					} else if ($cat) {
						$addQueryString[$viewCatViewTable->getPivar()] = $cat;
					}
                    $prevUrl = FrontendUtility::getTypoLink_URL(
                        $cObj,
                        $GLOBALS['TSFE']->id,
                        $urlObj->getLinkParams(
                            $excludeList,
                            $addQueryString,
                            true,
                            $bUseBackPid,
                            0,
                            $itemTableViewArray[$this->type]->getPivar(),
                            $viewCatViewTable->getPivar()
                        ),
                        '',
                        [
                            'useCacheHash' => true
                        ]
                    );

					$wrappedSubpartArray['###LINK_PREV_SINGLE###'] =
						array(
							'<a href="' . htmlspecialchars($prevUrl) . '">',
							'</a>'
						);
				} else {
					$subpartArray['###LINK_PREV_SINGLE###'] = '';
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($resprev);
				$querynext = $queryNextPrefix . $whereCat . $wherePid . $wherestock . $itemTableArray[$this->type]->getTableObj()->enableFields();
				// $resnext = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $querynext, $nextOrderby);
				$resnext =
					$itemTableArray[$this->type]->getTableObj()->exec_SELECTquery(
						'*',
						$querynext,
						'',
						$GLOBALS['TYPO3_DB']->stripOrderBy($nextOrderby),
						'1',
						$categoryJoin
					);

	// TODO: remove the SQL queries if not LINK_NEXT markers are in the template subpart
				if ($rownext = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resnext) ) {
					$addQueryString=[];
					$addQueryString[$this->type] = $rownext['uid'];
					if ($bUseBackPid) {
						$addQueryString['backPID'] = $backPID;
					} else if ($cat) {
						$addQueryString[$viewCatViewTable->getPivar()] = $cat;
					}
                    $nextUrl = FrontendUtility::getTypoLink_URL(
                        $cObj,
                        $GLOBALS['TSFE']->id,
                        $urlObj->getLinkParams(
                            $excludeList,
                            $addQueryString,
                            true,
                            $bUseBackPid,
                            0,
                            $itemTableViewArray[$this->type]->getPivar(),
                            $viewCatViewTable->getPivar()
                        ),
                        '',
                        [
                            'useCacheHash' => true
                        ]
                    );
					$wrappedSubpartArray['###LINK_NEXT_SINGLE###'] =
						[
							'<a href="' . htmlspecialchars($nextUrl) . '">',
							'</a>'
						];
				} else {
					$subpartArray['###LINK_NEXT_SINGLE###'] = '';
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($resnext);

				if ($this->type == 'product') {
					$itemTableViewArray[$this->type]->getVariant()->removeEmptyMarkerSubpartArray(
						$markerArray,
						$subpartArray,
						$wrappedSubpartArray,
						$row,
						$this->conf,
						$itemTableArray[$this->type]->hasAdditional($row, 'isSingle'),
						!$itemTableArray[$this->type]->hasAdditional($row, 'noGiftService')
					);
				}

				$relatedListView = GeneralUtility::makeInstance('tx_ttproducts_relatedlist_view');
				$relatedListView->init(
					$this->pidListObj->getPidlist(),
					$this->pidListObj->getRecursive()
				);

				$listMarkerArray = $relatedListView->getListMarkerArray(
					'SINGLE',
					$templateCode,
					$viewTagArray,
					$itemTableArray[$this->type]->getFuncTablename(),
					$this->uid,
					$this->uidArray,
					$prodRow,
                    true,
                    $multiOrderArray = [],
					$useArticles,
					$pageAsCategory,
					$this->pid,
					$errorCode
				);

				$listUncachedMarkerArray =
					$relatedListView->getListUncachedMarkerArray(
						$this->uid,
						$conf,
						$funcTablename,
						$viewTagArray
					);

				if (
					$this->type == 'product' &&
					$listMarkerArray !== false &&
					is_array($listMarkerArray) &&
					!empty($this->uidArray['article'])
				) {
					$uid = $this->uidArray['article'];
					$listArticleMarkerArray =
                        $relatedListView->getListMarkerArray(
                            'SINGLE',
                            $templateCode,
                            $articleViewTagArray,
                            $itemTableArray['article']->getFuncTablename(),
                            $uid,
                            $this->uidArray,
                            $prodRow,
                            true,
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
					$quantityMarkerArray = [];

					foreach ($listMarkerArray as $marker => $markerValue) {
						$markerValue = $parser->substituteMarkerArray($markerValue, $markerArray);
						$markerArray[$marker] = $markerValue;
					}
				}

				$jsMarkerArray = [];
				$javaScriptMarker->getMarkerArray(
					$jsMarkerArray,
					$markerArray,
					$cObj
				);

				$tabulatorMarkerArray = [];
				FrontendUtility::addTab(
					$templateCode,
					$tabulatorMarkerArray,
					$subpartArray,
					$wrappedSubpartArray,
					'',
					'',
					''
				);

				$markerArray = array_merge($categoryMarkerArray, $jsMarkerArray, $tabulatorMarkerArray, $listUncachedMarkerArray, $markerArray);
				$hiddenText = '';
				$markerArray['###HIDDENFIELDS###'] = $hiddenText; // TODO

				if (isset($this->conf['id_shop'])) {
				// edit jf begin
				// Rootline bis Shop-Root holen
				// Breadcrumb aufbauen
				// Seiten <title> aendern
				
                    // Hole rootline, ausgehend von Kategorie des aktuellen Produktes
                    // Speichere uids bis Shop-Root
                    $breadcrumbArray = [];
                    $rootlineArray = [];
                    $rootlineArray[] = $row['title'];
                    $rootline = [];
                    $parent = $row['pid'];
                    do {
                        $res_parent =
                            $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                                'uid,pid,title',
                                'pages',
                                'uid=' . $parent . ' AND hidden=0 AND deleted=0'
                            );
                        $row_parent = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_parent);
                        $GLOBALS['TYPO3_DB']->sql_free_result($res_parent);
                        $parent = $row_parent['pid'];
                        $rootlineArray[] = $row_parent['title'];
                        $breadcrumbArray[] = FrontendUtility::getTypoLink(
                            $cObj,
                            $row_parent['title'],
                            $row_parent['uid'],
                            '',
                            []
                        );
                    } while($row_parent['uid'] != $this->conf['id_shop']);
                    $markerArray['###LINK_BACK2LIST###'] = implode(' &laquo; ', array_reverse($breadcrumbArray));
                    // edit jf end
                }

				// TODO: Bug #44270
                $markerArray = $markerObj->reduceMarkerArray($itemFrameWork, $markerArray);

					// Substitute
				$content = tx_div2007_core::substituteMarkerArrayCached(
					$itemFrameWork,
					$markerArray,
					$subpartArray,
					$wrappedSubpartArray
				);

				if ($content == '') {
					$errorCode[0] = 'internal_error';
					$errorCode[1] = 'TTP_3';
					$errorCode[2] = 'TYPO3 function';
				}
			}
		} else {
			$errorCode[0] = 'wrong_parameter';
			$errorCode[1] = ($this->type ? $this->type : 'product');
			$errorCode[2] = intval($this->uidArray[$this->type] ?? 0);
			$errorCode[3] = $this->pidListObj->getPidlist();
		}
		return $content;
	} // printView
}
