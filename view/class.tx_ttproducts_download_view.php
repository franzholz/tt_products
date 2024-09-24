<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Franz Holzinger (franz@ttproducts.de)
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
 * functions for downloads
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

use JambageCom\Div2007\Utility\FrontendUtility;

use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\ParameterApi;

class tx_ttproducts_download_view extends tx_ttproducts_article_base_view
{
    public $marker = 'DOWNLOAD';

    /**
     * Generates a radio or selector box for download.
     */
    public function generateRadioSelect(
        $theCode,
        $row
    ): void {
    }

    /**
     * Template marker substitution
     * Fills in the markerArray with data for a product.
     *
     * @param	array		reference to an item array with all the data of the item
     * @param	array		Returns a markerArray ready for substitution with information
     *
     * @access private
     */
    public function getDownloadMarkerSubpartArrays(
        $templateCode,
        $conf,
        $rowArray,
        &$markerArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        $parentMarker,
        $tagArray,
        $hiddenFields,
        $multiOrderArray,
        $productRowArray,
        $checkPriceZero = false
    ): void {
        $error = false;
        $cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
        $cObj->start([]);
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $funcTablename = 'tt_products';
        $itemTableView = $tablesObj->get($funcTablename, true);
        $itemTable = $itemTableView->getModelObj();
        $orderObj = $tablesObj->get('sys_products_orders');
        $variantSeparator = $itemTable->getVariant()->getSplitSeparator();
        $t = [];

        if (isset($tagArray['DOWNLOAD_SINGLE'])) {
            $t['item'] = $templateService->getSubpart($templateCode, '###DOWNLOAD_SINGLE###');
        }

        // 			<!-- ###DOWNLOAD_SINGLE### begin -->
        // 				###DOWNLOAD_TITLE###
        // 				###DOWNLOAD_NOTE###
        // 				###DOWNLOAD_LINK###
        // 				<br/>
        // 			<!-- ###DOWNLOAD_SINGLE### end -->

        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $postVar = tx_ttproducts_control_command::getCommandVar();
        $downloadVar = $parameterApi->getPiVar($this->getModelObj()->getFuncTablename());
        $bAddonsEM = ExtensionManagementUtility::isLoaded('addons_em');
        tx_ttproducts_control_access::getVariables(
            $conf,
            $updateCode,
            $bIsAllowed,
            $bValidUpdateCode,
            $trackingCode
        );

        $subpartArray['###DOWNLOAD_SINGLE###'] = '';

        if (
            isset($rowArray) &&
            is_array($rowArray) &&
            count($rowArray)
        ) {
            foreach ($rowArray as $k => $row) {
                $content = '';
                $marker = $parentMarker . '_' . $this->getMarker() . '_' . strtoupper($row['marker']);

                $selectValueArray = [];
                $selectedKey = 0;
                $productUid = 0;

                if (
                    isset($productRowArray) &&
                    is_array($productRowArray) &&
                    count($productRowArray)
                ) {
                    $productUid = $productRowArray['0']['uid'];

                    foreach ($productRowArray as $productRow) {
                        $selectValueArray = $itemTable->getEditVariant()->getVariantRowFromProductRow($productRow);
                    }

                    if (is_array($selectValueArray) && count($selectValueArray)) {
                        sort($selectValueArray, SORT_STRING);
                    }
                }

                $selectOut = '';
                $selectedDomain = '';
                $markerSelect = $marker . '_SELECT';

                if (is_array($selectValueArray) && count($selectValueArray) && isset($selectValueArray['edit_domain'])) {
                    $piVars = $parameterApi->getPiVars();
                    $domainVar = 'domain';
                    $piVar = $domainVar;

                    $tagName = $parameterApi->getPrefixId() . '[' . $piVar . '][' . $productUid . '][' . $row['uid'] . ']';

                    if (
                        isset($piVars[$piVar]) &&
                        is_array($piVars[$piVar]) &&
                        isset($piVars[$piVar][$productUid]) &&
                        is_array($piVars[$piVar][$productUid]) &&
                        isset($piVars[$piVar][$productUid][$row['uid']])
                    ) {
                        $selectedKey = $piVars[$piVar][$productUid][$row['uid']];
                    }

                    $selectedDomain = $selectValueArray[$selectedKey];

                    if (isset($tagArray[$markerSelect])) {
                        $selectOut = tx_ttproducts_form_div::createSelect(
                            $languageObj,
                            $selectValueArray,
                            $tagName,
                            $selectedKey,
                            true,
                            true,
                            [],
                            'select',
                            ['title' => 'Auswahl der Domäne', 'onchange' => 'submit();']
                        );
                    }
                }

                $markerArray['###HIDDENFIELDS###'] = $hiddenFields;

                if (isset($tagArray[$markerSelect])) {
                    $markerArray['###' . $markerSelect . '###'] = $selectOut;
                }

                // hier die Select Box für die Auswahl der Domäne zum Download

                if ($bAddonsEM) {
                    $fileArray = $this->getModelObj()->getFileArray(
                        $orderObj,
                        $row,
                        $multiOrderArray,
                        $checkPriceZero
                    );
                    if (empty($fileArray)) { // If there is no file to download, then skip the whole download object
                        continue;
                    }

                    $path = Environment::getPublicPath() . '/';
                    $path = $path . $row['path'] . '/';

                    // $directLink = TYPO3_SITE_SCRIPT;
                    $paramArray = [];
                    if ($bValidUpdateCode) {
                        $paramArray['update_code'] = $updateCode;
                    } elseif ($trackingCode != '') {
                        $paramArray['tracking'] = $trackingCode;
                    }

                    $prefixId = $parameterApi->getPrefixId();
                    $paramArray[$prefixId . '[' . $downloadVar . ']'] = $row['uid'];

                    if ($selectedDomain != '') {
                        $paramArray[$prefixId . '[' . $domainVar . ']'] = $selectedDomain;
                    }
                    $orderUid = 0;

                    if (
                        isset($multiOrderArray) &&
                        is_array($multiOrderArray) &&
                        count($multiOrderArray)
                    ) {
                        $currentOrderUid = 0;
                        foreach ($multiOrderArray as $orderRow) {
                            if (
                                isset($orderRow['edit_variants']) &&
                                strlen($orderRow['edit_variants']) &&
                                strpos($orderRow['edit_variants'], 'domain:') !== false
                            ) {
                                // andere Varianten erlauben
                                $editVariants =
                                    preg_split(
                                        '/[\h]*' . $variantSeparator . '[\h]*/',
                                        $orderRow['edit_variants'],
                                        -1,
                                        PREG_SPLIT_NO_EMPTY
                                    );

                                if (
                                    $selectedDomain != '' &&
                                    is_array($editVariants) &&
                                    count($editVariants)
                                ) {
                                    $editVariantComparator = 'domain:' . $selectedDomain;
                                    foreach ($editVariants as $editVariant) {
                                        if ($editVariant == $editVariantComparator) {
                                            $orderUid = $orderRow['uid'];
                                            break;
                                        }
                                    }
                                    if ($orderUid) {
                                        break;
                                    }
                                }
                            } else {
                                $currentOrderUid = $orderRow['uid'];
                            }
                        }
                        if (!$orderUid && $bValidUpdateCode) {
                            $orderUid = $currentOrderUid;
                        }
                    }

                    $orderPivar = $parameterApi->getPiVar('sys_products_orders');

                    if ($orderUid) {
                        $paramArray[$prefixId . '[' . $orderPivar . ']'] = $orderUid;
                    }

                    $downloadImageFile = PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath(TT_PRODUCTS_EXT) . 'Resources/Public/Icons/system-extension-download.png');

                    foreach ($fileArray as $k => $file) {
                        if ($file != $path) {
                            if (substr($file, -1) == '/') {
                                // $extKey
                                $paramArray[$postVar . '[download]'] = $k;

                                $url = FrontendUtility::getTypoLink_URL(
                                    $cObj,
                                    $GLOBALS['TSFE']->id,
                                    $paramArray
                                );
                                $foldername = basename($file);

                                $content .= '<a href="' . htmlspecialchars($url) . '" title="' .
                                    $GLOBALS['TSFE']->sL(DIV2007_LANGUAGE_PATH . 'locallang_common.xlf:download') . ' ' . $foldername . '">' .
                                $foldername . '<img src="' . $downloadImageFile . '"></a>';
                            } else {
                                $paramArray[$postVar . '[fal]'] = $k;
                                $url = FrontendUtility::getTypoLink_URL(
                                    $cObj,
                                    $GLOBALS['TSFE']->id,
                                    $paramArray
                                );
                                $filename = basename($file);

                                $content .= '<a href="' . htmlspecialchars($url) . '" title="' .
                                    $GLOBALS['TSFE']->sL(DIV2007_LANGUAGE_PATH . 'locallang_common.xlf:download') . ' ' . $filename . '">' . $filename . '<img src="' . $downloadImageFile . '"></a>';
                            }
                        }
                    }
                } else {
                    $content = 'error: "addons_em" has not been installed';
                    $error = true;
                }

                if (isset($tagArray[$marker])) {
                    $markerArray['###' . $marker . '###'] = $content;
                } elseif ($error) {
                    $subpartArray['###DOWNLOAD_SINGLE###'] .= $content;
                } elseif (!empty($t['item'])) {
                    // 			<!-- ###DOWNLOAD_SINGLE### begin -->
                    // 				###DOWNLOAD_TITLE###
                    // 				###DOWNLOAD_NOTE###
                    // 				###DOWNLOAD_LINK###
                    // 				<br/>
                    // 			<!-- ###DOWNLOAD_SINGLE### end -->

                    $downloadMarker = $this->getMarker();
                    $markerLink = $downloadMarker . '_' . strtoupper('link');

                    if (isset($tagArray[$markerLink])) {
                        $markerArray['###' . $markerLink . '###'] = $content;
                    }

                    $fieldArray = ['author', 'edition', 'note', 'title'];

                    foreach ($fieldArray as $field) {
                        $fieldMarker = $downloadMarker . '_' . strtoupper($field);

                        if (isset($tagArray[$fieldMarker])) {
                            $value = $row[$field];
                            if ($field == 'note') {
                                $value = ($conf['nl2brNote'] ? nl2br($value) : $value);

                                // Extension CSS styled content
                                if (ExtensionManagementUtility::isLoaded('css_styled_content')) {
                                    $value =
                                        FrontendUtility::RTEcssText(
                                            $cObj,
                                            $value
                                        );
                                } elseif (is_array($conf['parseFunc.'])) {
                                    $value =
                                        $cObj->parseFunc(
                                            $value,
                                            $conf['parseFunc.']
                                        );
                                }
                            }
                            $markerArray['###' . $fieldMarker . '###'] = $value;
                        }
                    }

                    $out = $templateService->substituteMarkerArrayCached($t['item'], $markerArray);

                    $subpartArray['###DOWNLOAD_SINGLE###'] .= $out;
                }
            }
        } else {
            // mothing
        }

        $this->setMarkersEmpty(
            $tagArray,
            [$parentMarker . '_' . $this->getMarker()],
            $markerArray
        );

        if (!isset($subpartArray['###DOWNLOAD_SINGLE###'])) {
            $wrappedSubpartArray['###DOWNLOAD_SINGLE###'] = ['', ''];
        }
    }
}
