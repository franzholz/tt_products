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
 * AJAX control over select boxes for categories
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 *
 *+
 */
use JambageCom\TtProducts\Api\Localization;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_selectcat_view extends tx_ttproducts_catlist_view_base
{
    protected $htmlTagMain = 'select';	// main HTML tag
    protected $htmlTagElement = 'option';

    // returns the products list view
    public function printView(
        $functablename,
        &$templateCode,
        $theCode,
        &$error_code,
        $templateArea = 'ITEM_CATEGORY_SELECT_TEMPLATE',
        $pageAsCategory,
        $templateSuffix = '',
        $basketExtra,
        $basketRecs
    ) {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $content = '';
        $out = '';
        $where = '';

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $categoryTableView = $tablesObj->get($functablename, 1);
        $categoryTable = $categoryTableView->getModelObj();
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $config = $cnf->getConfig();

        $bSeparated = false;
        $method = 'clickShow';
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
            $subCategoryMarkers,
            $ctrlArray
        );

        if (empty($error_code)) {
            $count = 0;
            $depth = 1;
            if ($pos = strpos($t['listFrameWork'], '###CATEGORY_SINGLE_')) {
                $bSeparated = true;
            }

            $contentId = '';

            $contentPos = strpos($this->cObj->currentRecord, 'tt_content');
            if ($contentPos !== false) {
                $contentIdPos = strpos($this->cObj->currentRecord, ':');
                $contentId = substr($this->cObj->currentRecord, $contentIdPos + 1);
            }

            $menu = $conf['CSS.'][$functablename . '.']['menu'];
            $menu = ($menu ? $menu : $categoryTableView->getPivar() . '-' . $contentId . '-' . $depth);
            $fillOnchange = '';
            if ($method == 'clickShow') {
                if ($bSeparated) {
                    $fillOnchange = 'fillSelect(this, 2, ' . $contentId . ', 1);';
                } else {
                    $fillOnchange = 'fillSelect(this, 0, ' . $contentId . ', 0);';
                }
            }

            $selectArray = [];
            if (
                isset($conf['form.'][$theCode . '.']) &&
                is_array($conf['form.'][$theCode . '.']) &&
                is_array($conf['form.'][$theCode . '.']['dataArray.'])
            ) {
                foreach ($conf['form.'][$theCode . '.']['dataArray.'] as $k => $setting) {
                    if (is_array($setting)) {
                        $selectArray[$k] = [];
                        $type = $setting['type'];
                        if ($type) {
                            $parts = GeneralUtility::trimExplode('=', $type);
                            if ($parts[1] == 'select') {
                                $selectArray[$k]['name'] = $parts[0];
                            }
                        }
                        $label = $setting['label'];
                        if ($label) {
                            $selectArray[$k]['label'] = $label;
                        }
                        $params = $setting['params'];
                        if ($params) {
                            $selectArray[$k]['params'] = $params;
                        }
                    }
                }
            }

            $label = '';
            $name = 'tt_products[' . strtolower($theCode) . ']';

            reset($selectArray);
            $select = current($selectArray);

            if (is_array($select)) {
                if ($select['name']) {
                    $name = $select['name'];
                }
                if ($select['label']) {
                    $label = $select['label'] . ' ';
                }
                if ($select['params']) {
                    $params = $select['params'];
                }
            }

            $selectedKey = '0';
            $valueArray = [];
            $valueArray['0'] = '';
            $selectedCat = $currentCat;

            if (is_array($catArray[$depth])) {
                foreach ($catArray[$depth] as $k => $actCategory) {
                    if (!$categoryArray[$actCategory]['reference_category']) {
                        $valueArray[$actCategory] = $categoryArray[$actCategory]['title'];
                    } else {
                    }
                }
            }

            $mainAttributeArray = [];
            $mainAttributeArray['id'] = $menu;
            if ($fillOnchange != '') {
                $mainAttributeArray['onchange'] = $fillOnchange;
            }

            $foreignRootLine = $categoryTable->getRootline(['0'], $currentCat, 0);

            if (is_array($foreignRootLine)) {
                foreach ($foreignRootLine as $cat => $foreignRow) {
                    if (
                        isset($valueArray[$cat]) ||
                        (
                            isset($categoryArray[$cat]) &&
                            $categoryArray[$cat]['reference_category'] > 0 &&
                            isset($valueArray[$categoryArray[$cat]['reference_category']])
                        )
                    ) {
                        $mainAttributeArray['disabled'] = 'disabled';
                        $selectedCat = $cat;
                        if (!isset($valueArray[$cat])) {
                            $selectedCat = $categoryArray[$cat]['reference_category'];
                        }
                        $mainAttributeArray['class'] .= (isset($mainAttributeArray['class']) ? ' ' : '') . 'sel-inactive';
                        break;
                    }
                }
            }

            $paramArray = GeneralUtility::get_tag_attributes($params);
            if (isset($paramArray) && is_array($paramArray)) {
                $mainAttributeArray = array_merge($mainAttributeArray, $paramArray);
            }

            if (!$valueArray[$selectedCat]) {
                $selectedCat = '0';
            }

            $selectOut = tx_ttproducts_form_div::createSelect(
                $languageObj,
                $valueArray,
                $name,
                $selectedCat,
                $bSelectTags = true,
                $bTranslateText = false,
                [],
                $this->htmlTagMain,
                $mainAttributeArray,
                $layout = '',
                $imageFileArray = '',
                $keyMarkerArray = ''
            );
            $out = $label . $selectOut;

            $markerArray = [];
            $subpartArray = [];
            $wrappedSubpartArray = [];
            $markerArray =
                $this->urlObj->addURLMarkers(
                    $conf['PIDlistDisplay'] ?? 0,
                    $markerArray
                );
            $this->urlObj->getWrappedSubpartArray($wrappedSubpartArray);
            $subpartArray['###CATEGORY_SINGLE###'] = $out;

            $count = intval(substr_count($t['listFrameWork'], '###CATEGORY_SINGLE_') / 2);
            if ($pageAsCategory == 2) {
                // $catid = 'pid';
                $parentFieldArray = ['pid'];
            } else {
                // $catid = 'cat';
                $parentFieldArray = ['parent_category'];
            }
            $piVar = $categoryTableView->piVar;

            if ($method == 'clickShow') {
                $javaScriptObj = GeneralUtility::makeInstance('tx_ttproducts_javascript');
                $javaScriptObj->set(
                    $languageObj,
                    'selectcat',
                    [$categoryArray],
                    $this->cObj->currentRecord,
                    1 + $count,
                    'cat',
                    $parentFieldArray,
                    [$piVar],
                    [],
                    'clickShow'
                );
            }

            if ($bSeparated) {
                for ($i = 2; $i <= 1 + $count; ++$i) {
                    $menu = $piVar . '-' . $contentId . '-' . $i;
                    $bShowSubcategories = ($i < 1 + $count ? 1 : 0);
                    $boxNumber = ($i < 1 + $count ? ($i + 1) : 0);
                    $fill = ' onchange="fillSelect(this, ' . $boxNumber . ', ' . $contentId . ', ' . $bShowSubcategories . ');"';
                    $tmp = '<' . $this->htmlTagMain .
                        ' name="' . $name . '"' .
                        ' id="' . $menu . '"' . $fill . '>';
                    $tmp .= '<' . $this->htmlTagElement . ' value="0"></' . $this->htmlTagElement . '>';
                    $tmp .= '</' . $this->htmlTagMain . '>';
                    $subpartArray['###CATEGORY_SINGLE_' . $i . '###'] = $tmp;
                }

                // $subpartArray['###CATEGORY_SINGLE_BUTTON'] = '<input type="button" value="Laden" onclick="fillSelect(0, '.$boxNumber.','.$bShowSubcategories.');">';
            }

            $out =
                $templateService->substituteMarkerArrayCached(
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
