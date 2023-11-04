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
 * Part of the tt_products (Shopping System) extension.
 *
 * product list view functions
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use JambageCom\Div2007\Utility\BrowserUtility;
use JambageCom\Div2007\Utility\FrontendUtility;


use JambageCom\TtProducts\Api\PluginApi;
use JambageCom\TtProducts\Model\Field\FieldInterface;


class tx_ttproducts_list_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $pid; // pid where to go
	public $uidArray;
	public $pidListObj;


	public function init (
		$pid,
		$uidArray,
		$pid_list,
		$recursive
	) {
		$this->pid = $pid;
		$this->uidArray = $uidArray;

		$this->pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
		$this->pidListObj->applyRecursive($recursive, $pid_list, true);
		$this->pidListObj->setPageArray();
	}

	public function finishHTMLRow (
		&$cssConf,
		&$iColCount,
		$tableRowOpen,
		$displayColumns
	) {
		$itemsOut = '';
		if ($tableRowOpen) {
			$iColCount++;
            if (isset($cssConf['itemSingleWrap'])) {
                $itemSingleWrapArray = GeneralUtility::trimExplode('|', $cssConf['itemSingleWrap']);
                $bIsTable = (strpos($itemSingleWrapArray['0'], 'td') != false);
                if ($bIsTable) {
                    // fill up with empty fields
                    while ($iColCount <= $displayColumns) {
                        $itemsOut .= $itemSingleWrapArray['0'] . $itemSingleWrapArray['1'];
                        $iColCount++;
                    }
                }
            }
            if (isset($cssConf['itemRowWrap'])) {
                $itemRowWrapArray = GeneralUtility::trimExplode('|', $cssConf['itemRowWrap']);
                $itemsOut .= ($tableRowOpen ? ($itemRowWrapArray['1'] ?? '')  : '');
            }
        }
        $iColCount = 0;

        return $itemsOut;
    } // finishHTMLRow


	public function advanceCategory (
		&$categoryAndItemsFrameWork,
		&$itemListOut,
		&$categoryOut,
		$itemListSubpart,
		$oldFormCount,
		&$formCount
	) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$subpartArray = [];
		$subpartArray['###ITEM_CATEGORY###'] = $categoryOut;
		$subpartArray[$itemListSubpart] = $itemListOut;
		$rc = $templateService->substituteMarkerArrayCached($categoryAndItemsFrameWork, [], $subpartArray);
		if ($formCount == $oldFormCount) {
			$formCount++; // next form must have another name
		}
		$categoryOut = '';
		$itemListOut = '';	// Clear the item-code var
		return $rc;
	}


	public function advanceProduct (
		&$productAndItemsFrameWork,
		&$productFrameWork,
		&$itemListOut,
		&$productMarkerArray,
		&$categoryMarkerArray
	) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$cObj = FrontendUtility::getContentObjectRenderer();
		$markerArray = array_merge($productMarkerArray, $categoryMarkerArray);
		$productOut = $templateService->substituteMarkerArray($productFrameWork, $markerArray);
		$subpartArray = [];
		$subpartArray['###ITEM_PRODUCT###'] = $productOut;
		$subpartArray['###ITEM_LIST###'] = $itemListOut;
		$rc = $templateService->substituteMarkerArrayCached($productAndItemsFrameWork, [], $subpartArray);
		$categoryOut = '';
		$itemListOut = '';	// Clear the item-code var

		return $rc;
	}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$queryString: ...
	 * @return	[type]		...
	 */
	public function getSearchParams (&$queryString) {
		$sword = GeneralUtility::_GP('sword');

		if (!isset($sword)) {
			$sword = GeneralUtility::_GP('swords');
		}

		if ($sword) {
			$sword = rawurlencode($sword);
		}

		if (!isset($sword)) {
			$piVars = tx_ttproducts_model_control::getPiVars();
			$sword = $piVars['sword'] ?? null;
		}

		if ($sword) {
			$queryString['sword'] = $sword;
		}
	}


	protected function getCategories (
		$catObj,
		$catArray,
		$rootCatArray,
		&$rootLineArray,
		$cat,
		&$currentCat,
		&$displayCat
	) {
		if (in_array($cat, $catArray)) {
			$currentCat = $cat;
		} else {
			$currentCat = current($catArray);
		}

		foreach ($catArray as $displayCat) {
			$totalRootLineArray = $catObj->getLineArray($currentCat, [0]);

			if (($displayCat != $currentCat) && !in_array($displayCat, $totalRootLineArray)) {
				break;
			}
		}
		$rootLineArray = $catObj->getLineArray($currentCat, $rootCatArray);
	}


	protected function getDisplayInfo (
		$displayConf,
		$type,
		$depth,
		$bLast
	) {
		$result = '';
		if (is_array($displayConf[$type])) {
			foreach ($displayConf[$type] as $k => $val) {
				if (
					MathUtility::canBeInterpretedAsInteger($k) &&
					$depth >= $k
				) {
					$result = $val;
				} else {
					break;
				}
			}

			if (isset($displayConf[$type]['last']) && $bLast) {
				$result = $displayConf[$type]['last'];
			}
		}
		return $result;
	}


	public function getBrowserConf ($tableConfArray) {

		$browserConf = '';
		if (isset($tableConfArray['view.']) && $tableConfArray['view.']['browser'] == 'div2007') {
			if (isset($tableConfArray['view.']['browser.'])) {
				$browserConf = $tableConfArray['view.']['browser.'];
			} else {
				$browserConf = [];
			}
		}
		return $browserConf;
	}

	public function getBrowserObj(
		$conf,
		$browserConf,
		$productsCount,
		$limit,
		$maxPages
	) {
		$bShowFirstLast = true;
		$dontLinkActivePage = 0;
		$parameterApi = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\ParameterApi::class);

		if (
			isset($browserConf) &&
			is_array($browserConf)
		) {
			if (isset($browserConf['showFirstLast'])) {
                $bShowFirstLast = $browserConf['showFirstLast'];
            }
			if (isset($browserConf['dontLinkActivePage'])) {
                $dontLinkActivePage = $browserConf['dontLinkActivePage'];
            }
		}

		$pagefloat = 0;
		$imageArray = [];
		$imageActiveArray = [];
		$piVars = $parameterApi->getPiVars();
		$browseObj = GeneralUtility::makeInstance(\JambageCom\Div2007\Base\BrowserBase::class);
		$browseObj->init(
			$conf,
			$piVars,
			[],
			false,	// no autocache used yet
			tx_ttproducts_control_pibase::$pi_USER_INT_obj,
			$productsCount,
			$limit,
			$maxPages,
			$bShowFirstLast,
			false,
			$pagefloat,
			$imageArray,
			$imageActiveArray,
			$dontLinkActivePage
		);

		return $browseObj;
	}

	public function getBrowserMarkers (
		$browseObj,
		$browserConf,
		$t,
		$addQueryString,
		$productsCount,
		$more,
		$limit,
		$begin_at,
		$useCacheHash,
		&$markerArray,
		&$subpartArray,
		&$wrappedSubpartArray
	) {
        $cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$parameterApi = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\ParameterApi::class);

		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$pointerParam = $parameterApi->getPointerPiVar('LIST');
		$splitMark = md5(microtime());
		$prefixId = $parameterApi->getPrefixId();
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);

		if ($more) {
			$next = ($begin_at + $limit >= $productsCount) ? ($productsCount >= $limit ? $productsCount - $limit : 0) : $begin_at + $limit;
            $next = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($next, 0);

			$addQueryString[$pointerParam] = intval($next / $limit);
			$this->getSearchParams($addQueryString);
			
			$tempUrl =
				BrowserUtility::linkTPKeepCtrlVars(
					$browseObj,
					$cObj,
					$prefixId,
					$splitMark,
					$addQueryString,
					$useCacheHash
				);
			$wrappedSubpartArray['###LINK_NEXT###'] = explode($splitMark, $tempUrl);
		} else {
			$subpartArray['###LINK_NEXT###'] = '';
		}

		if ($begin_at) {
			$prev = ($begin_at - $limit < 0) ? 0 : $begin_at - $limit;
            $prev = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($prev, 0);
			$addQueryString[$pointerParam] = intval($prev / $limit);
			$this->getSearchParams($addQueryString);
			$tempUrl =
				BrowserUtility::linkTPKeepCtrlVars(
					$browseObj,
					$cObj,
					$prefixId,
					$splitMark,
					$addQueryString,
					$useCacheHash
				);

// 			$tempUrl = $pibaseObj->pi_linkTP_keepPIvars($splitMark,$addQueryString,$useCacheHash,0);
			$wrappedSubpartArray['###LINK_PREV###'] = explode($splitMark, $tempUrl);
		} else {
			$subpartArray['###LINK_PREV###'] = '';
		}

		$markerArray['###BROWSE_LINKS###'] = '';

		if ($productsCount > $limit) { // there is more than one page, so let's browse

			$t['browseFrameWork'] = $templateService->getSubpart(
				$t['listFrameWork'],
				$subpartmarkerObj->spMarker('###LINK_BROWSE###')
			);
			if ($t['browseFrameWork'] != '') {

				$wrappedSubpartArray['###LINK_BROWSE###'] = ['', ''];

				if (is_array($browserConf)) {
					$addQueryString = [];
					$this->getSearchParams($addQueryString);

					$markerArray['###BROWSE_LINKS###'] =
						BrowserUtility::render(
							$browseObj,
							$languageObj,
							$cObj,
							$prefixId,
							true,
							1,
							'',
							$browserConf,
							$pointerParam,
							true,
							$addQueryString
						);
				} else {
					for ($i = 0 ; $i < ($productsCount / $limit); $i++) {
						if (($begin_at >= $i * $limit) && ($begin_at < $i * $limit + $limit)) {
							$markerArray['###BROWSE_LINKS###'] .= ' <em>' . (string) ($i + 1) . '</em> ';
							//	you may use this if you want to link to the current page also
							//
						} else {
							$addQueryString[$pointerParam] = (string) ($i);
							$tempUrl =
								BrowserUtility::linkTPKeepCtrlVars(
									$browseObj,
									$cObj,
									$prefixId,
									(string)($i + 1) . ' ',
									$addQueryString,
									$useCacheHash
								);

							$markerArray['###BROWSE_LINKS###'] .= $tempUrl;
						}
					}
				}
			}
			// ###CURRENT_PAGE### of ###TOTAL_PAGES###
			$markerArray['###CURRENT_PAGE###'] = intval($begin_at / $limit + 1);
			$markerArray['###TOTAL_PAGES###'] = ceil($productsCount / $limit);
		} else {
			$subpartArray['###LINK_BROWSE###'] = '';
			$markerArray['###CURRENT_PAGE###'] = '1';
			$markerArray['###TOTAL_PAGES###'] = '1';
		}
	}


	// returns the products list view
	public function printView (
		$templateCode,
		$theCode,
		$functablename,
		$allowedItems,
		$additionalPages,
		$hiddenFields,
		&$error_code,
		$templateArea = 'ITEM_LIST_TEMPLATE',
		$pageAsCategory,
		$basketExtra,
		$basketRecs,
		$mergeRow = [],
		$calllevel = 0,
		$callFunctableArray = [],
		$parentDataArray = [],
		$parentProductRow = [], // Download
		$parentFuncTablename = '', // Download
		$parentRows = [], // Download
		$notOverwritePriceIfSet = true, // Download
		$multiOrderArray = [],
		$productRowArray = [],
		$bEditableVariants = true
	) {
		if (!empty($error_code)) {
			return '';
		}

		$basketExt = tx_ttproducts_control_basket::getBasketExt();
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnfObj->getConf();
		$config = $cnfObj->getConfig();
		$cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$backPid = 0;
		$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');

		$whereCat = '';
        $whereProduct = '';
		$itemArray = [];

		$viewedCodeArray = ['LISTAFFORDABLE', 'LISTVIEWEDITEMS', 'LISTVIEWEDMOST', 'LISTVIEWEDMOSTOTHERS'];
		$bUseCache = true;
		$prefixId = tx_ttproducts_model_control::getPrefixId();
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$javaScriptMarker = GeneralUtility::makeInstance('tx_ttproducts_javascript_marker');
		$urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');

		$contentUid = $cObj->data['uid'] ?? 0;
		$itemTableArray = [];
		$itemTableViewArray = [];
		$currentParentRow = '';
		$externalRowArray = [];
		$bCheckUnusedArticleMarkers = false;

		if (
			$parentFuncTablename != '' &&
			isset($parentRows) &&
			is_array($parentRows)
		) {
			$externalRowArray[$parentFuncTablename] = current($parentRows); // Todo: erweitern, damit die Datensätze mit dem jeweils zugehörenden Vater-Datensatz verbunden werden
		}

		$relatedListView = GeneralUtility::makeInstance('tx_ttproducts_relatedlist_view');
		$relatedListView->init($this->pidListObj->getPidlist(), 0);

		$piVars = tx_ttproducts_model_control::getPiVars();
		$showArticles = false;

		$globalMarkerArray = $markerObj->getGlobalMarkerArray();
		$viewControlConf = $cnfObj->getViewControlConf($theCode);

		if (is_array($viewControlConf) && count($viewControlConf)) {
			if (
				isset($viewControlConf['param.']) &&
				is_array($viewControlConf['param.'])
			) {
				$viewParamConf = $viewControlConf['param.'];
			}

			if (
				isset($viewControlConf['links.']) &&
				is_array($viewControlConf['links.'])
			) {
				$linkConfArray = $viewControlConf['links.'];
			}
		}

		$useBackPid = (isset($viewParamConf) && $viewParamConf['use'] == 'backPID' ? true : false);
		if (PluginApi::isRelatedCode($theCode)) {
			$backPid = $config['backPID']; // stay with the current backPid
		}

		if (strpos($theCode, 'MEMO') === false) {
			$memoViewObj = GeneralUtility::makeInstance('tx_ttproducts_memo_view');

			$memoViewObj->init(
				$theCode,
				$config['pid_list'],
				$conf,
				$conf['useArticles']
			);
		}
		$pidMemo = ($conf['PIDmemo'] ? $conf['PIDmemo'] : $GLOBALS['TSFE']->id);

		$sqlTableArray = [];
		$tableAliasArray = [];
		$sqlTableIndex = 0;
		$headerFieldArray = [];
		$headerTableArray = [];
		$headerTableObjArray = [];
		$content = '';
		$out = '';
		$t = [];
		$childCatArray = [];
		$rootCatArray = [];
		$jsMarkerArray = [];
		$childCatWrap = '';
		$imageWrap = '';
		$linkCat = '';
		$depth = 1;	// TODO
		if ($conf['displayBasketColumns'] == '{$plugin.tt_products.displayBasketColumns}') {
			$conf['displayBasketColumns'] = '1';
		}
		$displayColumns = $conf['displayBasketColumns'];
		$sword = null;
		$htmlSwords = '';

		if ($calllevel == 0) {
			$sword = GeneralUtility::_GP('sword');
			$sword = (isset($sword) ? $sword : GeneralUtility::_GP('swords'));

			if (!isset($sword)) {
				$postVars = GeneralUtility::_POST($prefixId);
				$sword = $postVars['sword'] ?? null;

				if (!isset($sword)) {
					$getVars = GeneralUtility::_GET($prefixId);
					$sword = $getVars['sword'] ?? null;
				}
			}
            if (!empty($sword)) {
                $sword = rawurldecode($sword);
                $htmlSwords = htmlspecialchars($sword);
            }
		}
		$more = 0;	// If set during this loop, the next-item is drawn
		$where = '';

		$formName = 'ShopListForm';
		$formNameArray = [ // TODO all possible CODEs must have their own form names
			'LISTRELATEDPARTIALDOWNLOAD' => 'PartialDownloadForm',
			'LISTRELATEDCOMPLETEDOWNLOAD' => 'CompleteDownloadForm',
            'LISTRELATEDALLDOWNLOAD' => 'AllDownloadForm'
		];

		if (isset($formNameArray[$theCode])) {
			$formName = $formNameArray[$theCode];
		}

		$itemTableView = $tablesObj->get($functablename, true);
		$itemTable = $itemTableView->getModelObj();
		$tablename = $itemTable->getTablename();
		$categoryfunctablename = '';

		if ($itemTable->getType() == 'dam') {
			$categoryfunctablename = 'tx_dam_cat';
		} else {
			if ($functablename == 'tt_products') {
				if (!$pageAsCategory || $pageAsCategory == 1) {
					$categoryfunctablename = 'tt_products_cat';
				} else {
					$categoryfunctablename = 'pages';
				}
			}
		}
		$useCategories = true;
		if ($categoryfunctablename == '') {
			$categoryfunctablename = 'tt_products_cat';
			$useCategories = false;
		}
		$keyFieldArray = $itemTable->getKeyFieldArray($theCode);
		$tableConfArray = [];
		$viewConfArray = [];
		$functableArray = [$functablename, $categoryfunctablename];
		tx_ttproducts_model_control::getTableConfArrays(
			$cObj,
			$functableArray,
			$theCode,
			$tableConfArray,
			$viewConfArray
		);

		$itemTable->initCodeConf($theCode, $tableConfArray[$functablename]);
		$prodAlias = $itemTable->getTableObj()->getAlias();
		$tableAliasArray[$tablename] = $itemTable->getAlias();
		$itemTableArray[$itemTable->getType()] = $itemTable;
		$itemTableViewArray[$itemTable->getType()] = $itemTableView;
		$selectableVariantFieldArray = $itemTable->getVariant()->getSelectableFieldArray();
		$useArticles = $cnfObj->getUseArticles();

		$excludeList = '';
		$pointerParam = tx_ttproducts_model_control::getPointerPiVar('LIST');

		if (
			$itemTable->getType() == 'product' &&
			in_array($useArticles, [1, 3])
		) {
			$articleViewObj = $tablesObj->get('tt_products_articles', true);
			$articleTable = $articleViewObj->getModelObj();
			$itemTableArray['article'] = $articleTable;
			$itemTableViewArray['article'] = $articleViewObj;
		} else if (
			$itemTable->getType() == 'article' ||
			$itemTable->getType() == 'dam' && !empty($conf['productDAMCategoryID']) ||
			$itemTable->getType() == 'download' ||
			$itemTable->getType() == 'fal'
		) {
			$itemTableViewArray['product'] =
				$tablesObj->get(
					'tt_products',
					true
				);
			$itemTableArray['product'] = $itemTableViewArray['product']->getModelObj();
			if ($itemTable->getType() == 'article')  {
				$articleViewObj = $itemTableView;
			}
		}

		if (
			!PluginApi::isRelatedCode($theCode) &&
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('taxajax') &&
			$itemTable->getType() == 'product' &&
			(
				$bEditableVariants ||
				in_array($useArticles, [1, 2, 3])
			)
		) {
			tx_ttproducts_control_product::addAjax(
				$tablesObj,
				$languageObj,
				$theCode,
				$itemTable->getFuncTablename()
			);
		}

		$cssConf = $cnfObj->getCSSConf($itemTable->getFuncTablename(), $theCode);
		$categoryPivar = tx_ttproducts_model_control::getPiVar($categoryfunctablename);

		if ($useCategories) {
			$categoryTableView = $tablesObj->get($categoryfunctablename, true);
			$categoryTable = $categoryTableView->getModelObj();
			$tableConfArray[$categoryfunctablename] = $categoryTable->getTableConf($theCode);
			$catTableConf = $categoryTable->getTableConf($theCode);
			$categoryTable->initCodeConf($theCode, $catTableConf);
			$categoryAnd = tx_ttproducts_model_control::getAndVar($categoryPivar);
		}
		$whereArray = '';
		if (!empty($piVars[tx_ttproducts_model_control::getPiVar($functablename)])) {
            $whereArray = $piVars[tx_ttproducts_model_control::getPiVar($functablename)];
        }

		if (is_array($whereArray)) {
			foreach ($whereArray as $field => $value) {
				if (is_array($value)) {
					foreach ($value as $comparator => $comparand) {
						$sqlOperator = '';
						$comparator = tx_ttproducts_sql::transformComparator($comparator);

						if ($comparand) {
							$tablename = $itemTable->getTableObj()->getName();
							$whereField =
								tx_ttproducts_sql::getWhere4Field(
									$tablename,
									$field,
									$comparator,
									$comparand
								);

							if ($whereField !== false) {

								$where .= $whereField;
							}
						}
					}
				} else {
					$where .= ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $itemTable->getTableObj()->name);
				}
			}
		}
		$flexformArray = \JambageCom\TtProducts\Api\PluginApi::getFlexform();
		$dam_group_by = '';

		if ($itemTable->getType() == 'product') {
			$product_where = \JambageCom\Div2007\Utility\FlexformUtility::get($flexformArray, 'product_where');

			if ($product_where) {
				$product_where = $itemTable->getTableObj()->transformWhere($product_where);
				$where .= ' AND ' . $product_where;
			}
		} else if ($itemTable->getType() == 'dam') {
			$dam_where = \JambageCom\Div2007\Utility\FlexformUtility::get($flexformArray, 'dam_where');
			$dam_group_by = \JambageCom\Div2007\Utility\FlexformUtility::get($flexformArray, 'dam_group_by');
			$dam_join_tables = \JambageCom\Div2007\Utility\FlexformUtility::get($flexformArray, 'dam_join_tables');
			$damJoinTableArray = GeneralUtility::trimExplode(',', $dam_join_tables);

			if ($dam_where) {
				$dam_where = $itemTable->getTableObj()->transformWhere($dam_where);
				$where .= ' AND ' . $dam_where;
			}
		}

		// if parameter 'newitemdays' is specified, only new items from the last X days are displayed
		$newitemdays = '';
		if (!empty($piVars['newitemdays'])) {
            $newitemdays = $piVars['newitemdays'];
        }
		$newitemdays = ($newitemdays ? $newitemdays : GeneralUtility::_GP('newitemdays'));

		if (
			($newitemdays || $theCode == 'LISTNEWITEMS') &&
			is_array($tableConfArray[$functablename]) &&
			is_array($tableConfArray[$functablename]['controlFields.'])
		) {
			if (!$newitemdays) {
				$newitemdays = $conf['newItemDays'];
			}
			$temptime = time() - 86400 * intval(trim($newitemdays));
			$timeFieldArray = GeneralUtility::trimExplode(',', $tableConfArray[$functablename]['controlFields.']['newItemDays']);
			$whereTimeFieldArray = [];
			foreach ($timeFieldArray as $k => $value) {
				$whereTimeFieldArray[] = $tableAliasArray[$tablename] . '.' . $value . ' >= ' . $temptime;
			}
			if (count($whereTimeFieldArray)) {
				$where .= ' AND (' . implode(' OR ', $whereTimeFieldArray) . ')';
			}
		}

		if (
			$useCategories &&
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] != '2'
		) {
			$cat = $categoryTable->getParamDefault($theCode, $piVars[$categoryPivar] ?? '');
		}
		$searchboxWhere = '';
		$searchVars = [];
		if (isset($piVars[tx_ttproducts_model_control::getSearchboxVar()])) {
            $searchVars = $piVars[tx_ttproducts_model_control::getSearchboxVar()];
        }
		$bUseSearchboxArray = [];
		$latest = '';

		if (
            !empty($searchVars) &&
            (
                isset($searchVars['local']) ||
                isset($searchVars['uid'])
            )
        ) {
			tx_ttproducts_model_control::getSearchInfo(
				$cObj,
				$searchVars,
				$functablename,
				$tablename,
				$searchboxWhere,
				$bUseSearchboxArray,
				$sqlTableIndex,
				$latest
			);
		}
		$pageViewObj = $tablesObj->get('pages', 1);
		$pid =
			$pageViewObj->getModelObj()->getParamDefault(
				$theCode,
				$piVars[$pageViewObj->piVar] ?? ''
			);

		$addressUid = $piVars['a'] ?? '';

		$hookVar = 'allowedItems';
		if (
            $hookVar &&
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar]) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar])
        ) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this);
				}
				if (method_exists($hookObj, 'getAllowedItems')) {
					$tmpArray =
						$hookObj->getAllowedItems(
							$allowedItems,
							$itemTable,
							$theCode,
							$additionalPages,
							$pageAsCategory
						);
				}
			}
		}
		if ($allowedItems != '') { // formerly: !$tableConfArray[$functablename]['orderBy'] &&
			$tableConfArray[$functablename]['orderBy'] = 'FIELD(' . $prodAlias . '.uid, ' . $allowedItems . ')';
		}

		$whereAddress = '';
		$addrTablename = $conf['table.']['address'];
		if (
			(
				$addrTablename == 'tx_party_addresses' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(PARTY_EXT) ||
				$addrTablename == 'tx_partner_main' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(PARTNER_EXT) ||
				$addrTablename == 'tt_address' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(TT_ADDRESS_EXT)
			)
			&& $addressUid && $itemTable->fieldArray['address']
		) {
			$addressViewObj = $tablesObj->get('address', true);
			$addressObj = $addressViewObj->getModelObj();

			if (intval($addressUid)) {
				$whereAddress = ' AND (' . $itemTable->fieldArray['address'] . '=' . intval($addressUid) . ')';
			} else if ($addressObj->fieldArray['name']) {
				$addressRow =
					$addressObj->get(
						'0',
						0,
						false,
						$addressObj->fieldArray['name'] . '=' .
						$GLOBALS['TYPO3_DB']->fullQuoteStr(
							$addressUid,
							$addressObj->getTablename(),
							'',
							'uid,' . $addressObj->fieldArray['name']
						)
					);

				$addressText = $addressRow[$addressObj->fieldArray['name']];
				$whereAddress = ' AND (' . $itemTable->fieldArray['address'] . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($addressText, $addressObj->getTablename()) . ')';
			}
			$where .= $whereAddress;
		}

		if ($whereAddress == '') { // do not mix address with category filter
            $bForceCatParams = false;

			if (isset($tableConfArray[$functablename]['filter.']) && is_array($tableConfArray[$functablename]['filter.']) &&
				isset($tableConfArray[$functablename]['filter.']['param.']) && is_array($tableConfArray[$functablename]['filter.']['param.']) &&
				$tableConfArray[$functablename]['filter.']['param.']['cat'] == 'gp') {
				$bForceCatParams = true;
			}

			if (
				$allowedItems == '' &&
				!in_array($theCode, $viewedCodeArray) &&
				$calllevel == 0 ||

				$bForceCatParams
			) {
				$whereCat =
					$itemTable->addWhereCat(
						$categoryTable,
						$theCode,
						$cat,
						$categoryAnd,
						$this->pidListObj->getPidlist(),
						true
					);
			}

			if ($whereCat == '' && ($allowedItems == '' || $bForceCatParams)) {
				$neededParams = $itemTable->getNeededUrlParams($functablename, $theCode);
				$needArray = GeneralUtility::trimExplode(',', $neededParams);
				$bListStartEmpty = false;
				foreach($needArray as $k => $param) {
					if ($param && !isset($piVars[$param])) {
						$bListStartEmpty = true;
						break;
					}
				}
				if ($bListStartEmpty) {
					$allowedItems = '0';	// not possible uid
				}
			}

			if ($searchboxWhere != '') {
				if ($bUseSearchboxArray[$categoryfunctablename]) {
					$whereCat .= ' AND ' . $searchboxWhere;
				} else {
					$whereProduct = ' AND ' . $searchboxWhere;
				}
			}
			$where .= $whereCat . $whereProduct;
		}

		if (
            isset($conf['form.'][$theCode . '.']) &&
            isset($conf['form.'][$theCode . '.']['data.']) &&
            isset($conf['form.'][$theCode . '.']['data.']['name'])
        ) {
			$formNameSetup = $conf['form.'][$theCode . '.']['data.']['name'];
		}
		$formName = (!empty($formNameSetup) ? $formNameSetup : $formName);

		if ($htmlSwords && (in_array($theCode, ['LIST', 'SEARCH']))) {
				//extend standard search fields with user setup
			$searchFieldList =
				trim($conf['stdSearchFieldExt']) ?
					implode(',', array_unique(GeneralUtility::trimExplode(',', $conf['stdSearchFieldExt']))) :
					'title,note,' . $tablesObj->get('tt_products')->fieldArray['itemnumber'];

			$where .= $tablesObj->get('tt_products')->searchWhere($searchFieldList, trim($htmlSwords), $theCode);
		}

		if (isset($piVars['search']) && is_array($piVars['search'])) {
			$searchWhere = '';
			foreach ($piVars['search'] as $field => $value) {
				if (isset($GLOBALS['TCA'][$tablename]['columns'][$field])) {
					$searchWhere .= ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $tablename);
				}
			}
			$where .= $searchWhere;
		}

		switch ($theCode) {
			case 'LISTAFFORDABLE':
				$formName = 'ListAffordable';
			break;
			case 'LISTARTICLES':
			case 'LISTRELATEDARTICLES':
				$formName = 'ListArticlesForm';
			break;
			case 'LISTDAM':
			case 'MEMODAM':
				if ($theCode == 'LISTDAM') {
					$formName = 'ListDAMForm';
					$templateArea = 'ITEM_LISTDAM_TEMPLATE' . $templateObj->getTemplateSuffix();
				} else if ($theCode == 'MEMODAM') {
					$formName = 'ListMemoDAMForm';
					$bUseCache = false;
				}
			break;
			case 'LISTGIFTS':
				$formName = 'GiftForm';
				$where .= ' AND ' . ($conf['whereGift'] ? $conf['whereGift'] : '1=0');
				$templateArea = 'ITEM_LIST_GIFTS_TEMPLATE' . $templateObj->getTemplateSuffix();
			break;
			case 'LISTHIGHLIGHTS':
				$formName = 'ListHighlightsForm';
				$where .= ' AND highlight';
			break;
			case 'LISTNEWITEMS':
				$formName = 'ListNewItemsForm';
			break;
			case 'LISTOFFERS':
				$formName = 'ListOffersForm';
				$where .= ' AND offer';
			break;
			case 'LISTORDERED':
				$formName = 'ListOrderedForm';
			break;
			case 'LISTVIEWEDITEMS':
				$formName = 'ListViewedItemsForm';
			break;
			case 'LISTVIEWEDMOST':
				$formName = 'ListViewedMost';
			break;
			case 'LISTVIEWEDMOSTOTHERS':
				$formName = 'ListViewedMostOthers';
			break;
			case 'MEMO':
				$formName = 'ListMemoForm';
				$bUseCache = false;
			break;
			case 'SEARCH':
				$formName = 'ShopSearchForm';
				$searchTemplateArea = 'ITEM_SEARCH';
					// Get search subpart
				$t['search'] =
					$templateService->getSubpart(
						$templateCode,
						$subpartmarkerObj->spMarker('###' . $searchTemplateArea . '###' . $templateObj->getTemplateSuffix())
					);

				if (!$t['search']) {
					$error_code[0] = 'no_subtemplate';
					$error_code[1] = '###' . $searchTemplateArea . '###';
					$error_code[2] = $templateObj->getTemplateFile();

					return '';
				}

					// Substitute a few markers
				$out = $t['search'];
				$tmpPid = ($conf['PIDsearch'] ? $conf['PIDsearch'] : $GLOBALS['TSFE']->id);
				$addQueryString = [];
				$this->getSearchParams($addQueryString);

				$excludeList = 'sword';

				if (
					isset($viewParamConf) &&
					is_array($viewParamConf) &&
					GeneralUtility::inList($viewParamConf['item'], $categoryPivar)
				) {
					// nothing
				} else {
					$excludeList .= ',' . $prefixId . '[' . $categoryPivar . ']';
				}

				if (
					isset($viewParamConf) &&
					is_array($viewParamConf) &&
					isset($viewParamConf['ignore'])
				) {
					$ignoreArray = GeneralUtility::trimExplode(',', $viewParamConf['ignore']);
					foreach($ignoreArray as $ignoreParam) {
						if ($ignoreParam == 'backPID') {
							$useBackPid = false;
						}
						$excludeList .= ',' . $prefixId . '[' . $ignoreParam . ']';
					}
				}

				$markerArray =
					$urlObj->addURLMarkers(
						$tmpPid,
						[],
						$addQueryString,
						$excludeList,
						$useBackPid,
						$backPid
					);

					// add Global Marker Array
				$markerArray = array_merge($markerArray, $globalMarkerArray);
				$markerArray['###FORM_NAME###'] = $formName . '_' . $contentUid;
				$markerArray['###SWORD###'] = $htmlSwords;
				$markerArray['###SWORD_NAME###'] = $prefixId . '[sword]';
				$markerArray['###SWORDS###'] = $htmlSwords; // for backwards compatibility
				$out = $templateService->substituteMarkerArrayCached($out, $markerArray);
				if ($formName) {
						// Add to content
					$content .= $out;
				}
				$out = '';
				$bUseCache = false; // tt_products must be a USER_INT if the search word has been entered! You must not use a cache for a searched list or single view.
			break;
			default:
				// nothing here
			break;
		}

		if ($useCategories) {
			$currentCat = $categoryTable->getParamDefault($theCode, $piVars[$categoryPivar] ?? '');
			$rootCat = $categoryTable->getRootCat() ?? '';
			$relatedArray = $categoryTable->getRelated($rootCat, $currentCat, $this->pidListObj->getPidlist());	// read only related categories;
			$excludeCat = 0;
			$categoryArray = $categoryTable->getRelationArray($relatedArray, $excludeCat, $rootCat, implode(',', array_keys($relatedArray)));
			$rootCatArray = $categoryTable->getRootArray($rootCat, $categoryArray, $tableConfArray[$functablename]['autoRoot'] ?? 0);

			if ($conf['clickItemsIntoSubmenu']) {
				$childCatArray = $categoryTable->getChildCategoryArray($currentCat);
				if (count($childCatArray)) {
					$templateArea = 'HAS_CHILDS_' . $templateArea;
				}
			}
		}

		$limit = isset($tableConfArray[$functablename]['limit']) ? $tableConfArray[$functablename]['limit'] : $config['limit'];
		$limit = intval($limit);
		$begin_at = 0;

        if ($calllevel == 0) {

			$begin_at = ($piVars[$pointerParam] ?? 0) * $limit;
		}
        $begin_at = MathUtility::forceIntegerInRange($begin_at, 0, 100000);

		if ($theCode == 'SINGLE') {
			$begin_at = ''; // no page browser in single view for related products
		}

		if (
			$theCode != 'SEARCH' ||
			(
				$conf['listViewOnSearch'] == '1' &&
				$theCode == 'SEARCH' &&
				$sword
			)
		) {
			$t['listFrameWork'] = $templateService->getSubpart(
				$templateCode,
				$subpartmarkerObj->spMarker('###' . $templateArea . '###')
			);

			// $templateArea = 'ITEM_LIST_TEMPLATE'
			if (!$t['listFrameWork']) {
				$error_code[0] = 'no_subtemplate';
				$error_code[1] = '###' . $templateArea . '###';
				$error_code[2] = $templateObj->getTemplateFile();

				return $content;
			}

			$checkExpression = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['templateCheck'];
			if (!empty($checkExpression)) {
				$wrongPounds = preg_match_all($checkExpression, $t['listFrameWork'], $matches);

				if ($wrongPounds) {
					$error_code[0] = 'template_invalid_marker_border';
					$error_code[1] = '###' . $templateArea . '###';
					$error_code[2] =  htmlspecialchars(implode('|', $matches['0']));

					return '';
				}
			}

			$addQueryString = $this->uidArray;
			$excludeList = ($theCode == 'SEARCH' ? 'sword' : '');
			$this->getSearchParams($addQueryString);
			$markerArray = [];
			$markerArray['###HIDDENFIELDS###'] = '';
			$markerArray =
				$urlObj->addURLMarkers(
					$this->pid,
					$markerArray,
					$addQueryString,
					$excludeList,
					$useBackPid,
					$backPid
				); // clickIntoBasket

			if (strpos($theCode, 'MEMO') === false) {	// if you link to MEMO from somewhere else, you must not set some parameters for it coming from this list view
				$excludeList = $pointerParam;
			}

			$linkMemoConf = [];
			if (
				isset($linkConfArray) &&
				is_array($linkConfArray) &&
				isset($linkConfArray['FORM_MEMO.'])
			) {
				$linkMemoConf = $linkConfArray['FORM_MEMO.'];
			}

			$markerArray['###FORM_MEMO###'] =
				htmlspecialchars(
					FrontendUtility::getTypoLink_URL(
						$cObj,
						$pidMemo,
						$urlObj->getLinkParams(
							$excludeList,
							[],
							true,
							$useBackPid,
							$backPid,
							$itemTableView->getPivar()
						),
						'',
						$linkMemoConf
					)
				);

            $wrappedSubpartArray = [];
			$urlObj->getWrappedSubpartArray(
				$wrappedSubpartArray,
				[],
				'',
				$useBackPid
			);
			$subpartArray = [];
			$viewTagArray = $markerObj->getAllMarkers($t['listFrameWork']);
			$tablesObj->get('fe_users', true)->getWrappedSubpartArray(
				$viewTagArray,
				$useBackPid,
				$subpartArray,
				$wrappedSubpartArray
			);

			if (is_array($viewConfArray) && count($viewConfArray)) {
				$controlViewObj = GeneralUtility::makeInstance('tx_ttproducts_control_view');
				$controlViewObj->getMarkerArray(
					$markerArray,
					$viewTagArray,
					$tableConfArray
				);
			}

				// add Global Marker Array
			$markerArray = array_merge($markerArray, $globalMarkerArray);

			$t['listFrameWork'] = $templateService->substituteMarkerArrayCached(
				$t['listFrameWork'],
				$markerArray,
				$subpartArray,
				$wrappedSubpartArray
			);

			$t['categoryAndItemsFrameWork'] = $templateService->getSubpart($t['listFrameWork'], '###ITEM_CATEGORY_AND_ITEMS###');
			$t['categoryFrameWork'] = $templateService->getSubpart(
				$t['categoryAndItemsFrameWork'],
				'###ITEM_CATEGORY###'
			);
			if ($itemTable->getType() == 'article') {
				$t['productAndItemsFrameWork'] = $templateService->getSubpart($t['listFrameWork'],'###ITEM_PRODUCT_AND_ITEMS###');
				$t['productFrameWork'] = $templateService->getSubpart($t['productAndItemsFrameWork'], '###ITEM_PRODUCT###');
			}
			$t['itemFrameWork'] = $templateService->getSubpart($t['categoryAndItemsFrameWork'],'###ITEM_LIST###');
			$t['item'] = $templateService->getSubpart($t['itemFrameWork'], '###ITEM_SINGLE###');

			if (
				isset($damJoinTableArray) &&
				is_array($damJoinTableArray) &&
				in_array('address', $damJoinTableArray)
			) {
				$t['itemheader'] = [];
				$t['itemheader']['address'] = $templateService->getSubpart($t['itemFrameWork'],'###ITEM_ADDRESS###');
				if ($t['itemheader']['address'] != '') {
					$headerField = $itemTable->getField('address');
					$headerFieldIndex = 0;
					$headerFieldArray[$headerFieldIndex] = $headerField;
					$headerTableArray[$headerFieldIndex] = 'address';
					$headerTableObjArray['address'] = $tablesObj->get('address', true);
					$markerFieldArray = [];
					$headerViewTagArray[$headerFieldIndex] = [];
					$headerParentArray[$headerFieldIndex] = [];

					$headerTableFieldsArray[$headerFieldIndex] = $markerObj->getMarkerFields(
						$t['itemheader']['address'],
						$tablesObj->get('address')->getTableObj()->tableFieldArray,
						$tablesObj->get('address')->getTableObj()->requiredFieldArray,
						$markerFieldArray,
						$tablesObj->get('tt_products', true)->getMarker(),
						$headerViewTagArray[$headerFieldIndex],
						$headerParentArray[$headerFieldIndex]
					);
					// $foreignTableInfo = $tablesObj->getForeignTableInfo ($tablename,$itemTable->getField('address'));
				}
			}

			if (
				isset($articleViewObj) &&
				is_object($articleViewObj) &&
				(
					strpos($t['item'], $articleViewObj->getMarker()) ||
					strpos($t['item'], 'PRICE')
				)
			) {
				$showArticles = true;
			}

			if ($t['categoryAndItemsFrameWork'] != '') {
				$bItemPostHtml = (strpos($t['item'], 'ITEM_SINGLE_POST_HTML') !== false);
					// Get products count
				$selectConf = [];
				$allowedPages = ($pid ? $pid : $this->pidListObj->getPidlist());

				if ($additionalPages) {
					$allowedPages .= ','.$additionalPages;
				}
				$allowedPages = GeneralUtility::uniqueList($allowedPages);

				if (!empty($allowedPages)) {
					$selectConf['pidInList'] = $allowedPages;
				}

				if ($allowedItems || $allowedItems == '0') {
					$allowedItemArray = [];
					$tempArray = GeneralUtility::trimExplode(',', $allowedItems);
					$allowedItemArray = $GLOBALS['TYPO3_DB']->cleanIntArray($tempArray);
					$selectConf['uidInList'] = implode(',', $allowedItemArray);
				}

				$wherestock = (($conf['showNotinStock'] || !is_array($GLOBALS['TCA'][$tablename]['columns']['inStock'])) ? '' : ' AND (inStock > 0) ');
				$whereNew = $wherestock . $where;
				$whereNew = $itemTable->getTableObj()->transformWhere($whereNew);

				$selectConf['where'] = '1=1 ' . $whereNew;
				$selectConf['from'] = $itemTable->getTableObj()->getAdditionalTables();

				if (isset($damJoinTableArray) && is_array($damJoinTableArray) && in_array('address',$damJoinTableArray)) {
					$addressTable = $tablesObj->get('address', false);
					$addressAlias = $addressTable->getAlias();
					$addressTablename = $addressTable->getTablename();
					$bTableAlreadyPresent = false;

					foreach ($sqlTableArray['from'] as $fromTables) {
						if (strpos($fromTables,$addressTablename)!==false) {
							$bTableAlreadyPresent = true;
						}
					}
					if (!$bTableAlreadyPresent) {
						$enableFieldArray = $addressTable->getTableObj()->getEnableFieldArray();
						$foreignTableInfo = $tablesObj->getForeignTableInfo($tablename,$itemTable->fieldArray['address']);
						$foreignTableInfo['table_field'] = $itemTable->fieldArray['address'];
						$newSqlTableArray = [];
						$aliasPostfix=($sqlTableIndex);
						$tablesObj->prepareSQL($foreignTableInfo,$tableAliasArray,$aliasPostfix,$newSqlTableArray);
						$sqlTableArray['from'][$sqlTableIndex] = $foreignTableInfo['foreign_table'];
						if ($foreignTableInfo['where'] != '') {
							$sqlTableArray['where'][$sqlTableIndex] = $foreignTableInfo['where'];
						}
						if (isset($newSqlTableArray) && is_array($newSqlTableArray)) {
							foreach ($sqlTableArray as $k => $tmpArray) {
								if (isset($newSqlTableArray[$k])) {
									$sqlTableArray[$k][$sqlTableIndex] = $newSqlTableArray[$k];
								}
							}
						}
						$sqlTableIndex++;
					}
				}

				if (
                    isset($sqlTableArray) && is_array($sqlTableArray) && isset($sqlTableArray['from']) && is_array($sqlTableArray['from'])
                ) {
					foreach ($sqlTableArray['from'] as $k => $sqlFrom) {
						if ($sqlFrom != '') {
							$delimiter = ',';
							if ($sqlTableArray['local'][$k] == $tablename) {
								$delimiter = '';
							}
							$selectConf['from'] .= $delimiter . $sqlFrom;
						}
					}
					if (!empty($sqlTableArray['where'])) {
						$tmpWhere = implode(' AND ', $sqlTableArray['where']);
						if ($tmpWhere != '') {
							$selectConf['where'] = '(' . $selectConf['where'] . ') AND ' . $tmpWhere;
						}
					}
				}
				$displayConf = [];
					// Get products count
				$displayConf['columns'] = '';
				if (isset($tableConfArray[$functablename]['displayColumns.'])) {
					$displayConf['columns'] = $tableConfArray[$functablename]['displayColumns.'];
					if (is_array($displayConf['columns'])) {
						$displayColumns = $displayConf['columns']['1'];
						ksort($displayConf['columns'],SORT_STRING);
					}
				}
				$displayConf['header'] = '';
				if (isset($tableConfArray[$functablename]['displayHeader.'])) {
					$displayConf['header'] = $tableConfArray[$functablename]['displayHeader.'];
					if (is_array($displayConf['header'])) {
						ksort($displayConf['header'], SORT_STRING);
					}
				}
				$selectConf['orderBy'] = $tableConfArray[$functablename]['orderBy'];
					// performing query for display:
				if (!$selectConf['orderBy']) {
					$selectConf['orderBy'] = $conf['orderBy'];
				}
				$tmpArray = GeneralUtility::trimExplode(',', $selectConf['orderBy']);
				$orderByArray[$functablename] = $tmpArray[0]; // $orderByProduct

				if ($useCategories) {
					$orderByCat = $tableConfArray[$categoryfunctablename]['orderBy'];
				}

					// sorting by category not yet possible for articles
				if ($itemTable->getType() == 'article') { // ($itemTable === $this->tt_products_articles)
					$orderByCat = '';	// articles do not have a direct category
					$tmpArray = GeneralUtility::trimExplode(',', $selectConf['orderBy']);
					$tmpArray = array_diff($tmpArray, ['category']);
					$selectConf['orderBy'] = implode (',', $tmpArray);
				}
				if ($itemTable->fieldArray['itemnumber']) {
					$selectConf['orderBy'] = str_replace ('itemnumber', $itemTable->fieldArray['itemnumber'], $selectConf['orderBy']);
				}
				$selectConf['orderBy'] = $itemTable->getTableObj()->transformOrderby($selectConf['orderBy']);

				$productMarkerFieldArray = [
					'BULKILY_WARNING' => 'bulkily',
					'PRODUCT_SPECIAL_PREP' => 'special_preparation',
					'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
					'PRODUCT_LINK_DATASHEET' => 'datasheet'
				];
				$markerFieldArray = [];
				if ($itemTable->getType() == 'product') {
					$markerFieldArray = $productMarkerFieldArray;
				}
				$viewTagArray = [];
				$parentArray = [];

				$fieldsArray = $markerObj->getMarkerFields(
					$t['item'],
					$itemTable->getTableObj()->tableFieldArray,
					$itemTable->getTableObj()->requiredFieldArray,
					$markerFieldArray,
					$itemTableView->getMarker(),
					$viewTagArray,
					$parentArray
				);

				if (
					$itemTable->getType() == 'product' &&
					in_array($useArticles, [1, 2, 3])
				) {
					$markerFieldArray = [];
					$articleViewTagArray = [];
					$articleParentArray = [];
					$articleFieldsArray = $markerObj->getMarkerFields(
						$t['item'],
						$itemTable->getTableObj()->tableFieldArray,
						$itemTable->getTableObj()->requiredFieldArray,
						$productMarkerFieldArray,
						$articleViewObj->getMarker(),
						$articleViewTagArray,
						$articleParentArray
					);

					$prodUidField = $cnfObj->getTableDesc($articleTable->getTableObj()->name, 'uid_product');
					$fieldsArray = array_merge($fieldsArray, $articleFieldsArray);
					$uidKey = array_search($prodUidField, $fieldsArray);
					if ($uidKey != '') {
						unset($fieldsArray[$uidKey]);
					}
				} else if ($itemTable->getType() == 'article' || $itemTable->getType() == 'dam') {
					$viewProductsTagArray = [];
					$productsParentArray = [];
					$tmpFramework = ($t['productAndItemsFrameWork'] ? $t['productAndItemsFrameWork'] : $t['categoryAndItemsFrameWork']);
					$productsFieldsArray = $markerObj->getMarkerFields(
						$tmpFramework,
						$tablesObj->get('tt_products')->getTableObj()->tableFieldArray,
						$tablesObj->get('tt_products')->getTableObj()->requiredFieldArray,
						$markerFieldArray,
						$tablesObj->get('tt_products', true)->getMarker(),
						$viewProductsTagArray,
						$productsParentArray
					);
				} else {
					$bCheckUnusedArticleMarkers = true;
				}
				if (
					$itemTable->getType() != 'product'
				) {
					$defaultFieldsArray = $tablesObj->get($functablename)->getTableObj()->getDefaultFieldArray();

					$tcaFieldsArray = \JambageCom\Div2007\Utility\TableUtility::getFields($tablename);
					$noTcaFieldsArray = $tablesObj->get($functablename)->getTableObj()->getNoTcaFieldArray();

					$fieldsArray = array_merge($defaultFieldsArray, $tcaFieldsArray, $noTcaFieldsArray);
				}

				$itemTableConf = $cnfObj->getTableConf($itemTable->getFuncTablename(), $theCode);
				$itemTableLangFields = $cnfObj->getTranslationFields($itemTableConf);
				$fieldsArray = array_merge($fieldsArray, $itemTableLangFields);
				$itemImageFields = $cnfObj->getImageFields($itemTableConf);
				$fieldsArray = array_merge($fieldsArray, $itemImageFields);
				$viewCatTagArray = [];
				$catParentArray = [];

				$columnFields = $cnfObj->getColumnFields($itemTableConf);

				if (isset($columnFields) && is_array($columnFields) && count($columnFields)) {
					foreach ($columnFields as $field => $value) {
						$key = array_search($field, $fieldsArray);
						if ($key !== false) {
							unset($fieldsArray[$key]);
							$fieldsArray[] = str_replace($field, $prodAlias . '.' . $field, $value) . ' ' . $field;
						}
					}
				}

				$catFramework = '';
				$mergeTagArray = [];
				if ($useCategories) {
                    $tmp = [];
					$catfieldsArray = $markerObj->getMarkerFields(
						$t['categoryAndItemsFrameWork'], // categoryAndItemsFrameWork  categoryFrameWork
						$categoryTable->getTableObj()->tableFieldArray,
						$categoryTable->getTableObj()->requiredFieldArray,
						$tmp,
						$categoryTableView->getMarker(),
						$viewCatTagArray,
						$catParentArray
					);
					$mergeTagArray = array_merge($viewTagArray, $viewCatTagArray);
				}

				$catTitle = '';
				if (
					$whereCat != '' ||
					(
						$itemTable->getType() == 'product' &&
						$tablename != 'tt_products' &&
						$orderByCat != ''
					)
				) {
					$aliasArray = [];
					$aliasArray['mm1'] = 'mm_cat1';
					$aliasArray['mm2'] = 'mm_cat2';
					$itemTable->addConfCat($categoryTable, $selectConf, $aliasArray);
				}

				if ($orderByCat && ($pageAsCategory < 2 || $itemTable->getType() == 'dam')) {
					$catOrderBy = $categoryTable->getTableObj()->transformOrderby($orderByCat);
					$orderByCatFieldArray = GeneralUtility::trimExplode(',', $catOrderBy);
					$selectConf['orderBy'] = $catOrderBy . ($selectConf['orderBy'] ? ($catOrderBy != '' ? ',' : '') . $selectConf['orderBy'] : '');
					$catAlias = $categoryTable->getTableObj()->getAlias();

					if ($itemTable->getType() == 'dam') {
						// SELECT *
						// FROM tx_dam LEFT OUTER JOIN  tx_dam_mm_cat ON tx_dam.uid = tx_dam_mm_cat.uid_local

						if ($selectConf['leftjoin'] == '') {
							$selectConf['leftjoin'] = 'tx_dam_mm_cat mm_cat1 ON ' . $prodAlias . '.uid=mm_cat1.uid_local';
						}
					} else {
						// SELECT *
						// FROM tt_products
						// LEFT OUTER JOIN tt_products_cat ON tt_products.category = tt_products_cat.uid
						$selectConf['leftjoin'] = $categoryTable->getTableObj()->name . ' ' . $catAlias . ' ON ' . $catAlias . '.uid=' . $prodAlias . '.category';
					}
					$catTables = $categoryTable->getTableObj()->getAdditionalTables([$categoryTable->getTableObj()->getLangName()]);

					if (!empty($selectConf['from'])) {
						$tmpDelim = ',';
					}
					if ($catTables!='') {
						$selectConf['from'] = $catTables . $tmpDelim . $selectConf['from'];
					}

					if ($categoryTable->bUseLanguageTable($tableConfArray[$categoryfunctablename])) {

						$joinTables = $selectConf['leftjoin'];
						$categoryTable->getTableObj()->transformLanguage($joinTables, $selectConf['where'], true);
						$selectConf['leftjoin'] = $joinTables;
					}
				}

				$collateConf = [];
				if (
					isset($tableConfArray[$functablename]['collate.'])
				) {
					$collateConf[$functablename] = $tableConfArray[$functablename]['collate.'];
				}

				$selectFields = implode(',', $fieldsArray);
				$selectConf['selectFields'] = 'DISTINCT ' . $itemTable->getTableObj()->transformSelect($selectFields, '', $collateConf);

				if (
					isset($damJoinTableArray) &&
					is_array($damJoinTableArray) &&
					in_array('address', $damJoinTableArray) &&
					$addressAlias != ''
				) {
					$addressConf = $addressTable->getTableConf($theCode);
					if (isset($addressConf['requiredFields'])) {
						$addressFieldArray = GeneralUtility::trimExplode(',', $addressConf['requiredFields']);
						foreach ($addressFieldArray as $field) {
							$selectConf['selectFields'] .= ',' . $addressAlias . $aliasPostfix . '.' . $field . ' address_' . $field;
						}
					}
				}

				if (in_array($theCode, $viewedCodeArray) && $limit > 0) {
					if (\JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn()) {
						$feUserId = intval($GLOBALS['TSFE']->fe_user->user['uid']);
					}
					$whereMM = '';
					$productAlias = $itemTable->getAlias();
					$whereProducts = '';

					switch ($theCode) {
						case 'LISTAFFORDABLE':
							if ($feUserId) {
								$whereProducts = ' AND ' . $productAlias . '.creditpoints<=' .
									$GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'], $tablename);
							}
						break;
						case 'LISTVIEWEDITEMS':
							if ($feUserId) {
								$mmTablename = 'sys_products_fe_users_mm_visited_products mmv';
								$whereMM = 'mmv.uid_local=' . $feUserId;
								$orderByProducts = 'mmv.tstamp DESC';
								$whereProducts = ' AND ' . $productAlias . '.uid=mmv.uid_foreign';
							}
						break;
						case 'LISTVIEWEDMOST':
							if ($feUserId) {
								$mmTablename = 'sys_products_fe_users_mm_visited_products mmv';
								$whereMM = 'mmv.uid_local=' . $feUserId;
								$orderByProducts = 'mmv.qty DESC';
								$whereProducts = ' AND ' . $productAlias . '.uid=mmv.uid_foreign';
							}
						break;
						case 'LISTVIEWEDMOSTOTHERS':
							$viewedTablename = 'sys_products_visited_products visit';
							$orderByProducts = 'visit.qty DESC';
							$whereProducts = ' AND ' . $productAlias . '.uid=visit.uid';
						break;
					}

					if ($mmTablename != '') {
						$selectConf['from'] = (!empty($selectConf['from']) ? $selectConf['from'] . ',' . $mmTablename : $mmTablename);
						$whereProducts = ' AND ' . $whereMM . $whereProducts;
					}
					if ($viewedTablename != '') {
						$selectConf['from'] = (!empty($selectConf['from']) ? $selectConf['from'] . ',' . $viewedTablename : $viewedTablename);
					}
					if ($orderByProducts != '') {
						$selectConf['orderBy'] = $orderByProducts;
					}
					if ($whereProducts != '') {
						$selectConf['where'] .= $whereProducts;
					}
				}

				$join = '';
				$tmpTables = $itemTable->getTableObj()->transformTable('', false, $join);
				// $selectConf['where'] = $join.$itemTable->getTableObj()->transformWhere($selectConf['where']);
				$selectConf['where'] = $join . ' ' . $selectConf['where'];

				if (isset($tableConfArray[$functablename]['filter.']) && is_array($tableConfArray[$functablename]['filter.'])) {
					$filterConf = $tableConfArray[$functablename]['filter.'];

					if (
						isset($filterConf['regexp.']) &&
						is_array($filterConf['regexp.']) &&
						isset($filterConf['regexp.']['field.']) &&
						is_array($filterConf['regexp.']['field.'])
					) {
						foreach ($filterConf['regexp.']['field.'] as $field => $value) {
							$selectConf['where'] .= ' AND ' . $field . ' REGEXP ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(quotemeta($value), $tablename);
						}
					}
					if (
						isset($filterConf['where.']) &&
						is_array($filterConf['where.']) &&
						isset($filterConf['where.']['field.']) &&
						is_array($filterConf['where.']['field.'])
					) {
						foreach ($filterConf['where.']['field.'] as $field => $value) {
							if (strpos($value, $field) === false) {
								$selectConf['where'] .= ' AND ' . $field . ' = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(quotemeta($value),  $tablename);
							} else {
								$selectConf['where'] .= ' AND ' . $value;
							}
						}
					}
				}
				$selectConf['groupBy'] = $dam_group_by;

					// performing query to count all products (we need to know it for browsing):
				$selectCountConf = $selectConf;
				$selectCountConf['selectFields'] = 'count(distinct ' . $itemTable->getAlias() . '.uid)'; // .$catSelect;
				$queryParts =
					$itemTable->getTableObj()->getQueryConf(
						$cObj,
						$tablename,
						$selectCountConf,
						true
					);

				if (!empty($selectCountConf['groupBy'])) {
					$queryParts['SELECT'] = 'count(DISTINCT ' . $selectCountConf['groupBy'] . ')';
					unset($queryParts['GROUPBY']);
				}

				// run the COUNT SELECT
				$res = $itemTable->getTableObj()->exec_SELECT_queryArray(
					$queryParts,
					'',
					false,
					$collateConf
				);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				$productsCount = (is_array($row) ? $row['0'] : 0);

				$browserConf = $this->getBrowserConf($tableConfArray[$functablename]); // needed for the replacement of the method pi_linkTP_keepPIvars by BrowserUtility::linkTPKeepCtrlVars and the page browser
				$maxPages = 10000;
				if (isset($browserConf['maxPages'])) {
                    $maxPages = intval($browserConf['maxPages']);
				}

				$browseObj =
					$this->getBrowserObj(
						$conf,
						$browserConf,
						$productsCount,
						$limit,
						$maxPages
					);

					// range check to current productsCount
				$begin_at_start = (($begin_at >= $productsCount) ? ($productsCount >= $limit ? $productsCount - $limit : $productsCount) : $begin_at);
				$begin_at = MathUtility::forceIntegerInRange($begin_at_start, 0);

				if ($latest > 0) {
					$start = $productsCount - $latest;
					if ($start <= 0) {
						$start = 1;
					}
					$selectConf['begin'] = $start;
					$limit = $latest;
					$productsCount = $latest;
				}
				$selectConf['max'] = ($limit + 1);
				if ($begin_at > 0) {
					$selectConf['begin'] = $begin_at;
				}

				if ($selectConf['orderBy']) {
					$selectConf['orderBy'] =
						$GLOBALS['TYPO3_DB']->stripOrderBy($selectConf['orderBy']);
				}

				if (isset($tableConfArray[$functablename]['groupBy'])) {
					$selectConf['groupBy'] = $tableConfArray[$functablename]['groupBy'];

					$selectConf['groupBy'] = $itemTable->getTableObj()->transformOrderby($selectConf['groupBy']);

					if ($selectConf['groupBy']) {
						$selectConf['groupBy'] = $GLOBALS['TYPO3_DB']->stripGroupBy($selectConf['groupBy']);
					}
				}

				$queryParts = $itemTable->getTableObj()->getQueryConf(
					$cObj,
					$tablename,
					$selectConf,
					true
				);

				if (!empty($selectConf['groupBy'])) {
					$queryParts['SELECT'] .= ',count(' . $selectConf['groupBy'] . ') sql_groupby_count';
				}

				if ($queryParts === false) {
					return 'ERROR in tt_products';
				}

				// run the big SELECT
				$res =
					$itemTable->getTableObj()->exec_SELECT_queryArray(
						$queryParts,
						'',
						false,
						$collateConf
					);
				$iCount = 0;
				$uidArray = [];
				while($iCount < $limit && ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
					$iCount++;

					if (is_array($itemTableLangFields) && count($itemTableLangFields)) {
						foreach($itemTableLangFields as $field => $langfield) {
							$row[$field] = $row[$langfield];
						}
					}

					if (
						$itemTable->getType() == 'product' &&
						$useArticles == 3
					) {
						$itemTable->fillVariantsFromArticles($row);
					}

					$itemTable->getTableObj()->substituteMarkerArray(
						$row,
						$selectableVariantFieldArray
					);
					$itemTable->getTableObj()->transformRow(
						$row,
						TT_PRODUCTS_EXT
					);

					if (isset($parentRows) && is_array($parentRows)) {
						foreach ($parentRows as $parentRow) {
							if (
								isset($parentRow['childs']) &&
								is_array($parentRow['childs']) &&
								in_array($row['uid'], $parentRow['childs'])
							) {
								$currentParentRow = $parentRow;
								foreach ($row as $field => $value) {
									if (empty($value) && !empty($parentRow[$field])) {
                                        $prefixArray = ['', FieldInterface::EXTERNAL_FIELD_PREFIX];
                                        foreach ($prefixArray as $prefix) {
                                            if (
                                                (
                                                    $field == $prefix . 'price'
                                                ) &&
                                                (
                                                    $row[$prefix . 'price_enable'] ||
                                                    (
                                                        !$parentRow[FieldInterface::EXTERNAL_FIELD_PREFIX . 'price_enable'] &&
                                                        !$parentRow['price_enable']
                                                    )
                                                )
                                            ) {
                                                continue 2;
                                            }
										}
										$correspondingField = $field;
										if (strpos($field, FieldInterface::EXTERNAL_FIELD_PREFIX) === 0) {
											$correspondingField = substr($field, strlen(FieldInterface::EXTERNAL_FIELD_PREFIX));
										}
										$row[$field] = $parentRow[$correspondingField];
									}
								}
							}
						}
					}

					$itemArray[] = $row;
					if (isset($row['uid'])) {
						$uidArray[] = $row['uid'];
					}
				}

				if (
					$iCount == $limit &&
					($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
				) {
					$more = 1;
				}

				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				if ($theCode == 'LISTGIFTS') {
					$markerArray =
						tx_ttproducts_gifts_div::addGiftMarkers(
							$markerArray,
							$this->giftnumber
						);
					$javaScriptObj = GeneralUtility::makeInstance('tx_ttproducts_javascript');
					$javaScriptObj->set($languageObj, 'email');
				}
				$markerArray['###FORM_NAME###'] = $formName . '_' . $contentUid; // needed if form starts e.g. between ###ITEM_LIST_TEMPLATE### and ###ITEM_CATEGORY_AND_ITEMS###

				$markerFramework = 'listFrameWork';
				$t[$markerFramework] =
					$templateService->substituteMarkerArrayCached(
						$t[$markerFramework],
						$markerArray,
						[],
						[]
					);
				$t['itemFrameWork'] =
					$templateService->substituteMarkerArrayCached(
						$t['itemFrameWork'],
						$markerArray,
						[],
						[]
					);

				$currentArray = [];
				$currentArray['category'] = '-1';
				$currentArray['product'] = '-1';
				$nextArray = [];
				$nextArray['category'] = '';
				$nextArray['product'] = '';
				$productMarkerArray = [];
				$out = '';
				$categoryAndItemsOut = '';
				$iCount = 0;
				$iColCount = 0;
				$productListOut = '';
				$itemsOut = '';
				$headerItemsOutArray = [];
				$currentHeaderRow = [];
				$itemListOut = '';
				$categoryOut = '';
				$tableRowOpen = 0;
				$itemListSubpart = ($itemTable->getType() == 'article' && $t['productAndItemsFrameWork'] ? '###ITEM_PRODUCT_AND_ITEMS###' : '###ITEM_LIST###');
				$prodRow = [];
				if ($itemTable->getType() != 'product') {
					$prodRow = $parentProductRow;
				}
				$formCount = 1;
				$bFormPerItem = false;
				$itemLower = strtolower($t['item']);
                $cat = '';

				if (strpos($itemLower, '<form') !== false) {
					$bFormPerItem = true;
				}
				$bUseDAM = false;
				if (strpos($itemLower, '###dam_field_name###') !== false) {
					$bUseDAM = true;
				}

				if (count($itemArray)) {	// $itemArray must have numbered indexes to work, because the next item must be determined

					if ($itemTable->getType() == 'dam') { //
						$productDAMMarkerArray = $relatedListView->getListMarkerArray(
							$theCode,
							$templateCode,
							$mergeTagArray,
							$functablename,
							'',
							$this->uidArray,
							$parentProductRow,
							true,
                            $multiOrderArray,
							$useArticles,
							$pageAsCategory,
							$this->pid,
							$error_code
						);
					}
					$categoryMarkerArray = [];
					$categorySubpartArray = [];
					$categoryWrappedSubpartArray = [];
					$itemRowWrapArray = [];

					$itemRowWrapArray = GeneralUtility::trimExplode('|', $cssConf['itemRowWrap']);

					foreach ($itemArray as $k2 => $row) {
						$bHeaderFieldChanged = false;

						if (is_array($headerTableArray) && count($headerTableArray)) {
							if (is_array($currentHeaderRow) && count($currentHeaderRow)) {
								foreach($headerTableArray as $headertable) {
									$headerTableLen = strlen($headertable);

									foreach($row as $field => $v) {
										if (strpos($field, $headertable) === 0) {
											$headerKey =
												substr($field, $headerfieldLen + 1, strlen($field) - $headerTableLen - 1);
											if ($currentHeaderRow[$headertable][$headerKey] != $v) {
												$bHeaderFieldChanged = true;
												break;
											}
										}
									}
								}
							}

							if ($bHeaderFieldChanged || !count($currentHeaderRow)) {
								$bHeaderFieldChanged = true;
								$headerMarkerArray = [];
								foreach($headerTableArray as $headertable) {

									$headerTableLen = strlen($headertable);
									foreach($row as $field => $v) {

										if (strpos($field, $headertable) === 0) {
											$headerKey =
												substr($field,$headerTableLen + 1, strlen($field) - $headerTableLen - 1);
											$currentHeaderRow[$headertable][$headerKey] = $v;
										} // getMarker ()
									}
								}

								foreach($currentHeaderRow as $headertable => $headerRow) {

									$headerMarkerArray = [];
									$tmp = [];
									$tablesObj->get($headertable, true)->getRowMarkerArray(
										$headertable,
										$headerRow,
										'',
										$headerMarkerArray,
										$tmp,
										$tmp,
										$headerViewTagArray[$headerFieldIndex],
										$theCode,
										$basketExtra,
										$basketRecs,
										true,
										'',
										0,
										'image',
										'',	// id part to be added
										'', // if false, then no table marker will be added
										'',	// this could be a number to discern between repeated rows
										'',
										$theCode == 'LISTGIFTS'
									);
									$headerItemsOutArray[$headertable] = $templateService->substituteMarkerArrayCached(
										$t['itemheader']['address'],
										$headerMarkerArray,
										[],
										[]
									);
								}
							}
						}
						$iColCount++;
						$iCount++;
						$childCatWrap = '';
						$displayCatHeader = '';

						if ($useCategories) {
							if (
								empty($tableConfArray[$categoryfunctablename]['onlyDefaultCategory']) &&
								$categoryTable->getFuncTablename() == 'tt_products_cat'
							) {
								$currentCat = $row['category'];
							}

							$catArray = $categoryTable->getCategoryArray($row, 'sorting');

							if (
								empty($tableConfArray[$categoryfunctablename]['onlyDefaultCategory']) &&
								is_array($catArray) &&
								count($catArray)
							) {
								reset($catArray);
								$this->getCategories(
									$categoryTable,
									$catArray,
									$rootCatArray,
									$rootLineArray,
									$cat,
									$currentCat,
									$displayCat
								);
								$depth = 0;
								$bFound = false;

								foreach ($rootLineArray as $catVal) {
									$depth++;
									if (in_array($catVal, $rootCatArray)) {
										$bFound = true;
										break;
									}
								}
								if (!$bFound) {
									$depth = 0;
								}

								$catLineArray =
									$categoryTable->getLineArray(
										$displayCat,
										[0 => $currentCat]
									);
								$catLineArray = array_reverse($catLineArray);
								reset($catLineArray);
								$confDisplayColumns = $this->getDisplayInfo($displayConf, 'columns', $depth, !count($childCatArray));
								$displayColumns =
									(
										MathUtility::canBeInterpretedAsInteger($confDisplayColumns) ?
											$confDisplayColumns :
											$displayColumns
									);

								if (count($childCatArray)) {
									$linkCat = next($catLineArray);

									if ($linkCat) {
										$addQueryString = [$categoryPivar => $linkCat];
										$tempUrl =
											BrowserUtility::linkTPKeepCtrlVars(
												$browseObj,
												$cObj,
												$prefixId,
												'|',
												$addQueryString,
												1,
												1,
												$GLOBALS['TSFE']->id
											);
										$childCatWrap = '<a href="' . $tempUrl . '"' . $css . '> | </a>';
										$imageWrap = false;
									}
								}
							} else {
								$displayCat = $currentCat;
							}
							$displayCatHeader =
								$this->getDisplayInfo(
									$displayConf,
									'header',
									$depth,
									!count($childCatArray)
								);

							if ($displayCatHeader == 'current') {
								$displayCat = $currentCat;
							}
						}

							// print category title
						if	(
								$useCategories &&
								$conf['displayListCatHeader'] &&
								(
									($pageAsCategory < 2) && ($displayCat != $currentArray['category']) ||
									($pageAsCategory == 2) && ($row['pid'] != $currentArray['category']) ||
									$displayCatHeader == 'always'
								)
							) {
							$catItemsListOut = &$itemListOut;
							if ($itemTable->getType() == 'article' && $productListOut && $t['productAndItemsFrameWork']) {
								$catItemsListOut = &$productListOut;
							}

							if ($catItemsListOut && $conf['displayListCatHeader']) {
								$out .= $this->advanceCategory($t['categoryAndItemsFrameWork'], $catItemsListOut, $categoryOut, $itemListSubpart, $oldFormCount, $formCount);
							}
							$currentArray['category'] = (($pageAsCategory < 2 || $itemTable->getType() == 'dam') ? $displayCat : $row['pid']);
							$bCategoryHasChanged = true;
							$categoryMarkerArray = [];
							$categorySubpartArray = [];
							$categoryWrappedSubpartArray = [];

							if ($where != '' || !empty($conf['displayListCatHeader'])) { // Todo: displayListCatHeader is always 1 because of if before
                                $tmp = [];
								$categoryTableView->getMarkerArray(
									$categoryMarkerArray,
									'',
									$displayCat,
									$row['pid'],
									$config['limitImage'],
									'listcatImage',
									$viewCatTagArray,
									$tmp,
									$pageAsCategory,
									$theCode,
									$basketExtra,
									$basketRecs,
									'',
									'',
									''
								);

								$catTitle = $categoryTableView->getMarkerArrayCatTitle($categoryMarkerArray);
								$categoryTableView->getParentMarkerArray(
									$catParentArray,
									$row,
									$categoryMarkerArray,
									$displayCat,
									$row['pid'],
									$config['limitImage'],
									'listcatImage',
									$viewCatTagArray,
									[],
									$pageAsCategory,
									$theCode,
									$basketExtra,
									$basketRecs,
									1,
									''
								);

								if ($t['categoryFrameWork'] && $conf['displayListCatHeader']) {
									if ($displayCat) {
										$catRow = $categoryTable->get($displayCat);
										$categoryTableView->getItemSubpartArrays(
											$t['categoryAndItemsFrameWork'],
											$functablename,
											$catRow,
											$categorySubpartArray,
											$categoryWrappedSubpartArray,
											$viewCatTagArray,
											$theCode,
											$basketExtra,
											$basketRecs
										);
									}

									$categoryOut = $templateService->substituteMarkerArrayCached(
										$t['categoryFrameWork'],
										$categoryMarkerArray,
										$categorySubpartArray,
										$categoryWrappedSubpartArray
									);

									if ($displayCatHeader != 'always') {
										$iColCount = 1;
									}
								}
							}
						} else {
							$bCategoryHasChanged = false;
						}

						$subpartArray = [];

						if ($itemTable->getType() == 'article') {
							// relevant only for article list with articleMode == 0
							if (
								isset($row['uid_product']) &&
								$row['uid_product'] &&
								$row['uid_product'] != $currentArray['product']
							) {
								$productMarkerArray = [];
								// fetch new product if articles are listed
								$prodRow = $tablesObj->get('tt_products')->get($row['uid_product']);

								$item = $basketObj->getItem(
									tx_ttproducts_control_basket::getBasketExt(),
									$basketExtra,
									$basketRecs,
									$prodRow,
									'firstVariant'
								);

								$itemTableViewArray['product']->getModelMarkerArray(
									$prodRow,
									$itemTableViewArray['product']->getMarker(),
									$productMarkerArray,
									$catTitle,
									$config['limitImage'],
									'listImage',
									$viewProductsTagArray,
									[],
									$theCode,
									$basketExtra,
									$basketRecs,
									$iCount,
									'',
									'',
									$imageWrap,
									true,
									'UTF-8',
									$hiddenFields,
									$multiOrderArray,
									$productRowArray,
									$theCode == 'LISTGIFTS'
								);
								$tablesObj->get('tt_products', true)->getItemSubpartArrays(
									$t['item'],
									'tt_products',
									$row,
									$subpartArray,
									$wrappedSubpartArray,
									$viewProductsTagArray,
									$theCode,
									$basketExtra,
									$basketRecs,
									$iCount
								);

								if ($itemListOut && $t['productAndItemsFrameWork']) {
									$productListOut .=
										$this->advanceProduct(
											$t['productAndItemsFrameWork'],
											$t['productFrameWork'],
											$itemListOut,
											$productMarkerArray,
											$categoryMarkerArray
										);
								}
							} else {
								$prodRow = $parentProductRow;
                            }
							$itemTable->mergeAttributeFields(
								$row,
								$prodRow,
								true,
								false,
								false,
								'',
								false,
								true
							);
							$currentArray['product'] = $row['uid_product'] ?? 0;
						} else {
							$currentArray['product'] = $row['uid'];

							if ($itemTable->getType() == 'product') {
								$prodRow = $row;
							} else {
                                $prodRow = $parentProductRow;
                                $prefixArray = ['', FieldInterface::EXTERNAL_FIELD_PREFIX];
                                foreach ($prefixArray as $prefix) {

                                    if (
                                        isset($row[$prefix . 'price']) &&
                                        isset($row[$prefix . 'price_enable']) &&
                                        $row[$prefix . 'price_enable']
                                    ) {
                                        $prodRow['price'] = $row[$prefix . 'price'];
                                    }
                                }
                            }
						}
						$temp = $cssConf['default'] ?? '';
						$css_current = ($temp ? $temp : $conf['CSSListDefault'] ?? '');	// only for backwards compatibility

						if (
                            isset($this->uidArray[$itemTable->getType()]) &&
                            $row['uid'] == $this->uidArray[$itemTable->getType()]
                        ) {
							$temp = $cssConf['current'] ?? '';
							$css_current = ($temp ? $temp : $conf['CSSListCurrent'] ?? '');
						}
						$css_current = ($css_current ? ' class="' . $css_current . '"' : '');

							// Print Item Title
						$wrappedSubpartArray = [];
						$addQueryString = [];
						$pagesObj = $tablesObj->get('pages');
						$pid = $pagesObj->getPID($conf['PIDitemDisplay'] ?? '', $conf['PIDitemDisplay.'] ?? '', $row);

						$parentUid = 0;
						if (
							isset($parentDataArray) &&
							is_array($parentDataArray) &&
							isset($parentDataArray['functablename']) &&
							isset($parentDataArray['uid'])
						) {
							if (
								$parentDataArray['functablename'] == 'tt_products' &&
								(
									$conf['noArticleSingleView'] ||
									$itemTable->getType() != 'article'
								)
							) {
								$parentUid = intval($parentDataArray['uid']);
							}
						}

						if (
							$conf['noArticleSingleView'] &&
							!$parentUid &&
							$itemTable->getType() == 'article'
						) {
							$parentUid = $prodRow['uid'];
						}

						if ($parentUid) {
							$addQueryString[$itemTableViewArray['product']->getPivar()] = $parentUid;
						}

						$addQueryString[$itemTableView->getPivar()] = intval($row['uid']);
						$piVarCat = $piVars[$categoryPivar] ?? '';
						$useBackPid = $useBackPid && ($pid != $GLOBALS['TSFE']->id);
						$nextcat = $cat;

						if (
							$useCategories &&
							isset($viewParamConf) &&
							is_array($viewParamConf) &&
							isset($viewParamConf['item']) &&
							GeneralUtility::inList($viewParamConf['item'], $categoryPivar)
						) {
							$nextcat = $row['category'];
						} else if ($piVarCat) {
							if (
								!empty($conf['PIDlistDisplay']) &&
								!PluginApi::isRelatedCode($theCode)
							) {
								$useBackPid = false;
							}
							$cat = $piVarCat;
						}

						if ($nextcat) {
							$addQueryString[$categoryPivar] = $nextcat;
						}

						$excludeList = '';

						if (
							isset($viewParamConf) &&
							is_array($viewParamConf) &&
							!empty($viewParamConf['ignore'])
						) {
							$excludeList = $viewParamConf['ignore'];
						}

						$queryString = $urlObj->getLinkParams(
							$excludeList,
							$addQueryString,
							true,
							$useBackPid,
							$backPid,
							$itemTableView->getPivar(),
							$categoryPivar
						);

						$linkConf = [];
						if (
							isset($linkConfArray) &&
							is_array($linkConfArray) &&
							isset($linkConfArray['LINK_ITEM.'])
						) {
							$linkConf = $linkConfArray['LINK_ITEM.'];
						}

						$target = '';
						$pageLink = FrontendUtility::getTypoLink_URL(
							$cObj,
							$pid,
							$queryString,
							$target,
							$linkConf
						);

						if ($childCatWrap) {
							$wrappedSubpartArray['###LINK_ITEM###'] = GeneralUtility::trimExplode('|', $childCatWrap);
						} else {
							$wrappedSubpartArray['###LINK_ITEM###'] =
								[
									'<a class="singlelink" href="' . htmlspecialchars($pageLink) . '"' .
										$css_current .
										'>',
									'</a>'
								];
						}

						tx_ttproducts_control_memo::getWrappedSubpartArray(
							$wrappedSubpartArray,
							$pidMemo,
							$row['uid'],
							$cObj,
							$urlObj,
							$excludeList,
							[],
							'',
							$useBackPid
						);

						if (is_array($mergeRow) && count($mergeRow)) {
							$row = array_merge($row, $mergeRow);
						}

						if ($itemTable->getType() != 'product') {
							$externalRowArray[$itemTable->getFuncTablename()] = $row;
						}

						$markerArray = [];
						$item = $basketObj->getItem(
							tx_ttproducts_control_basket::getBasketExt(),
							$basketExtra,
							$basketRecs,
							$prodRow,
							'firstVariant',
							$itemTable->getFuncTablename(),
							$externalRowArray,
							$theCode == 'LISTGIFTS'
						);

						if (!empty($item)) {
							$prodRow = $item['rec'];
						}

						$image = ($childCatWrap ? 'listImageHasChilds' : 'listImage');

						if (
							isset($categoryArray) &&
							is_array($categoryArray) &&
							!isset($categoryArray[$currentCat]) &&
							isset($conf['listImageRoot.']) &&
							is_array($conf['listImageRoot.'])
						) {
							$image = 'listImageRoot';
						}
						$markerArray['###SQL_GROUPBY_COUNT###'] = $row['sql_groupby_count'] ?? 0;
						$allVariants = '';
						$prodVariantRow = $prodRow;

						if (
							in_array(
								$itemTable->getType(),
								['product', 'article']
							)
						) {
							if (
								in_array($useArticles, [1, 2, 3]) &&
								$showArticles
							) {
								$articleRow = '';
								if ($itemTable->getType() == 'product') {
									// get the article uid with these colors, sizes and gradings
									$articleRow = $itemTable->getArticleRow($row, $theCode, true);
								} else  if ($itemTable->getType() == 'article') {
									$articleRow = $row;
								}

									// use the product if no article row has been found
								if ($articleRow) {
									$prodVariantRow = tx_ttproducts_field_price::getWithoutTaxedPrices($prodVariantRow);
									if ($itemTable->getType() == 'product') {
										$itemTable->mergeAttributeFields(
											$prodVariantRow,
											$articleRow,
											false,
											($useArticles == 3),
											false,
											'',
											false,
											false
										);
									}

									$prodVariantRow['ext']['tt_products_articles'][] = $articleRow;
								} else {
									$prodVariantRow['ext']['tt_products_articles'] = [];
								}
							}
						}

						$allVariants =
							$basketObj->getAllVariants(
								$functablename,
								$row,
								$prodVariantRow
							);

						if (
							in_array(
								$itemTable->getType(),
								['product', 'article', 'fal']
							)
						) {
							if (
								$showArticles &&
								$itemTable->getType() == 'product'
							) {
								$currRow = $basketObj->getItemRow(
									$prodVariantRow,
									$allVariants,
									$useArticles,
									$itemTable->getFuncTablename(),
									false
								);
							} else {
								$currRow = $prodVariantRow;
							}

							$basketExt1 = tx_ttproducts_control_basket::generatedBasketExtFromRow($currRow, '1');

							$basketItemArray = $basketObj->getItemArrayFromRow(
								$currRow,
								$basketExt1,
								$basketExtra,
								$basketRecs,
								$functablename,
								$externalRowArray,
								$theCode == 'LISTGIFTS'
							);

							if (!empty($basketItemArray)) {
								$basketObj->calculate($basketItemArray); // get the calculated arrays
								$prodVariantRow = $basketObj->getMergedRowFromItemArray($basketItemArray, $basketExtra);
							}
							$currPriceMarkerArray = [];
							$articleTablename = (is_object($itemTableArray['article']) ? $itemTableArray['article']->getTablename() : '');
							$itemTableViewArray['product']->getCurrentPriceMarkerArray(
								$currPriceMarkerArray,
								'',
								$itemTableArray['product']->getTablename(),
								$prodRow,
								$articleTablename,
								$prodVariantRow,
								'',
								$theCode,
								$basketExtra,
								$basketRecs,
                                false, // $enableTaxZero neu
                                $notOverwritePriceIfSet
							);
							$markerArray = array_merge($markerArray, $currPriceMarkerArray);
							$bInputDisabled = ($row['inStock'] <= 0);

							$basketItemView = GeneralUtility::makeInstance('tx_ttproducts_basketitem_view');

							$mergedProdRowWithoutVariants = $prodVariantRow;
							$itemTable->mergeVariantFields(
								$mergedProdRowWithoutVariants,
								$row,
								false
							);

							if ($itemTable->getType() == 'product') {
								$item['rec'] = $mergedProdRowWithoutVariants;
							} else {
								$item['rec'] = $prodRow;
							}

							$basketItemView->getItemMarkerArray(
								$functablename,
								true,
								$item,
								$markerArray,
								$viewTagArray,
								$tmpHidden,
								$theCode,
								$bInputDisabled,
								$iCount,
								true,
								'UTF-8',
								$row,
								$parentFuncTablename,
								$currentParentRow,
								$callFunctableArray,
								$multiOrderArray
							);

							$listMarkerArray = $relatedListView->getListMarkerArray(
								$theCode,
								$templateCode,
								$viewTagArray,
								$functablename,
								$row['uid'],
								$this->uidArray,
								$prodRow,
								true,
                                $multiOrderArray,
 								$useArticles,
								$pageAsCategory,
								$this->pid,
								$error_code
							);

							if ($listMarkerArray && is_array($listMarkerArray)) {
								$quantityMarkerArray = [];

								foreach ($listMarkerArray as $marker => $markerValue) {
									$markerValue = $templateService->substituteMarkerArray($markerValue, $markerArray);
									$markerValue = $templateService->substituteMarkerArray($markerValue, $quantityMarkerArray);
									$markerArray[$marker] = $markerValue;
								}
							}
						}

						$itemTableView->getModelMarkerArray(
							$row,
							$itemTableViewArray[$itemTable->getType()]->getMarker(),
							$markerArray,
							$catTitle,
							$config['limitImage'],
							$image,
							$viewTagArray,
							[],
							$theCode,
							$basketExtra,
							$basketRecs,
							'',
							'',
							'',
							$imageWrap,
							true,
							'UTF-8',
							$hiddenFields,
							$multiOrderArray,
							$productRowArray,
							$theCode == 'LISTGIFTS'
						);

						if (
							$itemTable->getType() == 'product' &&
							$useCategories &&
							isset($tableConfArray[$categoryfunctablename]) &&
							isset($tableConfArray[$categoryfunctablename]['tagmark.'])
						) {
							$tagArray = [];
							$tagConf = $tableConfArray[$categoryfunctablename]['tagmark.'];
							foreach ($catArray as $loopCategory) {
								$catRow =
									$categoryTable->get(
										$loopCategory,
										0,
										false,
										'',
										'',
										'',
										'',
										'catid'
									);
								if (!empty($catRow['catid'])) {
									$tagArray[] = $catRow['catid'];
								}

								if (!empty($tagConf['parents']) && !empty($catRow['parent_category'])) {
									$parentRow =
										$categoryTable->get(
											$catRow['parent_category'],
											0,
											false,
											'',
											'',
											'',
											'',
											'catid'
										);
									if (
										isset($parentRow) &&
										is_array($parentRow) &&
										!empty($parentRow['catid'])
									) {
										$tagArray[] = $parentRow['catid'];
									}
								}
							}
							$tagArray = array_unique($tagArray);
							sort($tagArray, SORT_NUMERIC);
							$categoryTableView->addAllCatTagsMarker(
								$markerArray,
								$tagArray,
								$tagConf['prefix']
							);
						}

						if ($itemTable->getType() == 'product') {
							if (
								in_array($useArticles, [1, 2, 3]) &&
								$showArticles
							) {
								// use the fields of the article instead of the product
								//
								$itemTableView->getModelMarkerArray(
									$prodVariantRow, // must have the getMergedRowFromItemArray function called before. Otherwise the product will not show the first variant selection at the first start time
									$itemTableViewArray['article']->getMarker(),
									$markerArray,
									$catTitle,
									$config['limitImage'],
									$image,
									$articleViewTagArray,
									[],
									$theCode,
									$basketExtra,
									$basketRecs,
									'from-tt-products-articles',
									'',
									'',
									$imageWrap,
									true,
                                    'UTF-8',
									$hiddenFields,
									$multiOrderArray,
									$productRowArray,
									$theCode == 'LISTGIFTS'
								);
								$articleViewObj->getItemSubpartArrays(
									$t['item'],
									'tt_products_articles',
									$prodRow, // $row
									$subpartArray,
									$wrappedSubpartArray,
									$articleViewTagArray,
									$theCode,
									$basketExtra,
									$basketRecs,
									$iCount
								);
							}

							$itemTableView->getItemMarkerSubpartArrays(
								$t['item'],
								'tt_products',
								$prodRow,
								$markerArray,
								$subpartArray,
								$wrappedSubpartArray,
								$viewTagArray,
								$multiOrderArray,
								$productRowArray,
								$theCode,
								$basketExtra,
								$basketRecs,
								$iCount
							);

							$basketItemView->getItemMarkerSubpartArrays(
								$t['item'],
								$functablename,
								$row,
								$theCode,
								$viewTagArray,
								$bEditableVariants,
								$productRowArray,
								$markerArray,
								$subpartArray,
								$wrappedSubpartArray
							);
						} else {
							$itemTableView->getItemSubpartArrays(
								$t['item'],
								'tt_products',
								$row,
								$subpartArray,
								$wrappedSubpartArray,
								$viewTagArray,
								$theCode,
								$basketExtra,
								$basketRecs,
								$iCount
							);
						}

						if ($itemTable->getType() == 'article') {
							$productMarkerArray = array_merge ($productMarkerArray, $markerArray);
							$markerArray = array_merge ($productMarkerArray, $markerArray);
						} else if (
							$itemTable->getType() == 'dam' &&
							$productDAMMarkerArray &&
							is_array($productDAMMarkerArray)
						) {
							$tmpMarkerArray = [];
							$tmpMarkerArray['###DAM_UID###'] = $row['uid'];

							foreach ($productDAMMarkerArray as $marker => $v) {
								$markerArray[$marker] = $templateService->substituteMarkerArray(
									$v,
									$tmpMarkerArray
								);
							}
						}

						if ($linkCat) {
							$linkCategoryMarkerArray = [];
							$categoryTableView->getMarkerArray(
								$linkCategoryMarkerArray,
								$linkCat,
								$row['pid'],
								$config['limitImage'],
								'listcatImage',
								$viewCatTagArray,
								[],
								$pageAsCategory,
								$theCode,
								$basketExtra,
								$basketRecs,
								'',
								''
							);
							$productMarkerArray = array_merge($productMarkerArray, $linkCategoryMarkerArray);
						}
						$markerArray = array_merge($productMarkerArray, $categoryMarkerArray, $markerArray);

						if (isset($memoViewObj) && is_object($memoViewObj)) {
							$memoViewObj->getFieldMarkerArray(
								$row,
								'MEMODAM',
								$markerArray,
								$mergeTagArray,
								$bUseCheckBox
							);
						}
						$jsMarkerArray = array_merge($jsMarkerArray, $productMarkerArray);
						if ($theCode == 'LISTGIFTS') {
							$markerArray =
								tx_ttproducts_gifts_div::addGiftMarkers(
									$markerArray,
									$basketObj->giftnumber
								);
						}

						// $markerArray['###FORM_URL###']=$this->formUrl; // Applied later as well.
						$addQueryString = [];
						$addQueryString = $this->uidArray;
						$this->getSearchParams($addQueryString);
						$markerArray =
							$urlObj->addURLMarkers(
								$this->pid,
								$markerArray,
								$addQueryString,
								'',
								$useBackPid,
								$backPid
							); // clickIntoBasket
						$oldFormCount = $formCount;

						$markerArray['###FORM_NAME###'] = $formName . '_' . $contentUid . ($bFormPerItem ? '_' . $formCount : '');
						$markerArray['###FORM_INDEX###'] = $formCount - 1;

						if ($bFormPerItem) {
							$formCount++;
						}

						$markerArray['###ITEM_NAME###'] = 'item_' . $contentUid . '_' . $iCount;
						if (!$displayColumns) {
							$markerArray['###FORM_NAME###'] = $markerArray['###ITEM_NAME###'];
						}
						if ($bUseDAM) {
							$damUid = $this->uidArray['dam'];
							if ($damUid) {
								$tablesObj->get('tx_dam')->setFormMarkerArray($damUid, $markerArray);
							}
						}
						$markerArray['###FORM_ONSUBMIT###'] = 'return checkParams (document.'.$markerArray['###FORM_NAME###'].');';
						$rowEven = $cssConf['row.']['even'] ?? '';
						$rowEven = (!empty($rowEven) ? $rowEven : $conf['CSSRowEven'] ?? ''); // backwards compatible
						$rowUneven = $cssConf['row.']['uneven'] ?? '';
						$rowUneven = (!empty($rowUneven) ? $rowUneven : $conf['CSSRowUneven'] ?? ''); // backwards compatible
						// alternating css-class eg. for different background-colors
						$evenUneven = (($iCount & 1) == 0 ? $rowEven : $rowUneven);
						$temp='';
						if ($iColCount == 1) {
							if ($evenUneven) {
								$temp = str_replace('###UNEVEN###', $evenUneven, $itemRowWrapArray[0] ?? '');
							} else {
								$temp = $itemRowWrapArray[0] ?? '';
							}
							$tableRowOpen = 1;
						}
	//
						$itemSingleWrapArray = GeneralUtility::trimExplode('|', $cssConf['itemSingleWrap']);
						if ($itemSingleWrapArray[0]) {
							$temp .= str_replace('###UNEVEN###', $evenUneven, $itemSingleWrapArray[0]);
						}

						$markerArray['###ITEM_SINGLE_PRE_HTML###'] = $temp;
						$temp = $itemSingleWrapArray[1];

						if (!$displayColumns || $iColCount == $displayColumns) {
							$temp .= $itemRowWrapArray[1] ?? '';
							$tableRowOpen = 0;
						}

						$markerArray['###ITEM_SINGLE_POST_HTML###'] = $temp;

						// cuts note in list view
                        if (!isset($itemTableConf['field.']['note.'])) {

                            if (
                                isset($markerArray['###' . $itemTableView->getMarker() . '_NOTE###']) &&
                                strlen($markerArray['###' . $itemTableView->getMarker() . '_NOTE###']) > $conf['max_note_length']) {
                                $markerArray['###' . $itemTableView->getMarker() . '_NOTE###'] = substr(strip_tags($markerArray['###' . $itemTableView->getMarker() . '_NOTE###']), 0, $conf['max_note_length']) . '...';
                            }
                            if (
                                isset($markerArray['###' . $itemTableView->getMarker() . '_NOTE2###']) &&
                                strlen($markerArray['###' . $itemTableView->getMarker() . '_NOTE2###']) > $conf['max_note_length']) {
                                $markerArray['###' . $itemTableView->getMarker() . '_NOTE2###'] = substr(strip_tags($markerArray['###' . $itemTableView->getMarker() . '_NOTE2###']), 0, $conf['max_note_length']) . '...';
                            }
                        }

						if (is_object($itemTableView->variant)) {

							$itemTableView->variant->removeEmptyMarkerSubpartArray(
								$markerArray,
								$subpartArray,
								$wrappedSubpartArray,
								$row,
								$conf,
								$itemTable->hasAdditional($row, 'isSingle'),
								!$itemTable->hasAdditional($row, 'noGiftService')
							);
						}
						$tempContent = '';

						if ($t['item'] != '') {
							$tempContent .= $templateService->substituteMarkerArrayCached(
								$t['item'],
								$markerArray,
								$subpartArray,
								$wrappedSubpartArray
							);
						}
						$itemsOut .= $tempContent;

						// max. number of columns reached?
						if (
							!$displayColumns ||
							$iColCount == $displayColumns ||
							$displayCatHeader == 'always'
						) {
							if ($t['itemFrameWork']) {
								// complete the last table row
								if (!$displayColumns || $iColCount == $displayColumns) {
									$itemsOut .= $this->finishHTMLRow($cssConf, $iColCount, $tableRowOpen, $displayColumns);
								}

								$markerArray = array_merge($productMarkerArray, $categoryMarkerArray, $markerArray);
								$subpartArray = [];

								if ($bHeaderFieldChanged) {
									foreach ($headerItemsOutArray as $headerTable => $headerItemsOut) {
										$marker = $headerTableObjArray['address']->getMarker();
										$subpartArray['###ITEM_' . $marker . '###'] = $templateService->substituteMarkerArrayCached($headerItemsOut, $markerArray);
									}
								}
								$subpartArray['###ITEM_SINGLE###'] = $itemsOut;
								$itemListOut .= $templateService->substituteMarkerArrayCached($t['itemFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);
								$itemsOut = '';
							}
							$iColCount = 0; // restart in the first column
						}
						$nextCat = 0;
						$nextRow = [];
						$catArray = [];
						if (isset($itemArray[$iCount])) {
							$nextRow = $itemArray[$iCount];
							if ($useCategories) {
								$nextCat = $nextRow['category'];
								$catArray = $categoryTable->getCategoryArray($nextRow);
							}
						}

						if (is_array($catArray) && count($catArray)) {
							reset($catArray);
							$this->getCategories(
								$categoryTable,
								$catArray,
								$rootCatArray,
								$rootLineArray,
								$cat,
								$nextCurrentCat,
								$nextCat
							);
						}

                        if (empty($nextRow)) {
                            $nextArray['category'] = 0;
                            $nextArray['product'] = 0;
                        } else {
                            $nextArray['category'] = (($pageAsCategory < 2) ? $nextCat : $nextRow['pid']);
                            if ($itemTable->getType() == 'article') {
                                $nextArray['product'] = $nextRow['uid_product'];
                            } else {
                                $nextArray['product'] = $nextRow['uid'];
                            }
                        }

						// multiple columns display and ITEM_SINGLE_POST_HTML is in the item's template?
						if (
							$displayCatHeader != 'always' && $displayCatHeader != 'current' && (
								(
									$nextArray['category'] != $currentArray['category'] &&
									$itemsOut &&
									$t['categoryFrameWork']
								) ||
								(
									$nextArray['product'] != $currentArray['product'] &&
									$itemTable->getType() != 'product' &&
									$t['productAndItemsFrameWork']
								)
							) ||
							$nextRow == ''
						) {
							if ($bItemPostHtml && (
								$nextArray['category']  !=  $currentArray['category'] && $itemsOut && $t['categoryFrameWork'] || // && $t['categoryFrameWork'] != ''
								$nextArray['product']   !=  $currentArray['product']  && $itemTable->getType() == 'article' && $t['productAndItemsFrameWork']) ) {
								// complete the last table row
								$itemsOut .=
									$this->finishHTMLRow(
										$cssConf,
										$iColCount,
										$tableRowOpen,
										$displayColumns
									);
							}

							if (
								(
									$nextArray['category'] != $currentArray['category'] && $t['categoryFrameWork'] ||
									$nextRow == ''
								) &&
								$itemsOut &&
								$t['itemFrameWork']
							) {
								$subpartArray = [];
								$subpartArray['###ITEM_SINGLE###'] = $itemsOut;

								$itemListNewOut = $templateService->substituteMarkerArrayCached(
									$t['itemFrameWork'],
									$markerArray,
									$subpartArray,
									$wrappedSubpartArray
								);
								$itemListOut .= $itemListNewOut;
								$itemsOut = '';
							}
						}
					}	// foreach ($itemArray as $k1 => $productList) {
				} else {
					if (isset($catTableConf['subpart.'])) {
						$displayCat = $cat;
						$tmp = [];
						$categoryTableView->getMarkerArray(
							$categoryMarkerArray,
							$displayCat,
							$GLOBALS['TSFE']->id,
							$config['limitImage'],
							'listcatImage',
							$viewCatTagArray,
							$tmp,
							$pageAsCategory,
							$theCode,
							$basketExtra,
							$basketRecs,
							$iCount,
							'',
							''
						);

						foreach($catTableConf['subpart.'] as $subpart => $subpartConfig) {

							if (
								is_array($subpartConfig) &&
								$subpartConfig['show'] == 'default'
							)	{
								if (
									$subpart == 'ITEM_CATEGORY.' &&
									$t['categoryFrameWork']
								)	{
									$catTitle = $categoryTableView->getMarkerArrayCatTitle($categoryMarkerArray);
									$categoryOut = $templateService->substituteMarkerArray($t['categoryFrameWork'], $categoryMarkerArray);
								}

								if (
									$subpart == 'ITEM_LIST.' &&
									$t['itemFrameWork']
								) {
									$markerArray = $categoryMarkerArray;
									$subpartArray = [];
									$markerArray['###ITEM_SINGLE_PRE_HTML###'] = '';
									$markerArray['###ITEM_SINGLE_POST_HTML###'] = '';
									$subpartArray['###ITEM_SINGLE###'] = '';
									$itemListOut =
										$templateService->substituteMarkerArrayCached(
											$t['itemFrameWork'],
											$categoryMarkerArray,
											$subpartArray,
											$wrappedSubpartArray
										);
								}
							}
						}
					} else {
						// keine Produkte gefunden
					}
				}

				if ($itemListOut || $categoryOut || $productListOut) {
					$catItemsListOut = &$itemListOut;
					if ($itemTable->getType() == 'article' && $productListOut && $t['productAndItemsFrameWork']) {
						$productListOut .=
							$this->advanceProduct(
								$t['productAndItemsFrameWork'],
								$t['productFrameWork'],
								$itemListOut,
								$productMarkerArray,
								$categoryMarkerArray
							);
						$catItemsListOut = &$productListOut;
					}
					if ($conf['displayListCatHeader']) {
						$out .=
							$this->advanceCategory(
								$t['categoryAndItemsFrameWork'],
								$catItemsListOut,
								$categoryOut,
								$itemListSubpart,
								$oldFormCount,
								$formCount
							);
					} else {
						$out .= $itemListOut;
					}
				}
			}	// if ($theCode != 'SEARCH' || ($theCode == 'SEARCH' && $sword))	{
		} // if ($t['categoryAndItemsFrameWork'] != '') {

		$contentEmpty = '';

		if ($t['categoryAndItemsFrameWork'] == '') {
			// nothing is shown
		} else if (count($itemArray)) {

			// next / prev:
			// $url = $this->getLinkUrl('','begin_at');
				// Reset:
			$subpartArray= [];
			$wrappedSubpartArray= [];
			$markerArray=$globalMarkerArray;
			$splitMark = md5(microtime());
			$addQueryString= [];
			$addQueryString['addmemo'] = '';
			$addQueryString['delmemo'] = '';

			if ($sword) {
				$addQueryString['sword'] = $sword;
			}
			$bUseCache = $bUseCache && (count($basketObj->itemArray) == 0);

			$this->getBrowserMarkers(
				$browseObj,
				$browserConf,
				$t,
				$addQueryString,
				$productsCount,
				$more,
				$limit,
				$begin_at,
				$bUseCache,
				$markerArray,
				$subpartArray,
				$wrappedSubpartArray
			);
			$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = $out;
			$addQueryString = [];
			$addQueryString = $this->uidArray;
			$excludeList = ($theCode == 'SEARCH' ? 'sword' : '');
			$this->getSearchParams($addQueryString);
			$markerArray =
				$urlObj->addURLMarkers(
					$this->pid,
					$markerArray,
					$addQueryString,
					$excludeList,
					$useBackPid,
					$backPid
				); // clickIntoBasket

			$markerArray['###AMOUNT_CREDITPOINTS###'] = number_format($GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'] ?? 0, 0);
			$markerArray['###ITEMS_SELECT_COUNT###'] = $productsCount;
			$javaScriptMarker->getMarkerArray($jsMarkerArray, $markerArray, $cObj);
			$markerArray = array_merge($jsMarkerArray, $markerArray);

			if ($calllevel == 0) {
				$hiddenCount = 0;
				if ($itemTable->getType() == 'dam') {
					$hiddenFields .= '<input type="hidden" name="' . $prefixId . '[type][' . $hiddenCount . ']" value="product" />';
					$hiddenCount++;
				}
				$hiddenFields .= '<input type="hidden" name="' . $prefixId . '[type][' . $hiddenCount . ']" value="' . $itemTable->getType() . '" />';
			}

			$markerArray['###HIDDENFIELDS###'] = $hiddenFields; // TODO

			if (isset($memoViewObj) && is_object($memoViewObj)) {
				$memoViewObj->getHiddenFields($uidArray, $markerArray, $bUseCheckBox);
			}

			$out = $templateService->substituteMarkerArrayCached(
				$t['listFrameWork'],
				$markerArray,
				$subpartArray,
				$wrappedSubpartArray
			);
			$content .= $out;
		} else if ($theCode == 'SEARCH') {
			if ($conf['listViewOnSearch'] == '1' && $sword && $allowedItems != '0') {
				$templateArea = 'ITEM_SEARCH_EMPTY';

				$contentEmpty =
					$subpartmarkerObj->getSubpart(
						$templateCode,
						$subpartmarkerObj->spMarker('###' . $templateArea . '###'), $error_code
					);

			} else {
				// nothing is shown
			}
		} else if ($out) {
			$content .= $out;
		} else if ($whereCat != '' || $allowedItems != '0' || !$bListStartEmpty) {
			$subpartArray = [];
			$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = '';
			$subpartArray['###LINK_PREV###'] = '';
			$subpartArray['###LINK_NEXT###'] = '';
            $subpartArray['###LINK_BROWSE###'] = '';

			$markerArray['###BROWSE_LINKS###'] = '';

			$out =
				$templateService->substituteMarkerArrayCached(
					$t['listFrameWork'],
					$markerArray,
					$subpartArray
				);
			$content .= $out;
			$contentEmpty =
				$subpartmarkerObj->getSubpart(
					$templateCode,
					$subpartmarkerObj->spMarker('###ITEM_LIST_EMPTY###'),
					$error_code
				);
		} else {
			// nothing is shown
		} // if (count ($itemArray))

		if ($bCheckUnusedArticleMarkers) {
			$markerFieldArray = [];
			$articleViewTagArray = [];
			$articleParentArray = [];
			$articleViewObj = $tablesObj->get('tt_products_articles', true);

			$searchString = '###' . $articleViewObj->getMarker() . '_';
			if (strpos($t['item'], $searchString) > 0) {
				$error_code[0] = 'article_markers_unsubstituted';
				$error_code[1] = '###' . $articleViewObj->getMarker() . '_...###';
				$error_code[2] = $useArticles;
			}
		}

		if ($contentEmpty != '') {
			$contentEmpty = $templateService->substituteMarkerArray($contentEmpty, $globalMarkerArray);
		}
		$content .= $contentEmpty;

		return $content;
	}
}

