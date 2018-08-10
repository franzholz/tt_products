<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2011 Franz Holzinger (franz@ttproducts.de)
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
 * category list view functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */




class tx_ttproducts_catlist_view extends tx_ttproducts_catlist_view_base {

	// returns the products list view
	public function &printView (
		$functablename,
		&$templateCode,
		$theCode,
		&$error_code,
		$templateArea = 'ITEM_CATLIST_TEMPLATE',
		$pageAsCategory,
		$templateSuffix=''
	) {
		global $TSFE, $TCA;

		$basketObj = t3lib_div::makeInstance('tx_ttproducts_basket');
		$t = array();
		$ctrlArray = array();
		parent::getPrintViewArrays(
			$functablename,
			$templateCode,
			$t,
			$htmlParts,
			$theCode,
			$error_code,
			$templateArea,
			$pageAsCategory,
			$templateSuffix,
			$currentCat,
			$categoryArray,
			$catArray,
			$isParentArray,
			$subCategoryMarkerArray,
			$ctrlArray
		);
		$content='';
		$out='';
		$where='';
		$bFinished = false;
		$markerObj = t3lib_div::makeInstance('tx_ttproducts_marker');
		$subpartmarkerObj = t3lib_div::makeInstance('tx_ttproducts_subpartmarker');
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$catView = $tablesObj->get($functablename, TRUE);
		$catTableObj = $catView->getModelObj();

		if (count($error_code)) {
			// nothing
		} else if (count($categoryArray)) {
			$count = 0;
			$depth = 1;
			$countArray = array();
			$countArray[0] = 0;
			$countArray[1] = 0;
			$count = 0;
			$out = $htmlParts[0];
			$parentArray = array();
			$viewCatTagArray = array();
			$catfieldsArray = $markerObj->getMarkerFields(
				$t['linkCategoryFrameWork'],
				$catTableObj->getTableObj()->tableFieldArray,
				$catTableObj->getTableObj()->requiredFieldArray,
				$tmp = array(),
				$catTableObj->marker,
				$viewCatTagArray,
				$parentArray
			);

			$iCount = 0;
			$tSubParts = $this->subpartmarkerObj->getTemplateSubParts($templateCode, $subCategoryMarkerArray);

			foreach ($tSubParts as $marker => $area)	{
				$this->getFrameWork(
					$t[$marker],
					$templateCode,
					$area.$templateSuffix
				);
			}

			$iCount++;
			$currentMarkerArray = array();

			if ($currentCat) {
				$row = $catTableObj->get($currentCat);
			} else {
				foreach ($categoryArray as $currentCat => $row) {
					break;
				}
			}

			$this->getMarkerArray(
				$functablename,
				$currentMarkerArray,
				$linkOutArray,
				$iCount,
				$currentCat,
				$viewCatTagArray,
				$currentCat,
				$pageAsCategory,
				$row,
				$theCode,
				$basketObj->getBasketExtra()
			);

			foreach($catArray[$depth] as $actCategory)	{
				$row = $categoryArray[$actCategory];
				$markerArray = array();
				$iCount++;
				$this->getMarkerArray(
					$functablename,
					$markerArray,
					$linkOutArray,
					$iCount,
					$actCategory,
					$viewCatTagArray,
					$currentCat,
					$pageAsCategory,
					$row,
					$theCode,
					$basketObj->getBasketExtra()
				);
				$childArray = $row['child_category'];

				if (isset($childArray) && is_array($childArray))	{

					foreach ($subCategoryMarkerArray as $depth => $subCategoryMarker)	{
						if ($depth == 1)	{
							$icCount = 0;
							$childsOut = '';
							$childStart = 0;
							$childEnd = $childEndMax = count($childArray) - 1;

							if ($ctrlArray['bUseBrowser'])	{
								$piVars = tx_ttproducts_model_control::getPiVars();
								$childStart = $piVars['pointer'] * $ctrlArray['limit'];
								$childEnd = $childStart + $ctrlArray['limit'] - 1;

								if ($childEnd > $childEndMax)	{
									$childEnd = $childEndMax;
								}
							}
							for ($k=$childStart; $k<=$childEnd; ++$k)	{
								$child = $childArray[$k];
								$childRow = $categoryArray[$child];
								$childMarkerArray = array();
								$icCount++;

								if ($ctrlArray['bUseBrowser'])	{
									if ($icCount > $ctrlArray['limit'])	{
										break;
									}
								}
								$this->getMarkerArray(
									$functablename,
									$childMarkerArray,
									$linkOutArray,
									$icCount,
									$child,
									$viewCatTagArray,
									$currentCat,
									$pageAsCategory,
									$childRow,
									$theCode,
									$basketObj->getBasketExtra()
								);

								if ($t[$subCategoryMarker]['linkCategoryFrameWork'])	{
									$newOut = $this->cObj->substituteMarkerArray($t[$subCategoryMarker]['linkCategoryFrameWork'], $childMarkerArray);
									$childOut = $linkOutArray[0].$newOut.$linkOutArray[1];
								}
								$wrappedSubpartArray = array();
								$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
								$subpartArray = array();
								$subpartArray['###CATEGORY_SINGLE###'] = $childOut;
								$childsOut .= $this->cObj->substituteMarkerArrayCached($t[$subCategoryMarker]['categoryFrameWork'], $childMarkerArray, $subpartArray, $wrappedSubpartArray);
							}
							$subpartArray = array();
							$wrappedSubpartArray = array();
							$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
							$subpartArray['###CATEGORY_SINGLE###'] = $childsOut;
							$childsOut = $this->cObj->substituteMarkerArrayCached($t[$subCategoryMarker]['listFrameWork'], array(), $subpartArray, $wrappedSubpartArray);
							$markerArray['###'.$subCategoryMarker.'###'] = $childsOut;
						}
					}
				} else {
					foreach ($subCategoryMarkerArray as $depth => $subCategoryMarker)	{
						$markerArray['###'.$subCategoryMarker.'###'] = '';
					}
				}

				if ($t['linkCategoryFrameWork'])	{
// neu
					$subpartArray = array();
					$wrappedSubpartArray = array();
					$catView->getItemSubpartArrays(
						$t['listFrameWork'],
						$functablename,
						$row,
						$subpartArray,
						$wrappedSubpartArray,
						$viewCatTagArray,
						$theCode
					);
					$categoryOut = $this->cObj->substituteMarkerArrayCached($t['linkCategoryFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);
					$out .= $categoryOut;
				}
			} // foreach
			$out .= chr(13).$htmlParts[1];
			$markerArray = $currentMarkerArray;
			$markerArray[$this->htmlPartsMarkers[0]] = '';
			$markerArray[$this->htmlPartsMarkers[1]] = '';
			$out = $this->cObj->substituteMarkerArrayCached($out, $markerArray);
			$markerArray = array();
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);

			$subpartArray['###CATEGORY_SINGLE###'] = $out;
			$viewConfArray = $this->getViewConfArray();

			if (count($viewConfArray))	{
				$allMarkers = $this->getTemplateMarkers($t);
				$addQueryString = array();
				$markerArray = $this->urlObj->addURLMarkers($TSFE->id,$markerArray,$addQueryString,FALSE);

				$controlViewObj = t3lib_div::makeInstance('tx_ttproducts_control_view');
				$controlViewObj->getMarkerArray($markerArray, $allMarkers, $this->getTableConfArray());
			}
			$out = $this->cObj->substituteMarkerArrayCached($t['listFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);
			$content = $out;
		} else {
			$contentEmpty = $subpartmarkerObj->getSubpart($templateCode, $subpartmarkerObj->spMarker('###' . $templateArea . $templateSuffix . '_EMPTY###'), $error_code);
		}

		if ($contentEmpty != '') {

			$globalMarkerArray = $markerObj->getGlobalMarkerArray();
			$content = $this->cObj->substituteMarkerArray($contentEmpty, $globalMarkerArray);
		}

		return $content;
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a category
	 *
	 */
	public function getMarkerArray (
		$functablename,
		&$markerArray,
		&$linkOutArray,
		$iCount,
		$actCategory,
		$viewCatTagArray,
		$currentCat,
		$pageAsCategory,
		$row,
		$theCode,
		$basketExtra
	) {
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$css = 'class="w' . $iCount . '"';
		$css = ($actCategory == $currentCat ? 'class="act"' : $css);

		// $pid = $row['pid'];
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$pageObj = $tablesObj->get('pages');
		$categoryTableViewObj = $tablesObj->get($functablename,TRUE);
		$categoryTable = $categoryTableViewObj->getModelObj();
		$cssConf = $cnf->getCSSConf($categoryTable->getFuncTablename(), $theCode);
		if (isset($cssConf) && is_array($cssConf))	{
			$rowEven = $cssConf['row.']['even'];
			$rowUneven = $cssConf['row.']['uneven'];
			$evenUneven = (($iCount & 1) == 0 ? $rowEven : $rowUneven);
		} else {
			$evenUneven = '';
		}
		$markerArray['###UNEVEN###'] = $evenUneven;

		$pid = $pageObj->getPID($this->conf['PIDlistDisplay'], $this->conf['PIDlistDisplay.'], $row);
		$addQueryString = array($categoryTableViewObj->getPivar() => $actCategory);
		$linkUrl = $this->pibase->pi_linkTP_keepPIvars_url($addQueryString,1,1,$pid);
		$linkOutArray = array('<a href="' . htmlspecialchars($linkUrl) . '" ' . $css . '>', '</a>');
		$linkOut = $linkOutArray[0] . $row['title'] . $linkOutArray[1];

		$categoryTableViewObj->getMarkerArray(
			$markerArray,
			'',
			$actCategory,
			$row['pid'],
			$this->config['limitImage'],
			'listcatImage',
			$viewCatTagArray,
			array(),
			$pageAsCategory,
			'LISTCAT',
			$basketExtra,
			$iCount,
			''
		);
		$markerArray['###LIST_LINK###'] = $linkOut;
		$markerArray['###LIST_LINK_CSS###'] = $css;
		$markerArray['###LIST_LINK_URL###'] = htmlspecialchars($linkUrl);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_catlist_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_catlist_view.php']);
}

?>
