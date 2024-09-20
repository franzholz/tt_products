<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2020 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * functions for the basket item view
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use JambageCom\Div2007\Utility\FrontendUtility;

class BasketItemViewApi implements SingletonInterface
{
    public $useArticles = 0;
    protected $keepNotEmpty = 1;
    protected $config = [
        'categoryHeader' => '',
        'categoryHeader.' => '',
        'PIDitemDisplay' => '',
        'PIDitemDisplay.' => '',
    ];
    protected $displayBasketCatHeader = 1;
    protected $viewTagArray = [];
    protected $articleViewTagArray = [];
    protected $damViewTagArray = [];


    public function __construct(array $conf = [])
    {
            // Auskommentieren nicht möglich wenn mehrere Artikel dem Produkt zugewiesen werden
        $this->keepNotEmpty = (bool) ($conf['keepProductData'] ?? 1);
        foreach ($this->config as $key => $value) {
            if (!empty($conf[$key])) {
                $this->config[$key] = $conf[$key];
            }
        }
        $this->displayBasketCatHeader = ($conf['displayBasketCatHeader'] ?? 0);
    }

    public function init(
        array $markerFieldArray,
        $itemFrameWork,
        $useArticles
    ): void {
        $this->useArticles = $useArticles;
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $funcTablename = \tx_ttproducts_control_basket::getFuncTablename();
        $itemTableView = $tablesObj->get($funcTablename, true);
        $itemTable = $itemTableView->getModelObj();

        $viewTagArray = [];
        $parentArray = [];
        $fieldsArray = $markerObj->getMarkerFields(
            $itemFrameWork,
            $itemTable->getTableObj()->tableFieldArray,
            $itemTable->getTableObj()->requiredFieldArray,
            $markerFieldArray,
            $itemTableView->getMarker(),
            $viewTagArray,
            $parentArray
        );
        $this->setViewTagArray($viewTagArray);

        $articleViewTagArray = [];
        if (
            $useArticles == 1 ||
            $useArticles == 3
        ) {
            $articleViewObj = $tablesObj->get('tt_products_articles', true);
            $articleTable = $articleViewObj->getModelObj();
            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
            $markerFieldArray = [];
            $articleParentArray = [];
            $articleFieldsArray = $markerObj->getMarkerFields(
                $itemFrameWork,
                $itemTable->getTableObj()->tableFieldArray,
                $itemTable->getTableObj()->requiredFieldArray,
                $markerFieldArray,
                $articleViewObj->getMarker(),
                $articleViewTagArray,
                $articleParentArray
            );

            $prodUidField = $cnf->getTableDesc($articleTable->getTableObj()->name, 'uid_product');
            $fieldsArray = array_merge($fieldsArray, $articleFieldsArray);
            $uidKey = array_search($prodUidField, $fieldsArray);
            if ($uidKey != '') {
                unset($fieldsArray[$uidKey]);
            }
        }
        $this->setArticleViewTagArray($articleViewTagArray);

        $damViewTagArray = [];
        // DAM support
        if (ExtensionManagementUtility::isLoaded('dam')) {
            $damParentArray = [];
            $damObj = $tablesObj->get('tx_dam');
            $fieldsArray = $markerObj->getMarkerFields(
                $itemFrameWork,
                $damObj->getTableObj()->tableFieldArray,
                $damObj->getTableObj()->requiredFieldArray,
                $markerFieldArray,
                $damViewObj->getMarker(),
                $damViewTagArray,
                $damParentArray
            );
            $this->setDamViewTagArray($damViewTagArray);
            $damCatViewObj = $tablesObj->get('tx_dam_cat', true);
            $damCatMarker = $damCatViewObj->getMarker();
            $damCatViewObj->setMarker('DAM_CAT');
            $damCatObj = $damCatViewObj->getModelObj();

            $viewDamCatTagArray = [];
            $catParentArray = [];
            $catfieldsArray = $markerObj->getMarkerFields(
                $itemFrameWork,
                $damCatObj->getTableObj()->tableFieldArray,
                $damCatObj->getTableObj()->requiredFieldArray,
                $tmp = [],
                $damCatViewObj->getMarker(),
                $viewDamCatTagArray,
                $catParentArray
            );
        }
    }

