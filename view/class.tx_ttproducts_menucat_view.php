<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger (franz@ttproducts.de)
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
    protected $htmlTagMain = 'ul';	// main HTML tag
    protected $htmlTagElement = 'li'; // HTML tag element

    // returns the products list view
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
        $t = [];
        $ctrlArray = [];
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $pibaseObj = GeneralUtility::makeInstance('' . $this->pibaseClass);
        $javaScriptMarker = GeneralUtility::makeInstance('tx_ttproducts_javascript_marker');
        $prefixId = tx_ttproducts_model_control::getPrefixId();
        $cObj = \JambageCom\TtProducts\Api\ControlApi::getCObj();

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
            $subCategoryMarkers,
            $ctrlArray
        );

        if (empty($error_code)) {
            $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

            $categoryTableView = $tablesObj->get($functablename, true);
            $categoryTable = $categoryTableView->getModelObj();
            $maxDepth = $categoryTable->getDepth($theCode);

            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
            $conf = $cnf->getConf();
            $config = $cnf->getConfig();

            $content='';
            $out='';
            $where='';
            $bFinished = false;
            $iCount = 0;
            $mainCount = 1;
            $depth = 1;
            $countArray = [];
            $countArray[0] = 0;
            $countArray[1] = 0;
            $tabArray = [];
            $catConf = $categoryTable->getTableConf($theCode);
            $catConf['cssMode'] = $catConf['cssMode'] ?? 0;

            $cssObj = GeneralUtility::makeInstance('tx_ttproducts_css');
            $cssConf = $cssObj->getConf($functablename, $theCode);
            $fill = '';
            $menu = 'm' . $depth;
            $idMain = $categoryTableView->getPivar() . $mainCount;
            $tabArray[$depth] = $this->getTabs($depth * 2);
            $parentArray = [];
            $viewCatTagArray = [];
            $tmp = [];
            $catfieldsArray = $markerObj->getMarkerFields(
                $t['linkCategoryFrameWork'],
                $categoryTable->getTableObj()->tableFieldArray,
                $categoryTable->getTableObj()->requiredFieldArray,
                $tmp,
                $categoryTableView->getMarker(),
                $viewCatTagArray,
                $parentArray
            );

            $out = chr(13) . $tabArray[$depth] . '<' . $this->htmlTagMain . ' id="' . $idMain . '"' . '  class="' . $menu . '" ' . $fill . '>' . chr(13);
            $out = str_replace($this->htmlPartsMarkers[0], $out, $htmlParts[0]);

            while ($depth > 0 && $iCount < 500) {
                $iCount++;
                $cssClassArray = ['w' . $iCount];

                if (
                    !isset($catArray[$depth]) ||
                    !is_array($catArray[$depth])
                ) {
                    continue;
                }

                if($countArray[$depth] < count($catArray[$depth])) {
                    $markerArray = [];
                    $actCategory = $catArray[$depth][$countArray[$depth]];
                    $row = $categoryArray[$actCategory];
                    $subCategories = $row['child_category'] ?? [];

                    if ($catConf['cssMode'] == '1' && isset($subCategories) && is_array($subCategories) && count($subCategories)) {
                        $cssClassArray[] = 'parent';
                    }
                    $countArray[$depth]++;
                    $isNormal = true;
                    if ($actCategory == $currentCat) {
                        $cssClassArray[] = 'cur';
                        $isNormal = false;
                    }

                    if ($catConf['cssMode'] == '1' && isset($rootpathArray) && is_array($rootpathArray)) {
                        foreach ($rootpathArray as $lineRow) {
                            if ($actCategory == $lineRow['uid']) {
                                $cssClassArray[] = 'act';
                                $isNormal = false;
                            }
                        }
                    }
                    if ($catConf['cssMode'] == '1' && $isNormal) {
                        $cssClassArray[] = 'no';
                    }
                    $css = 'class="' . implode(' ', $cssClassArray) . '"';

                    $preOut = $tabArray[$depth] . chr(9) . '<' . $this->htmlTagElement . ($css ? ' ' . $css : '') . ' value="' . $actCategory . '">' . chr(13);
                    $out .= str_replace($this->htmlPartsMarkers[0], $preOut, $htmlParts[0]);

                    if ($pageAsCategory > 0) {
                        $pid = $row['pid'];
                    } else {
                        $pageObj = $tablesObj->get('pages');
                        $pid = $pageObj->getPID(
                            $conf['PIDlistDisplay'] ?? 0,
                            $conf['PIDlistDisplay.'] ?? [],
                            $row
                        );
                    }
                    $addQueryString = [$categoryTableView->getPivar() => $actCategory];

                    $markerArray = [];
                    $categoryTableView->getMarkerArray (
                        $markerArray,
                        $categoryTableView->getMarker(),
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
                        '',
                        '',
                        ''
                    );

                    $urlParameters = [$prefixId => $addQueryString];

                    $linkConf = [];
                    $linkConf['parameter'] = $pid;
                    $linkConf['additionalParams'] = GeneralUtility::implodeArrayForUrl('', $urlParameters);
                    $linkConf['ATagParams'] = $pibaseObj->cObj->getATagParams($linkConf);

                    $theLinkWrap = $pibaseObj->cObj->typolink('|', $linkConf);
                    $tagArray = $markerObj->getAllMarkers($theLinkWrap);
                    $linkMarkerArray = [];

                    foreach ($tagArray as $tag => $v) {
                        $marker = '###' . $tag. '###';
                        if (isset($markerArray[$marker])) {
                            $linkMarkerArray[$marker] = htmlspecialchars($markerArray[$marker]);
                        }
                    }

                    $theLinkWrap =
                        $templateService->substituteMarkerArray(
                            $theLinkWrap,
                            $linkMarkerArray
                        );

                    $linkOutArray = explode('|', $theLinkWrap);
                    $linkOut =
                        $linkOutArray[0] .
                            htmlentities(
                                $row[$categoryTable->getLabelFieldname()], ENT_QUOTES, 'UTF-8'
                            ) . $linkOutArray[1];

                    $markerArray['###LIST_LINK###'] = $linkOut;

                    if ($t['linkCategoryFrameWork']) {
                        $categoryOut =
                            $templateService->substituteMarkerArray(
                                $t['linkCategoryFrameWork'],
                                $markerArray
                            );
                        $out .= $categoryOut . chr(13);
                    }

                    if (
                        $depth < $maxDepth &&
                        is_array($subCategories) &&
                        (
                            !$catConf['onlyChildsOfCurrent'] ||
                            isset($activeRootline[$actCategory])
                        )
                    ) {
                        $depth++;
                        $mainCount++;
                        $idMain = $categoryTableView->getPivar() . $mainCount;
                        $menu = 'm' . $depth;
                        $tabArray[$depth] = $this->getTabs($depth * 2);

                        $preOut = $tabArray[$depth] . '<' . $this->htmlTagMain . ' id="' . $idMain . '"' . ' class="' . $menu . '" ' . $fill .  ' >' . chr(13);
                        $countArray[(int) $depth] = 0;
                        $catArray[(int) $depth] = $subCategories;
                        $out .= str_replace($this->htmlPartsMarkers[0], $preOut, $htmlParts[0]);
                    } else if($countArray[$depth] <= count ($catArray[$depth])) {	// several elements at same depth
                        $postOut = $tabArray[$depth] . chr(9) . '</' . $this->htmlTagElement . '>' . chr(13);
                        $tmp = str_replace($this->htmlPartsMarkers[1], $postOut, $htmlParts[1]);
                        $out .= $tmp;
                    }
                } else {
                    $postOut = $tabArray[$depth] . '</' . $this->htmlTagMain . '>' . chr(13);
                    $depth--;
                    if ($depth) {
                        $postOut .= $tabArray[$depth] . chr(9) . '</' . $this->htmlTagElement . '>' . chr(13);
                    }
                    $out .= str_replace($this->htmlPartsMarkers[1], $postOut, $htmlParts[1]);
                }
            }

            $markerArray = [];
            $subpartArray = [];
            $wrappedSubpartArray = [];

            $jsMarkerArray = [];
            $javaScriptMarker->getMarkerArray($jsMarkerArray, $markerArray, $cObj);
            $markerArray = array_merge($jsMarkerArray, $markerArray);

            $this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
            $subpartArray['###CATEGORY_SINGLE###'] = $out;
            $out = $templateService->substituteMarkerArrayCached(
                $t['listFrameWork'],
                $markerArray,
                $subpartArray,
                $wrappedSubpartArray
            );
            $content = $out;
        }

        return $content;
    }
}

