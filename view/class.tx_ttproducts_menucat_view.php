<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2009 Franz Holzinger (franz@ttproducts.de)
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

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_menucat_view extends tx_ttproducts_catlist_view_base {
	public $htmlTagMain = 'ul';	// main HTML tag
	public $htmlTagElement = 'li'; // HTML tag element

	// returns the products list view
	public function printView (
		$functablename,
		&$templateCode,
		$theCode,
		&$errorCode,
		$templateArea = 'ITEM_CATLIST_TEMPLATE',
		$pageAsCategory,
		$templateSuffix = ''
	) {
		$t = array();
		$ctrlArray = array();
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$parser = $this->pibase->cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		parent::getPrintViewArrays(
			$functablename,
			$templateCode,
			$t,
			$htmlParts,
			$theCode,
			$errorCode,
			$templateArea,
			$pageAsCategory,
			$templateSuffix,
			$currentCat,
			$categoryArray,
			$catArray,
			$isParentArray,
			$subCategoryMarkers,
			$ctrlArray
		);
		if (empty($errorCode))	{
			$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$categoryTableViewObj = $tablesObj->get($functablename,true);
			$categoryTable = $categoryTableViewObj->getModelObj();
			$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

			$content='';
			$out='';
			$where='';
			$bFinished = false;
			$iCount = 0;
			$depth = 1;
			$countArray = array();
			$countArray[0] = 0;
			$countArray[1] = 0;
			$catConf = $categoryTable->getTableConf($theCode);
			$cssObj = GeneralUtility::makeInstance('tx_ttproducts_css');
			$cssConf = $cssObj->getConf($functablename, $theCode);
			$menu = $categoryTableViewObj->getPivar() . $depth;
			$fill = '';
			$out = '<'.$this->htmlTagMain.' id="' . $menu . '" ' . $fill . '>';
			$out = str_replace($this->htmlPartsMarkers[0], $out, $htmlParts[0]);
			$parentArray = array();
			$viewCatTagArray = array();
			$catfieldsArray = $markerObj->getMarkerFields(
				$t['linkCategoryFrameWork'],
				$categoryTable->getTableObj()->tableFieldArray,
				$categoryTable->getTableObj()->requiredFieldArray,
				$tmp = array(),
				$categoryTableViewObj->getMarker(),
				$viewCatTagArray,
				$parentArray
			);

			while ($depth > 0 && $iCount < 500)	{
				$iCount++;
				$css = 'class="w'.$iCount.'"';

				if(
                    is_array($catArray[$depth]) &&
                    $countArray[$depth] < count($catArray[$depth])
                ) {
					$markerArray = array();
					$actCategory = $catArray[$depth][$countArray[$depth]];
					$row = $categoryArray[$actCategory];
					$subCategories = $row['child_category'];
					$countArray[$depth]++;
					$css = ($actCategory == $currentCat ? 'class="act"' : $css);
					$preOut = '<' . $this->htmlTagElement . ($css ? ' ' . $css : '') . ' value="' . $actCategory . '">';
					$out .= str_replace($this->htmlPartsMarkers[0], $preOut, $htmlParts[0]);

					if ($pageAsCategory > 0)	{
						$pid = $row['pid'];
					} else {
						$pageObj = $tablesObj->get('pages');
						$pid = $pageObj->getPID(
							$this->conf['PIDlistDisplay'],
							$this->conf['PIDlistDisplay.'],
							$row
						);
					}
					$addQueryString = array($categoryTableViewObj->getPivar() => $actCategory);
					$tempUrl = $this->pibase->pi_linkTP_keepPIvars_url($addQueryString, 1, 1, $pid);
					$linkOutArray = array('<a href="' . htmlspecialchars($tempUrl) . '" ' . $css . '>','</a>');
					$linkOut = $linkOutArray[0] . htmlentities($row['title'],ENT_QUOTES,'UTF-8').$linkOutArray[1];
					$markerArray = array();
					$categoryTableViewObj->getMarkerArray (
						$markerArray,
						$categoryTableViewObj->getMarker(),
						$actCategory,
						$row['pid'],
						$cnf->config['limitImage'],
						'listcatImage',
						$viewCatTagArray,
						array(),
						$pageAsCategory,
						$theCode,
						tx_ttproducts_control_basket::getBasketExtra(),
						'',
						'',
						''
					);
					$markerArray['###LIST_LINK###'] = $linkOut;

					if ($t['linkCategoryFrameWork'])	{
						$categoryOut =
							$parser->substituteMarkerArray(
								$t['linkCategoryFrameWork'],
								$markerArray
							);
						$out .= $categoryOut;
					}

					// $out .= str_replace('###LIST_LINK###', $linkOut, $t['linkCategoryFrameWork']);
					if (is_array($subCategories)	&&
						(!$catConf['onlyChildsOfCurrent'] || $isParentArray[$actCategory] == true))	{
						$depth++;
						$preOut = '<' . $this->htmlTagMain . ' ' . $css . ' >';
						$countArray[(int) $depth] = 0;
						$catArray[(int) $depth] = $subCategories;
						$out .= str_replace($this->htmlPartsMarkers[0], $preOut, $htmlParts[0]);
					} else if($countArray[$depth] <= count ($catArray[$depth]))	{	// several elements at same depth
						$postOut = '</' . $this->htmlTagElement . '>';
						$tmp = str_replace($this->htmlPartsMarkers[1], $postOut, $htmlParts[1]);
						$out .= $tmp;
					}
				} else {
					$depth--;
					$postOut = '</'.$this->htmlTagMain.'>';
					if ($depth)	{
						$postOut .= '</'.$this->htmlTagElement.'>';
					}
					$out .= str_replace($this->htmlPartsMarkers[1], $postOut, $htmlParts[1]);
				}
			}

			$markerArray = array();
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
			$subpartArray['###CATEGORY_SINGLE###'] = $out;
			$out = tx_div2007_core::substituteMarkerArrayCached($t['listFrameWork'], $markerArray, $subpartArray, $wrappedSubpartArray);
			$content = $out;
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_menucat_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_menucat_view.php']);
}