    public function generateItemView(
        &$hiddenFields,
        &$checkPriceArray, // neu
        //         $conf, neu
        array $item,
        $quantity,
        $itemFrameWork,
        //         $useArticles, neu
        $theCode,
        $useBackPid,
        $notOverwritePriceIfSet, // neu
        $feUserRecord, // neu FHO
        array $multiOrderArray,
        array $productRowArray,
        array $basketExtra,
        array $basketRecs,
        $index,
        $inputEnabled,
        $useHtmlFormat
    ) {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $variantApi = GeneralUtility::makeInstance(VariantApi::class);
        $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
        $funcTablename = \tx_ttproducts_control_basket::getFuncTablename();
        $itemTableView = $tablesObj->get($funcTablename, true);
        $itemTable = $itemTableView->getModelObj();
        $basketItemView = GeneralUtility::makeInstance('tx_ttproducts_basketitem_view');
        $checkPriceZero = true;
        $urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view'); // a copy of it
        $orderAddressObj = $tablesObj->get('fe_users', false);
        $articleViewObj = $tablesObj->get('tt_products_articles', true);
        $row = $item['rec'];

        if (!$row) {	// avoid bug with missing row
            return false;
        }

        $extArray = $row['ext'] ?? [];
        $pid = intval($row['pid']);

        if (!\tx_ttproducts_control_basket::getPidListObj()->getPageArray($pid)) {
            // product belongs to another basket
            return false;
        }

        $copyProduct2Article = false;

        if ($this->useArticles == 0) {
            if (
                strpos(
                    $itemFrameWork,
                    (string) $articleViewObj->getMarker()
                ) !== false
            ) {
                $copyProduct2Article = true;
            }
        }

        $bIsNoMinPrice = $itemTable->hasAdditional($row, 'noMinPrice');
        if (!$bIsNoMinPrice) {
            $checkPriceArray['minimum'] = true;
        }

        // debug ($row, 'basket view $row');
        $bIsNoMaxPrice = $itemTable->hasAdditional($row, 'noMaxPrice');

        if (!$bIsNoMaxPrice) {
            $checkPriceArray['maximum'] = true;
        }

        // Fill marker arrays
        $wrappedSubpartArray = [];
        $subpartArray = [];
        $markerArray = [];

        $bInputDisabled = !$useHtmlFormat || !$inputEnabled || ($row['inStock'] <= 0);

        $basketItemView->getItemMarkerArray(
            $funcTablename,
            false,
            $item,
            $markerArray,
            $this->getViewTagArray(),
            $hiddenFields,
            $theCode,
            $bInputDisabled,
            $index,
            false,
            'UTF-8'
        );

        $basketItemView->getItemMarkerSubpartArrays(
            $itemFrameWork,
            $funcTablename,
            $row,
            'SINGLE',
            $this->getViewTagArray(),
            false,
            $productRowArray,
            $markerArray,
            $subpartArray,
            $wrappedSubpartArray
        );

        $catRow = $row['category'] ? $tablesObj->get('tt_products_cat')->get($row['category']) : [];
        $catTitle = $catRow['title'] ?? '';
        $tmp = [];

        // use the product if no article row has been found
        $prodVariantRow = $row;
        $prodMarkerRow = $prodVariantRow;
        $itemTable->tableObj->substituteMarkerArray($prodMarkerRow);
        $parentProductRow = [];

        $itemTableView->getModelMarkerArray(
            $prodMarkerRow,
            '',
            $markerArray,
            $variantApi->getFieldArray(), // neu
            $catTitle,
            $this->getViewTagArray(),
            0,
            'basketImage',
            $tmp,
            $theCode,
            $basketExtra,
            $basketRecs,
            $index,
            '',
            '',
            '',
            $useHtmlFormat,
            'UTF-8',
            '',
            $parentProductRow, // neu für Download
            $parentFuncTablename = '', // neu für Download Object Liste
            $parentRows = [],     // neu für Download Object Liste
            $multiOrderArray,
            $productRowArray,
            true, // korrigiert neu $bIsGift
            $notOverwritePriceIfSet // neu
        );

        if (
            $this->useArticles == 1 ||
            $this->useArticles == 3 ||
            $copyProduct2Article
        ) {
            $articleTable = $articleViewObj->getModelObj();

            $articleRows = [];

            if (!$copyProduct2Article) {
                // get the article uid with these colors, sizes and gradings
                if (
                    is_array($extArray) &&
                    isset($extArray['mergeArticles']) &&
                    is_array($extArray['mergeArticles'])
                ) {
                    $prodVariantRow = $extArray['mergeArticles'];
                } elseif (
                    isset($extArray[$articleTable->getFuncTablename()]) &&
                    is_array($extArray[$articleTable->getFuncTablename()])
                ) {
                    $articleExtArray = $extArray[$articleTable->getFuncTablename()];
                    foreach ($articleExtArray as $k => $articleData) {
                        $articleRows[$k] = $articleTable->get($articleData['uid']);
                    }
                } else {
                    $articleRow = $itemTable->getArticleRow($row, $theCode);
                    if ($articleRow) {
                        $articleRows['0'] = $articleRow;
                    }
                }
            }

            if (
                is_array($articleRows) &&
                !empty($articleRows)
            ) {
                if ($this->useArticles == 3) {
                    $itemTable->fillVariantsFromArticles(
                        $prodVariantRow
                    );
                    $variantApi->modifyRowFromVariant($prodVariantRow, $this->useArticles);
                }
                $calculationField = '';
                foreach ($articleRows as $articleRow) {
                    $itemTable->mergeAttributeFields(
                        $prodVariantRow,
                        $articleRow,
                        $this->keepNotEmpty,
                        true,
                        true,
                        $calculationField,
                        false,
                        true
                    );
                }
            } else {
                $variant = $variantApi->getVariantFromRow($row);
                $variantApi->modifyRowFromVariant(
                    $prodVariantRow,
                    $this->useArticles,
                    $variant
                );
            }

            $markerKeyIsDownload = '###IS_DOWNLOAD###';
            $markerKeyIsNotDownload = '###IS_NOT_DOWNLOAD###';

            if (
                isset($extArray) &&
                isset($extArray['records']) &&
                is_array($extArray['records'])
            ) {
                $newTitleArray = [];
                $externalRowArray = $extArray['records'];
                $downloadTables = [];
                $neededDownloadTables = ['tt_products_downloads', 'sys_file_reference'];

                foreach ($externalRowArray as $tablename => $externalRow) {
                    if ($externalRow['title']) {
                        $newTitleArray[] = $externalRow['title'];
                    }
                    if (in_array($tablename, $neededDownloadTables)) {
                        $downloadTables[$tablename] = true;
                    }
                }
                $downloadTables = array_keys($downloadTables);
                $prodVariantRow['title'] = implode(' | ', $newTitleArray);
                if (
                    count($downloadTables) == count($neededDownloadTables)
                ) {
                    $wrappedSubpartArray[$markerKeyIsDownload] = '';
                    $subpartArray[$markerKeyIsNotDownload] = '';
                } else {
                    $subpartArray[$markerKeyIsDownload] = '';
                    $wrappedSubpartArray[$markerKeyIsNotDownload] = '';
                }
            } else {
                $subpartArray[$markerKeyIsDownload] = '';
                $wrappedSubpartArray[$markerKeyIsNotDownload] = '';
            }
            $prodMarkerRow = $prodVariantRow;
            $itemTable->tableObj->substituteMarkerArray($prodMarkerRow);

            $parentRows = [];
            $parentProductRow = [];
            $parentFuncTablename = '';
            $articleViewObj->getModelMarkerArray(
                $prodMarkerRow,
                '',
                $markerArray,
                $variantApi->getFieldArray(),
                $catTitle,
                $this->getArticleViewTagArray(),
                0,
                'basketImage',
                $tmp,
                $theCode,
                $basketExtra,
                $basketRecs,
                $index,
                '',
                '',
                '',
                $useHtmlFormat,
                'UTF-8',
                '',
                $parentProductRow,
                $parentFuncTablename,
                $parentRows,
                $multiOrderArray,
                $productRowArray,
                false, // neu: FHO wieder zurück korrigiert, sonst wird bei Artikel Tax=0 nicht die TAXpercentage genommen.
                $notOverwritePriceIfSet
            );
            $articleViewObj->getItemMarkerSubpartArrays(
                $itemFrameWork,
                $articleViewObj->getModelObj()->getFuncTablename(),
                $prodVariantRow,
                $markerArray,
                $subpartArray,
                $wrappedSubpartArray,
                $this->getArticleViewTagArray(),
                [],
                [],
                $theCode,
                $basketExtra,
                $basketRecs,
                $index,
                $checkPriceZero,
                false
            );
        }
        $itemTableView->getItemMarkerSubpartArrays(
            $itemFrameWork,
            $itemTableView->getModelObj()->getFuncTablename(),
            $prodVariantRow,
            $markerArray,
            $subpartArray,
            $wrappedSubpartArray,
            $this->getViewTagArray(),
            [],
            [],
            $theCode,
            $basketExtra,
            $basketRecs,
            $index,
            $checkPriceZero,
            false
        );

        $cObj->setCurrentVal($catTitle);
        $markerArray['###CATEGORY_TITLE###'] =
            $cObj->cObjGetSingle(
                $this->config['categoryHeader'],
                $this->config['categoryHeader.'],
                'categoryHeader'
            );
        $markerArray['###LINE_NO###'] = $index;
        $markerArray['###PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat(($row['pricetax'] + $item['deposittax']) * $quantity);
        $markerArray['###PRICE_TOTAL_NO_TAX###'] = $priceViewObj->priceFormat(($row['pricenotax'] + $item['depositnotax']) * $quantity);
        $markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat(($row['priceonlytax'] + ($item['deposittax'] - $item['depositnotax'])) * $quantity);
        $markerArray['###PRICE_TOTAL_0_TAX###'] = $priceViewObj->priceFormat($row['oldpricetax'] * $quantity);
        $markerArray['###PRICE_TOTAL_0_NO_TAX###'] = $priceViewObj->priceFormat($row['oldpricenotax'] * $quantity);
        $markerArray['###PRICE_TOTAL_0_ONLY_TAX###'] = $priceViewObj->priceFormat(($row['oldpricetax'] - $row['oldpricenotax']) * $quantity);
        $page = $tablesObj->get('pages');
        $pid = $page->getPID(
            $this->config['PIDitemDisplay'],
            $this->config['PIDitemDisplay.'],
            $row,
            $GLOBALS['TSFE']->rootLine[1] ?? ''
        );

        $addQueryString = [];
        $addQueryString[$itemTable->type] = intval($row['uid']);

        if (
            is_array($extArray) && is_array($extArray[\tx_ttproducts_control_basket::getFuncTablename()])
        ) {
            $addQueryString['variants'] = htmlspecialchars($extArray[\tx_ttproducts_control_basket::getFuncTablename()][0]['vars']);
        }
        $isImageProduct = $itemTable->hasAdditional($row, 'isImage');
        $damMarkerArray = [];
        $damCategoryMarkerArray = [];

        if (
            (
                $isImageProduct ||
                $funcTablename == 'tt_products'
            ) &&
            is_array($extArray) &&
            isset($extArray['tx_dam'])
        ) {
            reset($extArray['tx_dam']);
            $damext = current($extArray['tx_dam']);
            $damUid = $damext['uid'];
            $damRow = $tablesObj->get('tx_dam')->get($damUid);
            $damItem = [];
            $damItem['rec'] = $damRow;
            $damCategoryArray =
                $tablesObj->get('tx_dam_cat')->getCategoryArray($damRow);
            if (is_array($damCategoryArray) && count($damCategoryArray)) {
                reset($damCategoryArray);
                $damCat = current($damCategoryArray);
            }

            $tablesObj->get('tx_dam_cat', true)->getMarkerArray(
                $damCategoryMarkerArray,
                $tablesObj->get('tx_dam_cat', true)->getMarker(),
                $damCat,
                $damRow['pid'],
                $viewDamCatTagArray,
                'SINGLE',
                0,
                'basketImage',
                [],
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
                1,
                '',
                ''
            );

            $tablesObj->get('tx_dam', true)->getModelMarkerArray(
                $damRow,
                '',
                $damMarkerArray,
                $variantApi->getFieldArray(),
                $damCatRow['title'],
                0,
                'basketImage',
                $this->getDamViewTagArray(),
                $tmp,
                $theCode,
                $basketExtra,
                $basketRecs,
                $index,
                '',
                '',
                '',
                $useHtmlFormat,
                'UTF-8',
                '',
                [],
                '',
                [],
                $multiOrderArray,
                $productRowArray,
                false,
                $notOverwritePriceIfSet
            );
        }
        $markerArray = array_merge($markerArray, $damMarkerArray, $damCategoryMarkerArray);

        $tempUrl =
            FrontendUtility::getTypoLink_URL(
                $cObj,
                $pid,
                $urlObj->getLinkParams(
                    '',
                    $addQueryString,
                    true,
                    $useBackPid,
                    0,
                    ''
                ),
                '',
                []
            );

        $css_current = '';
        $wrappedSubpartArray['###LINK_ITEM###'] =
            [
                '<a class="singlelink" href="' . htmlspecialchars($tempUrl) . '"' . $css_current . '>',
                '</a>',
            ];

        if (is_object($variantApi)) {
            $variantApi->removeEmptyMarkerSubpartArray(
                $markerArray,
                $subpartArray,
                $wrappedSubpartArray,
                $prodVariantRow,
                //                 $conf,
                $itemTable->hasAdditional($row, 'isSingle'),
                !$itemTable->hasAdditional($row, 'noGiftService')
            );
        }

        $orderAddressObj->setCondition($row, $funcTablename, $feUserRecord);
        $feUserMarkerApi = GeneralUtility::makeInstance(FeUserMarkerApi::class);

        $feUserMarkerApi->getWrappedSubpartArray(
            $orderAddressObj,
            $this->getViewTagArray(),
            $feUserRecord,
            $subpartArray,
            $wrappedSubpartArray
        );

        // workaround for TYPO3 bug #44270
        $tempContent = $templateService->substituteMarkerArrayCached(
            $itemFrameWork,
            [],
            $subpartArray,
            $wrappedSubpartArray
        );

        $result = $templateService->substituteMarkerArray(
            $tempContent,
            $markerArray
        );

        return $result;
    }

    public function generateCategoryView(
        &$out,
        &$itemsOut,
        &$currentP,
        $item,
        $itemFrameWork,
        $categoryFrameWork,
        array $calculatedArray,
        $categoryQuantity
    ) {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        $row = $item['rec'];
        if (!$row) {	// avoid bug with missing row
            return false;
        }
        $pid = intval($row['pid']);

        $pidcategory = ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] == 1 ? $pid : '');
        $currentPnew = $pidcategory . '_' . $item['rec']['category'];

