<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Franz Holzinger (franz@ttproducts.de)
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

use JambageCom\Div2007\Utility\FrontendUtility;


class tx_ttproducts_catlist_view extends tx_ttproducts_catlist_view_base {

	public function getChildsContent (
		$theCode,
		$t,
		$functablename,
		$categoryArray,
		array $catArray,
		array $childArray,
		$ctrlArray,
		$linkOutArray,
        $depth,
        $maxDepth,
		$viewCatTagArray,
		$currentCat,
		$pageAsCategory,
		$childRow,
		$subCategoryMarkerArray
	) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);

        $subCategoryMarker = '';
        if (
            is_array($subCategoryMarkerArray) && count($subCategoryMarkerArray)
        ) {
            for ($markerDepth = $depth; $markerDepth >= 1; --$markerDepth) {
                if (isset($subCategoryMarkerArray[$markerDepth])) {
                    $subCategoryMarker = $subCategoryMarkerArray[$markerDepth];
                    break;
                }
            }
        }
		$icCount = 0;
		$childsOut = '';
		$childStart = 0;
		$childEnd = $childEndMax = count($childArray) - 1;

		if ($ctrlArray['bUseBrowser'] && !empty($ctrlArray['limit'])) {
			$piVars = tx_ttproducts_model_control::getPiVars();
			$childStart = ($piVars['pointer'] ?? 0) * $ctrlArray['limit'];
			$childEnd = $childStart + $ctrlArray['limit'] - 1;

			if ($childEnd > $childEndMax) {
				$childEnd = $childEndMax;
			}
		}

		for ($k = $childStart; $k <= $childEnd; ++$k) {
            $childLinkOut = '';
			$child = $childArray[$k];
			$childRow = $categoryArray[$child];
			$markerArray = [];
			$icCount++;

			if ($ctrlArray['bUseBrowser']) {
				if ($icCount > $ctrlArray['limit']) {
					break;
				}
			}
			$this->getMarkerArray(
				$functablename,
				$markerArray,
				$linkOutArray,
				$depth + 1,
				$maxDepth,
				$icCount,
				$child,
				$viewCatTagArray,
				$currentCat,
				$pageAsCategory,
				$childRow,
				$theCode,
				tx_ttproducts_control_basket::getBasketExtra(),
				tx_ttproducts_control_basket::getRecs()
			);

            $grandChildArray = $childRow['child_category'];

            if (
                !empty($grandChildArray) &&
                $depth + 1 <= $maxDepth
            ) {
                $childsOut .= 
                    $this->renderChilds(
                        $theCode,
                        $t,
                        $functablename,
                        $categoryArray,
                        $catArray,
                        $grandChildArray,
                        $ctrlArray,
                        $linkOutArray,
                        $depth + 1,
                        $maxDepth,
                        $viewCatTagArray,
                        $child,
                        $pageAsCategory,
                        $childRow,
                        $subCategoryMarkerArray
                    );
            }

			if ($t[$subCategoryMarker]['linkCategoryFrameWork']) {
				$newOut =
					$templateService->substituteMarkerArray(
						$t[$subCategoryMarker]['linkCategoryFrameWork'],
						$markerArray
					);
				$childLinkOut = $linkOutArray['0'] . $newOut . $linkOutArray['1'];
			}
			$wrappedSubpartArray = [];
			$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
			$subpartArray = [];
			$subpartArray['###CATEGORY_SINGLE###'] = $childLinkOut;
			$childsOut .=
				$templateService->substituteMarkerArrayCached(
					$t[$subCategoryMarker]['categoryFrameWork'],
					$markerArray,
					$subpartArray,
					$wrappedSubpartArray
				);
		}
		$subpartArray = [];
		$wrappedSubpartArray = [];
		$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
		$subpartArray['###CATEGORY_SINGLE###'] = $childsOut;
		$childsOut =
			$templateService->substituteMarkerArrayCached(
				$t[$subCategoryMarker]['listFrameWork'],
				[],
				$subpartArray,
				$wrappedSubpartArray
			);

		return $childsOut;
	}


    protected function renderChilds(
        $theCode,
        $t,
        $functablename,
        $categoryArray,
        $catArray,
        $childArray,
        $ctrlArray,
        $linkOutArray,
        $depth,
        $maxDepth,
        $viewCatTagArray,
        $currentCat,
        $pageAsCategory,
        $childRow,
        $subCategoryMarkerArray
    ) 
    {
        $childsOut = '';
        if (isset($childArray) && is_array($childArray)) {

            $childsOut =
                $this->getChildsContent(
                    $theCode,
                    $t,
                    $functablename,
                    $categoryArray,
                    $catArray,
                    $childArray,
                    $ctrlArray,
                    $linkOutArray,
                    1,
                    $maxDepth,
                    $viewCatTagArray,
                    $currentCat,
                    $pageAsCategory,
                    $childRow,
                    $subCategoryMarkerArray
                );
        }
        return $childsOut;
    }

	// returns the category list view
	public function printView (
		$functablename,
		&$templateCode,
		$theCode,
		&$error_code,
		$templateArea = 'ITEM_CATLIST_TEMPLATE',
		$pageAsCategory,
		$templateSuffix = '',
		$basketExtra,
		$basketRecs
	) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$t = [];
		$ctrlArray = [];
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
			$basketExtra,
			$basketRecs,
			$currentCat,
			$categoryArray,
			$catArray,
			$activeRootline,
			$rootpathArray,
			$subCategoryMarkerArray,
			$ctrlArray
		);
		$content = '';
		$out = '';
		$where = '';
		$bFinished = false;
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$catView = $tablesObj->get($functablename, true);
		$catTableObj = $catView->getModelObj();

		if (!empty($error_code)) {
			// nothing
		} else if (count($categoryArray)) {
			$count = 0;
			$countArray = [];
			$countArray[0] = 0;
			$countArray[1] = 0;
			$count = 0;
			$out = '';
			$parentArray = [];
			$viewCatTagArray = [];
			$currentMarkerArray = [];

			$maxDepth = $catTableObj->getDepth($theCode);
			$tmp = [];
			$catfieldsArray = $markerObj->getMarkerFields(
				$t['categoryFrameWork'],
				$catTableObj->getTableObj()->tableFieldArray,
				$catTableObj->getTableObj()->requiredFieldArray,
				$tmp,
				$catView->getMarker(),
				$viewCatTagArray,
				$parentArray
			);
			$iCount = 0;
			$tSubParts =
				$subpartmarkerObj->getTemplateSubParts(
					$templateCode,
					$subCategoryMarkerArray
				);

			foreach ($tSubParts as $marker => $area) {
				$this->getFrameWork(
					$t[$marker],
					$templateCode,
					$area . $templateSuffix
				);
			}
			$iCount++;
			$currentMarkerArray = [];

			if ($currentCat) {
			} else {
				$currentCat = array_key_first($categoryArray);
			}    

            if (
                isset($catArray['1']) &&
                is_array($catArray['1'])
            ) {
                foreach($catArray['1'] as $actCategory) {
                    $row = $categoryArray[$actCategory];
                    $markerArray = [];
                    $iCount++;
                    $this->getMarkerArray(
                        $functablename,
                        $markerArray,
                        $linkOutArray,
                        1, 
                        $maxDepth,
                        $iCount,
                        $actCategory,
                        $viewCatTagArray,
                        $currentCat,
                        $pageAsCategory,
                        $row,
                        $theCode,
                        $basketExtra,
                        $basketRecs
                    );
                    $childArray = $row['child_category'] ?? [];

                    if (
                        is_array($subCategoryMarkerArray) && count($subCategoryMarkerArray)
                    ) {
                        $childsOut =
                            $this->renderChilds(
                                $theCode,
                                $t,
                                $functablename,
                                $categoryArray,
                                $catArray,
                                $childArray,
                                $ctrlArray,
                                $linkOutArray,
                                1,
                                $maxDepth,
                                $viewCatTagArray,
                                $currentCat,
                                $pageAsCategory,
                                $childRow,
                                $subCategoryMarkerArray
                            );
                        $markerArray['###' . $subCategoryMarkerArray['1'] . '###'] = $childsOut;
                    }

                    $subpartArray = [];
                    $wrappedSubpartArray = [];

                    if ($t['linkCategoryFrameWork']) {
                        $subpartArray = [];
                        $wrappedSubpartArray = [];
                        $catView->getItemSubpartArrays(
                            $t['listFrameWork'],
                            $functablename,
                            $row,
                            $subpartArray,
                            $wrappedSubpartArray,
                            $viewCatTagArray,
                            $theCode,
                            $basketExtra,
                            $basketRecs
                        );

                        $categoryOut =
                            $templateService->substituteMarkerArrayCached(
                                $t['linkCategoryFrameWork'],
                                $markerArray,
                                $subpartArray,
                                $wrappedSubpartArray
                            );
                        $subpartArray['###LINK_CATEGORY###'] = $categoryOut;
                    }

                    $this->getItemSubpartArrays(
                        $t['categoryFrameWork'],
                        $functablename,
                        $row,
                        $subpartArray,
                        $wrappedSubpartArray,
                        $viewCatTagArray,
                        $theCode,
                        $basketExtra,
                        $basketRecs,
                        $iCount
                    );

                    $categoryOut =
                        $templateService->substituteMarkerArrayCached(
                            $t['categoryFrameWork'],
                            $markerArray,
                            $subpartArray
                        );
                    $out .= $categoryOut;
                }
            }

			$markerArray = $currentMarkerArray;
			$markerArray[$this->htmlPartsMarkers[0]] = '';
			$markerArray[$this->htmlPartsMarkers[1]] = '';
			$out = $templateService->substituteMarkerArrayCached($out, $markerArray);

			$markerArray = [];
			$subpartArray = [];
			$wrappedSubpartArray = [];
			$this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
			$subpartArray['###CATEGORY_SINGLE###'] = $out;
			$viewConfArray = $this->getViewConfArray();

			if (is_array($viewConfArray) && count($viewConfArray)) {
				$allMarkers = $this->getTemplateMarkers($t);
				$addQueryString = [];
				$markerArray =
					$this->urlObj->addURLMarkers(
						$GLOBALS['TSFE']->id,
						$markerArray,
						$addQueryString,
						false
					);

				$controlViewObj = GeneralUtility::makeInstance('tx_ttproducts_control_view');
				$controlViewObj->getMarkerArray(
					$markerArray,
					$allMarkers,
					$this->getTableConfArray()
				);
			}

			$out =
				$templateService->substituteMarkerArrayCached(
					$t['listFrameWork'],
					$markerArray,
					$subpartArray,
					$wrappedSubpartArray
				);
			$content = $out;
		} else {
			$contentEmpty =
				$subpartmarkerObj->getSubpart(
					$templateCode,
					$subpartmarkerObj->spMarker(
						'###' . $templateArea . $templateSuffix . '_EMPTY###'
					),
					$error_code
				);
		}

		if ($contentEmpty != '') {

			$globalMarkerArray = $markerObj->getGlobalMarkerArray();
			$content =
				$templateService->substituteMarkerArray(
					$contentEmpty,
					$globalMarkerArray
				);
		}

		return $content;
	}


	public function getItemSubpartArrays (
		$templateCode,
		$functablename,
		$row,
		&$subpartArray,
		&$wrappedSubpartArray,
		$tagArray,
		$theCode,
		$basketExtra,
		$basketRecs,
		$id
	) {
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$categoryTableView = $tablesObj->get($functablename, true);

		$categoryTableView->getItemSubpartArrays(
			$templateCode,
			$functablename,
			$row,
			$subpartArray,
			$wrappedSubpartArray,
			$tagArray,
			$theCode,
			$basketExtra,
			$basketRecs,
			$id
		);
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
		$depth,
		$maxDepth,
		$iCount,
		$actCategory,
		$viewCatTagArray,
		$currentCat,
		$pageAsCategory,
		$row,
		$theCode,
		$basketExtra,
		$basketRecs
	) {
		$pibaseObj = GeneralUtility::makeInstance('' . $this->pibaseClass);
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnfObj->getConf();
		$config = $cnfObj->getConfig();
		$css = 'class="w' . $iCount . '"';
		$css = ($actCategory == $currentCat ? 'class="act"' : $css);
		$prefixId = tx_ttproducts_model_control::getPrefixId();

		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$pageObj = $tablesObj->get('pages');
		$categoryTableView = $tablesObj->get($functablename, true);
		$categoryTable = $categoryTableView->getModelObj();

		$cssConf =
			$cnfObj->getCSSConf(
				$functablename,
				$theCode
			);

		if (isset($cssConf) && is_array($cssConf)) {
			$rowEven = $cssConf['row.']['even'] ?? '';
			$rowUneven = $cssConf['row.']['uneven'] ?? '';
			$evenUneven = (($iCount & 1) == 0 ? $rowEven : $rowUneven);
		} else {
			$evenUneven = '';
		}
		$markerArray['###UNEVEN###'] = $evenUneven;

		$pid =
			$pageObj->getPID(
				$conf['PIDlistDisplay'] ?? 0,
				$conf['PIDlistDisplay.'] ?? [],
				$row
			);
		$addQueryString = [$categoryTableView->getPivar() => $actCategory];

		$urlParameters = [$prefixId => $addQueryString];

		$linkConf = [];
		$linkConf['parameter'] = $pid;
		$linkConf['additionalParams'] = GeneralUtility::implodeArrayForUrl('', $urlParameters);

		$pibaseObj->cObj->typolink('', $linkConf);
		$linkUrl = $pibaseObj->cObj->lastTypoLinkUrl;
		$linkOutArray = ['<a href="' . htmlspecialchars($linkUrl) . '" ' . $css . '>', '</a>'];

		$linkOut =
			$linkOutArray[0] .
			htmlentities(
				$row[$categoryTable->getLabelFieldname()],
				ENT_QUOTES,
				'UTF-8'
			) .
			$linkOutArray[1];

		$categoryTableView->getMarkerArray(
			$markerArray,
			'',
			$actCategory,
			$row['pid'],
			$config['limitImage'],
			'listcatImage',
			$viewCatTagArray,
			[],
			$pageAsCategory,
			$theCode,
			$basketExtra,
			$basketRecs,
			$iCount,
			''
		);

		$markerArray['###LIST_LINK###'] = $linkOut;
		$markerArray['###LIST_LINK_CSS###'] = $css;
		$markerArray['###LIST_LINK_URL###'] = htmlspecialchars($linkUrl);
	}
}