        // Print Category Title
        if ($currentPnew != $currentP) {
            if ($itemsOut) {
                $out .=
                    $templateService->substituteSubpart(
                        $itemFrameWork,
                        '###ITEM_SINGLE###',
                        $itemsOut
                    );
            }
            $itemsOut = '';		// Clear the item-code var
            $currentP = $currentPnew;

            if ($this->displayBasketCatHeader) {
                $markerArray = [];
                $pageCatTitle = '';
                if (
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'] == 1
                ) {
                    $page = $tablesObj->get('pages');
                    $pageTmp = $page->get($pid);
                    $pageCatTitle = $pageTmp['title'] . '/';
                }
                $catTmp = '';
                if ($item['rec']['category']) {
                    $catTmp = $tablesObj->get('tt_products_cat')->get($item['rec']['category']);
                    $catTmp = $catTmp['title'] ?? '';
                }
                $catTitle = $pageCatTitle . $catTmp;
                $cObj->setCurrentVal($catTitle);
                $markerArray['###CATEGORY_TITLE###'] =
                    $cObj->cObjGetSingle(
                        $this->config['categoryHeader'],
                        $this->config['categoryHeader.'],
                        'categoryHeader'
                    );

                // compatible with bill/delivery
                $currentCategory = $row['category'];
                $markerArray['###CATEGORY_QTY###'] = $categoryQuantity[$currentCategory];

                $categoryPriceTax = $calculatedArray['categoryPriceTax']['goodstotal']['ALL'][$currentCategory] ?? 0;
                $markerArray['###PRICE_GOODS_TAX###'] = $priceViewObj->priceFormat($categoryPriceTax);
                $categoryPriceNoTax = $calculatedArray['categoryPriceNoTax']['goodstotal']['ALL'][$currentCategory];
                $markerArray['###PRICE_GOODS_NO_TAX###'] = $priceViewObj->priceFormat($categoryPriceNoTax);
                $markerArray['###PRICE_GOODS_ONLY_TAX###'] = $priceViewObj->priceFormat($categoryPriceTax - $categoryPriceNoTax);

                $out .= $templateService->substituteMarkerArray($categoryFrameWork, $markerArray);
            }
        }
    }

    public function setViewTagArray($value): void
    {
        $this->viewTagArray = $value;
    }

    public function getViewTagArray()
    {
        return $this->viewTagArray;
    }

    public function setArticleViewTagArray($value): void
    {
        $this->articleViewTagArray = $value;
    }

    public function getArticleViewTagArray()
    {
        return $this->articleViewTagArray;
    }

    public function setDamViewTagArray($value): void
    {
        $this->damViewTagArray = $value;
    }

    public function getDamViewTagArray()
    {
        return $this->damViewTagArray;
    }
}
