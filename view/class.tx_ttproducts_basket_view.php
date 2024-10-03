<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * basket functions for a basket object
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Renè Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @author	Els Verberne <verberne@bendoo.nl>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 */
use Psr\EventDispatcher\EventDispatcherInterface;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\CompatibilityUtility;
use JambageCom\Div2007\Utility\ErrorUtility;
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\ObsoleteUtility;

use JambageCom\TtProducts\Api\ActivityApi;
use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\BasketItemApi;
use JambageCom\TtProducts\Api\BasketItemViewApi;
use JambageCom\TtProducts\Api\CreditpointsViewApi;
use JambageCom\TtProducts\Api\FeUserMarkerApi;
use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\Api\PaymentShippingHandling;
use JambageCom\TtProducts\Model\Field\FieldInterface;
use JambageCom\TtProducts\View\RelatedList;



class tx_ttproducts_basket_view implements SingletonInterface
{
    public $conf;
    public $config;
    public $price; // price object
    public $urlObj; // url functions
    public $urlArray; // overridden url destinations
    public $funcTablename;
    public $errorCode;
    public $useArticles;

    /**
     * Initialized the basket, setting the deliveryInfo if a users is logged in
     * $basketObj is the TYPO3 default shopping basket array from ses-data.
     */
    public function init(
        $useArticles,
        $errorCode,
        $urlArray = []
    ): void {
        $this->errorCode = $errorCode;
        $this->useArticles = $useArticles;

        $this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view'); // a copy of it
        $this->urlObj->setUrlArray($urlArray);
    } // init

    public function getMarkerArray(
        $basketExtra,
        $calculatedArray,
        $taxArray
    ) {
        $cObj = FrontendUtility::getContentObjectRenderer();
        $taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
        $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
        $markerArray = [];
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->conf;

        // This is the total for the goods in the basket.
        $markerArray['###PRICE_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['goodstotal']['ALL'] + $calculatedArray['deposittax']['goodstotal']['ALL']);
        $markerArray['###PRICE_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceNoTax']['goodstotal']['ALL'] + $calculatedArray['depositnotax']['goodstotal']['ALL']);
        $markerArray['###PRICE_GOODSTOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['goodstotal']['ALL'] - $calculatedArray['priceNoTax']['goodstotal']['ALL'] + $calculatedArray['deposittax']['goodstotal']['ALL'] - $calculatedArray['depositnotax']['goodstotal']['ALL']);

        $markerArray['###PRICE2_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($calculatedArray['price2Tax']['goodstotal']['ALL']);
        $markerArray['###PRICE2_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['price2NoTax']['goodstotal']['ALL']);
        $markerArray['###PRICE2_GOODSTOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($calculatedArray['price2Tax']['goodstotal']['ALL'] - $calculatedArray['price2NoTax']['goodstotal']['ALL']);

        $markerArray['###PRICE_DISCOUNT_GOODSTOTAL_TAX###'] = $priceViewObj->priceFormat($calculatedArray['noDiscountPriceTax']['goodstotal']['ALL'] - $calculatedArray['priceTax']['goodstotal']['ALL']);
        $markerArray['###PRICE_DISCOUNT_GOODSTOTAL_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['noDiscountPriceNoTax']['goodstotal']['ALL'] - $calculatedArray['priceNoTax']['goodstotal']['ALL']);

        if (
            isset($taxArray) &&
            is_array($taxArray) &&
            !empty($taxArray)
        ) {
            foreach ($taxArray as $k => $taxrate) {
                $calculatedTax = $taxObj->getFieldCalculatedValue($taxrate, $basketExtra);
                if ($calculatedTax !== false) {
                    $taxrate = $calculatedTax;
                }
                $taxstr = strval(number_format(floatval($taxrate), 2));
                $label = chr(ord('A') + $k);
                $markerArray['###PRICE_TAXRATE_NAME' . ($k + 1) . '###'] = $label;
                $markerArray['###PRICE_TAXRATE_TAX' . ($k + 1) . '###'] = $taxrate;

                if (isset($calculatedArray['priceNoTax']['sametaxtotal']['ALL'][$taxstr])) {
                    $label = $calculatedArray['priceNoTax']['sametaxtotal']['ALL'][$taxstr] + ($calculatedArray['depositnotax']['sametaxtotal']['ALL'][$taxstr] ?? 0);
                    $markerArray['###PRICE_TAXRATE_TOTAL' . ($k + 1) . '###'] = $priceViewObj->priceFormat($label);

                    $label = $calculatedArray['priceNoTax']['goodssametaxtotal']['ALL'][$taxstr] + ($calculatedArray['depositnotax']['goodssametaxtotal']['ALL'][$taxstr] ?? 0);
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL' . ($k + 1) . '###'] = $priceViewObj->priceFormat($label);

                    $label =
                        $priceViewObj->priceFormat(
                            (
                                $calculatedArray['priceNoTax']['sametaxtotal']['ALL'][$taxstr] +
                                ($calculatedArray['depositnotax']['sametaxtotal']['ALL'][$taxstr] ?? 0)
                            ) *
                            ($taxrate / 100)
                        );
                    $markerArray['###PRICE_TAXRATE_ONLY_TAX' . ($k + 1) . '###'] = $label;

                    $label = $priceViewObj->priceFormat(($calculatedArray['priceNoTax']['goodssametaxtotal']['ALL'][$taxstr] +
                    $calculatedArray['depositnotax']['goodssametaxtotal']['ALL'][$taxstr] ?? 0) * ($taxrate / 100));
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL_ONLY_TAX' . ($k + 1) . '###'] = $label;
                } else {
                    $zeroPrice = $priceViewObj->priceFormat(0);
                    $markerArray['###PRICE_TAXRATE_TOTAL' . ($k + 1) . '###'] = $zeroPrice;
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL' . ($k + 1) . '###'] = $zeroPrice;
                    $markerArray['###PRICE_TAXRATE_ONLY_TAX' . ($k + 1) . '###'] = $zeroPrice;
                    $markerArray['###PRICE_TAXRATE_GOODSTOTAL_ONLY_TAX' . ($k + 1) . '###'] = $zeroPrice;
                }
            }
        }

        // This is for the Basketoverview
        $markerArray['###NUMBER_GOODSTOTAL###'] = $calculatedArray['count'];
        $fileresource = FrontendUtility::fileResource($conf['basketPic']);
        $markerArray['###IMAGE_BASKET###'] = $fileresource;

        return $markerArray;
    }

    public static function getDiscountSubpartArray(
        &$subpartArray,
        &$wrappedSubpartArray,
        $calculatedArray
    ): void {
        $discountValue = tx_ttproducts_basket_calculate::getRealDiscount($calculatedArray);
        if ($discountValue) {
            $wrappedSubpartArray['###DISCOUNT_NOT_EMPTY###'] = '';
        } else {
            $subpartArray['###DISCOUNT_NOT_EMPTY###'] = '';
        }
    }

    public function getBoundaryMarkerArray(
        $templateCode,
        $cObj,
        $cnf,
        $calculatedArray,
        $checkPriceArray,
        $markerArray,
        &$subpartArray,
        &$wrappedSubpartArray
    ): void {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $basketConfArray = [];
        // check the basket limits
        $basketConfArray['minimum'] = $cnf->getBasketConf('minPrice');
        $basketConfArray['maximum'] = $cnf->getBasketConf('maxPrice');
        $priceSuccessArray = [];
        $priceSuccessArray['minimum'] = true;
        $priceSuccessArray['maximum'] = true;
        $boundaryArray = ['minimum', 'maximum'];

        foreach ($boundaryArray as $boundaryType) {
            switch ($boundaryType) {
                case 'minimum':
                    $markerKey = 'MINPRICE';
                    break;
                case 'maximum':
                    $markerKey = 'MAXPRICE';
                    break;
            }

            if (
                isset($checkPriceArray[$boundaryType]) &&
                $checkPriceArray[$boundaryType] &&
                isset($basketConfArray[$boundaryType]['type']) &&
                $basketConfArray[$boundaryType]['type'] == 'price'
            ) {
                $value = $calculatedArray['priceTax'][$basketConfArray[$boundaryType]['collect']]['ALL'];

                if (
                    isset($value) &&
                    isset($basketConfArray[$boundaryType]['collect']) &&
                    (
                        ($boundaryType == 'minimum' && $value < doubleval($basketConfArray[$boundaryType]['value'])) ||
                        ($boundaryType == 'maximum' && $value > doubleval($basketConfArray[$boundaryType]['value']))
                    )
                ) {
                    $subpartArray['###MESSAGE_' . $markerKey . '###'] = '';
                    $tmpSubpart = $templateService->getSubpart($templateCode, '###MESSAGE_' . $markerKey . '_ERROR###');
                    $subpartArray['###MESSAGE_' . $markerKey . '_ERROR###'] = $templateService->substituteMarkerArray($tmpSubpart, $markerArray);
                    $priceSuccessArray[$boundaryType] = false;
                }
            }

            if ($priceSuccessArray[$boundaryType]) {
                $subpartArray['###MESSAGE_' . $markerKey . '_ERROR###'] = '';
                $tmpSubpart = $templateService->getSubpart($templateCode, '###MESSAGE_' . $markerKey . '###');
                $subpartArray['###MESSAGE_' . $markerKey . '###'] = $templateService->substituteMarkerArray($tmpSubpart, $markerArray);
            }

            if (!isset($subpartArray['###MESSAGE_' . $markerKey . '###'])) {
                $wrappedSubpartArray['###MESSAGE_' . $markerKey . '###'] = '';
            }

            if (!isset($subpartArray['###MESSAGE_' . $markerKey . '_ERROR###'])) {
                $wrappedSubpartArray['###MESSAGE_' . $markerKey . '_ERROR###'] = '';
            }
        } // foreach

        if ($priceSuccessArray['minimum'] && $priceSuccessArray['maximum']) {
            $wrappedSubpartArray['###MESSAGE_PRICE_VALID###'] = '';
            $subpartArray['###MESSAGE_PRICE_INVALID###'] = '';
        } else {
            $subpartArray['###MESSAGE_PRICE_VALID###'] = '';
            $wrappedSubpartArray['###MESSAGE_PRICE_INVALID###'] = '';
        }

    }

    /**
     * This generates the shopping basket layout and also calculates the totals. Very important function.
     * TODO: basket view must not make any complex reading of data of articles. Only the itemarray of the basket should be treated with at all.
     */
    public function getView(
        &$errorCode,
        $templateCode,
        $theCode,
        $infoViewObj,
        $feUserRecord,
        $bSelectSalutation,
        $bSelectVariants,
        $calculatedArray,
        $bHtml = true,
        $subpartMarker = 'BASKET_TEMPLATE',
        $mainMarkerArray = [],
        array $mainSubpartArray = [],
        array $mainWrappedSubpartArray = [],
        $templateFilename = '',
        $itemArray = [],
        $notOverwritePriceIfSet = false,
        $multiOrderArray = [],
        $productRowArray = [],
        $basketExtra = [],
        $basketRecs = []
    ) {
        /*
            Very central function in the library.
            By default it extracts the subpart, ###BASKET_TEMPLATE###, from the $templateCode (if given, else the default $this->templateCode)
            and substitutes a lot of fields and subparts.
            Any pre-preparred fields can be set in $mainMarkerArray, which is substituted in the subpart before the item-and-categories part is substituted.
        */

        $out = '';
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $calculationField = FieldInterface::PRICE_CALCULATED;
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');
        $basketExt = $basketApi->getBasketExt();
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $itemObj = GeneralUtility::makeInstance('tx_ttproducts_basketitem');
        $basketItemView = GeneralUtility::makeInstance('tx_ttproducts_basketitem_view');
        $cObj = FrontendUtility::getContentObjectRenderer();
        $taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
        $basketItemViewApi = GeneralUtility::makeInstance(BasketItemViewApi::class, $cnf->getConf());
        $basketItemApi = GeneralUtility::makeInstance(BasketItemApi::class);
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $piVars = $parameterApi->getPiVars();
        $articleViewTagArray = [];
        $checkPriceZero = true;
        $this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view'); // a copy of it

        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $billdeliveryObj = GeneralUtility::makeInstance('tx_ttproducts_billdelivery');
        $viewControlConf = $cnf->getViewControlConf($theCode);
        $context = GeneralUtility::makeInstance(Context::class);

        $viewControlConf = $cnf->getViewControlConf($theCode);
        if (!empty($viewControlConf)) {
            if (
                isset($viewControlConf['param.']) &&
                is_array($viewControlConf['param.'])
            ) {
                $viewParamConf = $viewControlConf['param.'];
            }
        }

        $useBackPid =
            (
                isset($viewParamConf) && $viewParamConf['use'] == 'backPID' ?
                    true :
                    false
            );

        $conf = $cnf->getConf();
        $config = $cnf->getConfig();

        $funcTablename = tx_ttproducts_control_basket::getFuncTablename();
        $itemTableView = $tablesObj->get($funcTablename, true);
        $itemTable = $itemTableView->getModelObj();
        $tableConf = $itemTable->getTableConf($theCode);
        $itemTable->initCodeConf($theCode, $tableConf);
        $quantityArray = [];
        $quantityArray['minimum'] = [];
        $quantityArray['maximum'] = [];

        $articleViewObj = $tablesObj->get('tt_products_articles', true);
        $articleTable = $articleViewObj->getModelObj();
        $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');

        if ($templateCode == '') {
            $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
            $errorCode[0] = 'empty_template';
            $errorCode[1] = ($templateFilename ?: $templateObj->getTemplateFile());

            return '';
        }
        // Getting subparts from the template code.
        $t = [];

        $tempContent =
            $templateService->getSubpart(
                $templateCode,
                $subpartmarkerObj->spMarker(
                    '###' . $subpartMarker . $config['templateSuffix'] . '###'
                )
            );

        if (!$tempContent) {
            $tempContent =
                $templateService->getSubpart(
                    $templateCode,
                    $subpartmarkerObj->spMarker(
                        '###' . $subpartMarker . '###'
                    )
                );
        }

        $feuserSubpartArray = [];
        $feuserWrappedSubpartArray = [];
        $viewTagArray = $markerObj->getAllMarkers($tempContent);

        $orderAddressViewObj = $tablesObj->get('fe_users', true);
        $orderAddressObj = $orderAddressViewObj->getModelObj();
        $feUserMarkerApi = GeneralUtility::makeInstance(FeUserMarkerApi::class);
        $feUserMarkerApi->getWrappedSubpartArray(
            $orderAddressObj,
            $viewTagArray,
            $feUserRecord,
            $feuserSubpartArray,
            $feuserWrappedSubpartArray
        );

        $tmp = [];
        $feUsersParentArray = [];
        $feUsersArray = $markerObj->getMarkerFields(
            $tempContent,
            $orderAddressObj->getTableObj()->tableFieldArray,
            $orderAddressObj->getTableObj()->requiredFieldArray,
            $tmp,
            $orderAddressViewObj->getMarker(),
            $feUsersViewTagArray,
            $feUsersParentArray
        );

        $orderAddressViewObj->getItemSubpartArrays(
            $tempContent,
            'fe_users',
            $feUserRecord,
            $feuserSubpartArray,
            $feuserWrappedSubpartArray,
            $feUsersViewTagArray,
            $theCode,
            $basketExtra
        );

        $feUserMarkerApi->getGlobalMarkerArray($markerArray, $feUserRecord);

        if (isset($mainMarkerArray) && is_array($mainMarkerArray)) {
            $markerArray = array_merge($markerArray, $mainMarkerArray);
        }

        if (is_object($infoViewObj)) {
            $infoViewObj->getSubpartMarkerArray(
                $mainSubpartArray,
                $mainWrappedSubpartArray,
                $viewTagArray
            );
        }
        $mainSubpartArray = array_replace_recursive($mainSubpartArray, $feuserSubpartArray);
        $mainWrappedSubpartArray = array_replace_recursive($mainWrappedSubpartArray, $feuserWrappedSubpartArray);

        // add Global Marker Array
        $globalMarkerArray = $markerObj->getGlobalMarkerArray();
        $markerArray = array_merge($markerArray, $globalMarkerArray);

        $tempContent =
            $templateService->substituteMarkerArrayCached(
                $tempContent,
                $markerArray,
                $mainSubpartArray  // The emptied subparts must be considered before the wrapped subparts are added because TYPO3 does not support nested subparts.
            );

        $t['basketFrameWork'] =
            $templateService->substituteMarkerArrayCached(
                $tempContent,
                [],
                [],
                $feuserWrappedSubpartArray
            );

        $subpartEmptyArray = [
            'EMAIL_PLAINTEXT_TEMPLATE_SHOP',
            'EMAIL_HTML_TEMPLATE_SHOP',
            'BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE',
        ];

        if (
            !$t['basketFrameWork'] &&
            !in_array($subpartMarker, $subpartEmptyArray)
        ) {
            $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
            $errorCode[0] = 'no_subtemplate';
            $errorCode[1] = '###' . $subpartMarker . $templateObj->getTemplateSuffix() . '###';
            $errorCode[2] = ($templateFilename ?: $templateObj->getTemplateFile());

            return '';
        }

        if ($t['basketFrameWork']) {
            $checkExpression = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['templateCheck'];
            if (!empty($checkExpression)) {
                $wrongPounds = preg_match_all($checkExpression, $t['basketFrameWork'], $matches);
                if ($wrongPounds) {
                    $errorCode[0] = 'template_invalid_marker_border';
                    $errorCode[1] = '###' . $subpartMarker . '###';
                    $errorCode[2] = htmlspecialchars(implode('|', $matches[0]));

                    return '';
                }
            }

            if (!$bHtml) {
                $t['basketFrameWork'] = html_entity_decode($t['basketFrameWork'], ENT_QUOTES);
            }

            // If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
            if (
                trim(
                    $templateService->getSubpart(
                        $t['basketFrameWork'],
                        '###BILLING_ADDRESS_LOGIN###'
                    )
                )
            ) {
                if (CompatibilityUtility::isLoggedIn() && $conf['lockLoginUserInfo']) {
                    $t['basketFrameWork'] = $templateService->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS###', '');
                } else {
                    $t['basketFrameWork'] = $templateService->substituteSubpart($t['basketFrameWork'], '###BILLING_ADDRESS_LOGIN###', '');
                }
            }
            $t['categoryFrameWork'] = $templateService->getSubpart($t['basketFrameWork'], '###ITEM_CATEGORY###');
            $t['itemFrameWork'] = $templateService->getSubpart($t['basketFrameWork'], '###ITEM_LIST###');

            $t['item'] = $templateService->getSubpart($t['itemFrameWork'], '###ITEM_SINGLE###');
            $t['taxes'] = $templateService->getSubpart($t['basketFrameWork'], '###COUNTRY_TAXRATES###');

            $currentP = '';
            $itemsOut = '';
            $viewTagArray = [];
            $markerFieldArray = ['BULKILY_WARNING' => 'bulkily',
                'PRODUCT_SPECIAL_PREP' => 'special_preparation',
                'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
                'PRODUCT_LINK_DATASHEET' => 'datasheet'];
            $basketItemViewApi->init(
                $markerFieldArray,
                $t['item'],
                $this->useArticles
            );

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
            $count = 0;
            $bCopyProduct2Article = false;

            if ($this->useArticles == 0) {
                if (
                    strpos(
                        $t['item'],
                        (string)$articleViewObj->getMarker()
                    ) !== false
                ) {
                    $bCopyProduct2Article = true;
                }
            }

            $checkPriceArray = [];
            $checkPriceArray['minimum'] = false;
            $checkPriceArray['maximum'] = false;

            if ($this->useArticles == 1 || $this->useArticles == 3) {
                $markerFieldArray = [];
                $articleParentArray = [];
                $articleFieldsArray = $markerObj->getMarkerFields(
                    $t['item'],
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

            $damViewTagArray = [];
            // DAM support
            if (ExtensionManagementUtility::isLoaded('dam') || !empty($piVars['dam'])) {
                $damParentArray = [];
                $damViewObj = $tablesObj->get('tx_dam', true);
                $damObj = $damViewObj->getModelObj();
                $fieldsArray = $markerObj->getMarkerFields(
                    $itemFrameWork,
                    $damObj->getTableObj()->tableFieldArray,
                    $damObj->getTableObj()->requiredFieldArray,
                    $markerFieldArray,
                    $damViewObj->getMarker(),
                    $damViewTagArray,
                    $damParentArray
                );
                $damCatViewObj = $tablesObj->get('tx_dam_cat', true);
                $damCatObj = $damCatViewObj->getModelObj();
                $damCatMarker = $damCatViewObj->getMarker();
                $damCatViewObj->setMarker('DAM_CAT');

                $viewDamCatTagArray = [];
                $catParentArray = [];
                $tmp = [];
                $catfieldsArray = $markerObj->getMarkerFields(
                    $itemFrameWork,
                    $damCatObj->getTableObj()->tableFieldArray,
                    $damCatObj->getTableObj()->requiredFieldArray,
                    $tmp,
                    $damCatViewObj->getMarker(),
                    $viewDamCatTagArray,
                    $catParentArray
                );
            }
            $hiddenFields = '';
            $sum_pricecredits_total_totunits_no_tax = 0;
            $sum_price_total_totunits_no_tax = 0;
            $sum_pricecreditpoints_total_totunits = 0;
            $creditpointsGifts = '';

            $basketItemViewApi->init(
                $markerFieldArray,
                $t['item'],
                $this->useArticles
            );

            // loop over all items in the basket indexed by sorting text
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k1 => $actItem) {
                    $count++;
                    $quantity = $basketItemApi->getQuantity($actItem);
                    $row = $actItem['rec'];
                    $basketItemApi->addMinMaxQuantities(
                        $quantityArray,
                        $row,
                        $quantity
                    );

                    $itemOut = $basketItemViewApi->generateItemView(
                        $hiddenFields,
                        $checkPriceArray,
                        $actItem,
                        $quantity,
                        $t['item'],
                        $theCode,
                        $useBackPid,
                        $notOverwritePriceIfSet,
                        $feUserRecord,
                        $multiOrderArray,
                        $productRowArray,
                        $basketExtra,
                        $basketRecs,
                        $count,
                        $inputEntabled = true,
                        $bHtml
                    );

                    if (empty($itemOut)) {
                        $count--;
                    } else {
                        $basketItemViewApi->generateCategoryView(
                            $out,
                            $itemsOut,
                            $currentP,
                            $actItem,
                            $t['itemFrameWork'],
                            $t['categoryFrameWork'],
                            $calculatedArray,
                            $basketObj->getCategoryQuantity()
                        );
                        $itemsOut .= $itemOut;
                    }
                }
                if ($itemsOut) {
                    $tempContent =
                    $templateService->substituteSubpart(
                        $t['itemFrameWork'],
                        '###ITEM_SINGLE###',
                        $itemsOut
                    );

                    $out .= $tempContent;
                    $itemsOut = '';	// Clear the item-code var
                }
            } // end of foreach ($itemArray

            if (isset($damCatMarker)) {
                $damCatViewObj->setMarker($damCatMarker); // restore original value
            }
            $subpartArray = [];
            $wrappedSubpartArray = [];
            $shopCountryArray = [];
            $taxRateArray =
                $taxObj->getTaxRates(
                    $shopCountryArray,
                    $taxInfoArray,
                    $basketObj->getUidArray(),
                    $basketRecs
                );

            if (
                isset($taxRateArray) &&
                is_array($taxRateArray) &&
                isset($shopCountryArray) &&
                is_array($shopCountryArray) &&
                isset($shopCountryArray['country_code']) &&
                isset($taxRateArray[$shopCountryArray['country_code']])
            ) {
                $taxArray = $taxRateArray[$shopCountryArray['country_code']];
            } elseif (
                isset($taxRateArray) &&
                is_array($taxRateArray)
            ) {
                $taxArray = current($taxRateArray);
            } else {
                $taxArray = [];
            }

            $basketMarkerArray =
                $this->getMarkerArray(
                    $basketExtra,
                    $calculatedArray,
                    $taxArray
                );

            // Initializing the markerArray for the rest of the template
            $markerArray = $mainMarkerArray;
            $markerArray = array_merge($markerArray, $basketMarkerArray);
            $activityApi = GeneralUtility::makeInstance(ActivityApi::class);
            $activityArray = $activityApi->getActivityArray();
            $infoArray = tx_ttproducts_control_basket::getInfoArray();
            tx_ttproducts_control_basket::uncheckAgb(
                $infoArray,
                $activityArray['products_payment'] ?? 0
            );

            if (is_array($activityArray)) {
                $activity = '';
                if (!empty($activityArray['products_payment'])) {
                    $activity = 'payment';
                } elseif (!empty($activityArray['products_info'])) {
                    $activity = 'info';
                }
                if ($activity) {
                    $bUseXHTML = !empty($GLOBALS['TSFE']->config['config']['xhtmlDoctype']);
                    $hiddenFields .= '<input type="hidden" name="' . TT_PRODUCTS_EXT . '[activity][' . $activity . ']" value="1" ' . ($bUseXHTML ? '/' : '') . '>';
                }
            }
            $markerArray['###HIDDENFIELDS###'] = $hiddenFields;
            $pid = ($conf['PIDbasket'] ?: $GLOBALS['TSFE']->id);

            $confCache = [];
            $excludeList = '';

            if (
                isset($viewParamConf) &&
                is_array($viewParamConf) &&
                !empty($viewParamConf['ignore'])
            ) {
                $excludeList = $viewParamConf['ignore'];
            }

            $url = FrontendUtility::getTypoLink_URL(
                $cObj,
                $pid,
                $this->urlObj->getLinkParams(
                    $excludeList,
                    [],
                    true,
                    $useBackPid,
                    0,
                    ''
                ),
                $target = '',
                $confCache
            );

            $htmlUrl = htmlspecialchars(
                $url,
                ENT_NOQUOTES,
                'UTF-8'
            );

            $wrappedSubpartArray['###LINK_BASKET###'] = ['<a href="' . $htmlUrl . '">', '</a>'];

            PaymentShippingHandling::getMarkerArray(
                $theCode,
                $markerArray,
                $pid,
                $useBackPid,
                $calculatedArray,
                $basketExtra
            );

            // for receipt from DIBS script
            $markerArray['###TRANSACT_CODE###'] = $parameterApi->getParameter('transact');
            $markerArray['###CUR_SYM###'] = ' ' . ($bHtml ? htmlentities($conf['currencySymbol'], ENT_QUOTES) : $conf['currencySymbol']);
            $discountValue = tx_ttproducts_basket_calculate::getRealDiscount($calculatedArray, true);

            $markerArray['###PRICE_TAX_DISCOUNT###'] = $markerArray['###PRICE_DISCOUNT_TAX###'] = $priceViewObj->priceFormat($discountValue);

            $discountValue = tx_ttproducts_basket_calculate::getRealDiscount($calculatedArray, false);

            $markerArray['###PRICE_NO_TAX_DISCOUNT###'] = $priceViewObj->priceFormat($discountValue);

            self::getDiscountSubpartArray(
                $subpartArray,
                $wrappedSubpartArray,
                $calculatedArray
            );

            $markerArray['###PRICE_VAT###'] =
                $priceViewObj->priceFormat(
                    $calculatedArray['priceTax']['goodstotal']['ALL'] -
                    $calculatedArray['priceNoTax']['goodstotal']['ALL'] +
                    $calculatedArray['deposittax']['goodstotal']['ALL'] -
                    $calculatedArray['depositnotax']['goodstotal']['ALL']
                );

            $orderViewObj = $tablesObj->get('sys_products_orders', true);
            $orderViewObj->getBasketRecsMarkerArray($markerArray, $multiOrderArray[0] ?? '');
            $trackingCode = '';
            if (isset($multiOrderArray[0]['tracking_code'])) {
                $trackingCode = $multiOrderArray[0]['tracking_code'];
            }
            $billdeliveryObj->getMarkerArray($markerArray, $trackingCode, 'bill');
            $billdeliveryObj->getMarkerArray($markerArray, $trackingCode, 'delivery');

            // URL
            $markerArray = $this->urlObj->addURLMarkers(
                0,
                $markerArray,
                $theCode,
                [],
                '',
                $useBackPid,
                0
            ); // Applied it here also...

            $taxFromShipping = PaymentShippingHandling::getReplaceTaxPercentage($basketExtra);
            $taxInclExcl = (isset($taxFromShipping) && is_double($taxFromShipping) && $taxFromShipping == 0 ? 'tax_zero' : 'tax_included');
            $markerArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? $languageObj->getLabel($taxInclExcl) : '');

            if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['creditpoints'])) {
                $creditpointsViewApi = GeneralUtility::makeInstance( CreditpointsViewApi::class, $conf);
                $amountCreditpoints =
                tx_ttproducts_creditpoints_div::getCreditPointsFeuser($feUserRecord);

                $creditpointsViewApi->getItemMarkerSubpartArrays(
                    $itemArray,
                    $markerArray,
                    $subpartArray,
                    $wrappedSubpartArray,
                    $conf,
                    $priceViewObj,
                    $calculatedArray,
                    $amountCreditpoints
                );
            }


            foreach ($quantityArray as $k => $subQuantityArray) {
                switch ($k) {
                    case 'minimum':
                        $markerkey = 'MINQUANTITY';
                        break;
                    case 'maximum':
                        $markerkey = 'MAXQUANTITY';
                        break;
                }

                if (is_array($subQuantityArray) && count($subQuantityArray)) {
                    $subpartArray['###MESSAGE_' . $markerkey . '###'] = '';
                    $tmpSubpart =
                        $templateService->getSubpart(
                            $t['basketFrameWork'],
                            '###MESSAGE_' . $markerkey . '_ERROR###'
                        );
                    $quantityCode = [];
                    $quantityCode[0] = 'error_' . strtolower($markerkey);
                    $quantityCode[1] = '';

                    foreach ($subQuantityArray as $subQuantityRow) {
                        $quantityCode[1] .= $subQuantityRow['rec']['title'] . ': ' . $subQuantityRow['quantity'] . ' ' . ($k == 'minimum' ? '&lt;' : '&gt;') . ' ' . $subQuantityRow['limitQuantity'];
                    }

                    $errorOut = ErrorUtility::getMessage($languageObj, $quantityCode);
                    $markerArray['###ERROR_' . $markerkey . '###'] = $errorOut;
                    $subpartArray['###MESSAGE_' . $markerkey . '_ERROR###'] = $templateService->substituteMarkerArray($tmpSubpart, $markerArray);
                } else {
                    $subpartArray['###MESSAGE_' . $markerkey . '_ERROR###'] = '';
                    $tmpSubpart =
                        $templateService->getSubpart(
                            $t['basketFrameWork'],
                            '###MESSAGE_' . $markerkey . '###'
                        );
                    $subpartArray['###MESSAGE_' . $markerkey . '###'] = $templateService->substituteMarkerArray($tmpSubpart, $markerArray);
                }
            }

            if (count($quantityArray['minimum']) || count($quantityArray['maximum'])/* || !$minPriceSuccess */) {
                $subpartArray['###MESSAGE_NO_ERROR###'] = '';
            } else {
                $subpartArray['###MESSAGE_ERROR###'] = '';
            }
            $voucherView = $tablesObj->get('voucher', true);

            if (is_object($voucherView)) {
                $voucherView->getSubpartMarkerArray(
                    $subpartArray,
                    $wrappedSubpartArray
                );
                $voucherView->getMarkerArray($markerArray);
            }

            $pidagb = intval($conf['PIDagb']);

            $addQueryString = [];

            // $addQueryString['id'] = $pidagb;
            if ($GLOBALS['TSFE']->type) {
                $addQueryString['type'] = $GLOBALS['TSFE']->type;
            }

            $pointerExcludeArray = array_keys($parameterApi->getPointerParamsCodeArray());
            $singleExcludeList = $this->urlObj->getSingleExcludeList(implode(',', $pointerExcludeArray));
            $tempUrl =
                FrontendUtility::getTypoLink_URL(
                    $cObj,
                    $pidagb,
                    $this->urlObj->getLinkParams(
                        $singleExcludeList,
                        $addQueryString,
                        true,
                        $useBackPid,
                        0,
                        ''
                    )
                );

            $wrappedSubpartArray['###LINK_AGB###'] = [
                '<a href="' . htmlspecialchars($tempUrl) . '" target="' . $conf['AGBtarget'] . '">',
                '</a>',
            ];

            $pidPrivacy = intval($conf['PIDprivacy']);
            $tempUrl =
                FrontendUtility::getTypoLink_URL(
                    $cObj,
                    $pidPrivacy,
                    $this->urlObj->getLinkParams(
                        $singleExcludeList,
                        $addQueryString,
                        true,
                        $useBackPid,
                        0,
                        ''
                    )
                );
            $wrappedSubpartArray['###LINK_PRIVACY###'] = [
                '<a href="' . htmlspecialchars($tempUrl) . '" target="' . $conf['AGBtarget'] . '">',
                '</a>',
            ];

            $pidRevocation = intval($conf['PIDrevocation']);

            $tempUrl =
                FrontendUtility::getTypoLink_URL(
                    $cObj,
                    $pidRevocation,
                    $this->urlObj->getLinkParams(
                        $singleExcludeList,
                        $addQueryString,
                        true,
                        $useBackPid,
                        0,
                        ''
                    )
                );

            $wrappedSubpartArray['###LINK_REVOCATION###'] = [
                '<a href="' . htmlspecialchars($tempUrl) . '" target="' . $conf['AGBtarget'] . '">',
                '</a>',
            ];

            // Final substitution:
            if (!CompatibilityUtility::isLoggedIn()) {		// Remove section for FE_USERs only, if there are no fe_user
                $subpartArray['###FE_USER_SECTION###'] = '';
            }

            if (is_object($infoViewObj)) {
                $infoViewObj->getRowMarkerArray(
                    $basketExtra,
                    $markerArray,
                    $feUserRecord,
                    $bHtml,
                    $bSelectSalutation
                );
            }

            $fieldsTempArray = $markerObj->getMarkerFields(
                $t['basketFrameWork'],
                $itemTable->getTableObj()->tableFieldArray,
                $itemTable->getTableObj()->requiredFieldArray,
                $markerFieldArray,
                $itemTableView->getMarker(),
                $viewTagArray,
                $parentArray
            );

            $priceCalcMarkerArray = [
                'PRICE_TOTAL_TAX' => $calculatedArray['priceTax']['total']['ALL'] +
                    $calculatedArray['deposittax']['goodstotal']['ALL'],
                'PRICE_TOTAL_NO_TAX' => $calculatedArray['priceNoTax']['total']['ALL'] +
                    $calculatedArray['depositnotax']['goodstotal']['ALL'],
                'PRICE_TOTAL_0_TAX' => $calculatedArray['price0Tax']['total']['ALL'] +
                    $calculatedArray['depositnotax']['goodstotal']['ALL'],
                'PRICE_TOTAL_ONLY_TAX' => $calculatedArray['priceTax']['total']['ALL'] -
                    $calculatedArray['priceNoTax']['total']['ALL'] +
                    $calculatedArray['deposittax']['goodstotal']['ALL'] -
                    $calculatedArray['depositnotax']['goodstotal']['ALL'],
                'PRICE_GOODSTOTAL_0_TAX' => $calculatedArray['price0Tax']['goodstotal']['ALL'],
                'PRICE_GOODSTOTAL_0_NO_TAX' => $calculatedArray['price0NoTax']['goodstotal']['ALL'],
                'PRICE_VOUCHERTOTAL_TAX' => $calculatedArray['priceTax']['vouchertotal']['ALL'] +
                    $calculatedArray['deposittax']['goodstotal']['ALL'],
                'PRICE_VOUCHERTOTAL_NO_TAX' => $calculatedArray['priceNoTax']['vouchertotal']['ALL'] +
                    $calculatedArray['depositnotax']['goodstotal']['ALL'],
                'PRICE_VOUCHERGOODSTOTAL_TAX' => $calculatedArray['priceTax']['vouchergoodstotal']['ALL'] +
                    $calculatedArray['deposittax']['goodstotal']['ALL'],
                'PRICE_VOUCHERGOODSTOTAL_NO_TAX' => $calculatedArray['priceNoTax']['vouchergoodstotal']['ALL'] +
                    $calculatedArray['depositnotax']['goodstotal']['ALL'],
                'PRICE_TOTAL_TAX_WITHOUT_PAYMENT' => $calculatedArray['priceTax']['total']['ALL'] +
                    $calculatedArray['deposittax']['goodstotal']['ALL'] -
                    $calculatedArray['payment']['priceTax'],
                'PRICE_TOTAL_NO_TAX_WITHOUT_PAYMENT' => $calculatedArray['priceNoTax']['total']['ALL'] +
                        $calculatedArray['depositnotax']['goodstotal']['ALL'] -
                        $calculatedArray['payment']['priceNoTax'],
                'PRICE_TOTAL_TAX_CENT' => intval(round(100 * $calculatedArray['priceTax']['total']['ALL'])),
                'PRICE_VOUCHERTOTAL_TAX_CENT' => intval(
                    round(
                        100 * (
                            $calculatedArray['priceTax']['vouchertotal']['ALL'] +
                            $calculatedArray['deposittax']['goodstotal']['ALL']
                        )
                    )
                ),
            ];

            foreach ($priceCalcMarkerArray as $markerKey => $value) {
                $markerArray['###' . $markerKey . '###'] = (is_int($value) ? $value : $priceViewObj->priceFormat($value));
            }

            $variantFieldArray = [];
            $variantMarkerArray = [];
            $taxContent = '';

            if (tx_ttproducts_static_tax::isInstalled()) {
                $staticTaxViewObj = $tablesObj->get('static_taxes', true);
                if (is_object($staticTaxViewObj)) {
                    $staticTaxObj = $staticTaxViewObj->getModelObj();

                    $bUseTaxArray = false;
                    $viewTaxTagArray = [];
                    $parentArray = [];
                    $markerFieldArray = [];

                    $fieldsArray = $markerObj->getMarkerFields(
                        $t['basketFrameWork'],
                        $staticTaxObj->getTableObj()->tableFieldArray,
                        $staticTaxObj->getTableObj()->requiredFieldArray,
                        $markerFieldArray,
                        $staticTaxViewObj->getMarker(),
                        $viewTaxTagArray,
                        $parentArray
                    );

                    if (
                        isset($taxInfoArray) &&
                        is_array($taxInfoArray) &&
                        !empty($taxInfoArray)
                    ) {
                        $bUseTaxArray = true;
                        $bEnableTaxZero = false;
                        foreach ($taxInfoArray as $countryCode => $taxArray) {
                            foreach ($taxArray as $k => $taxRow) {
                                $theTax = $taxRow['tx_rate'] * 0.01;
                                $markerKey = 'STATICTAX_' . $taxId . '_' . ($k + 1);
                                $staticTaxMarkerArray = [];

                                $staticTaxViewObj->getRowMarkerArray(
                                    'static_tax_rates',
                                    $taxRow,
                                    $markerKey,
                                    $staticTaxMarkerArray,
                                    $variantFieldArray,
                                    $variantMarkerArray,
                                    $viewTagArray,
                                    $theCode,
                                    $basketExtra,
                                    $basketRecs,
                                    $bHtml,
                                    $charset,
                                    0,
                                    '',
                                    $id,
                                    $prefix, // if false, then no table marker will be added
                                    $suffix,
                                    '',
                                    $bEnableTaxZero
                                );

                                $markerArray = array_merge($markerArray, $staticTaxMarkerArray);
                                $countryMarkerArray = [];
                                $countrySubpartArray = [];
                                $countryWrappedSubpartArray = [];
                                foreach ($staticTaxMarkerArray as $key => $value) {
                                    $countryMarkerKey = str_replace($markerKey, 'STATICTAX', $key);
                                    $countryMarkerArray[$countryMarkerKey] = $value;
                                }

                                $priceArray = [];

                                $value = 0;
                                if (
                                    isset($calculatedArray['priceNoTax']['goodssametaxtotal'][$countryCode]) &&
                                    isset($calculatedArray['priceNoTax']['goodssametaxtotal'][$countryCode][$taxRow['tx_rate']])
                                ) {
                                    $value = $calculatedArray['priceNoTax']['goodssametaxtotal'][$countryCode][$taxRow['tx_rate']];
                                }
                                $priceArray['priceNoTax'] = $value;
                                $priceArray['priceTax'] = $priceArray['priceNoTax'] * (1 + $theTax);
                                $priceArray['onlyTax'] = $priceArray['priceTax'] - $priceArray['priceNoTax'];
                                $priceCalcMarkerArray2 = [
                                    'PRICE_TOTAL_ONLY_TAX' => $priceArray['onlyTax'],
                                ];

                                foreach ($priceCalcMarkerArray2 as $markerKey => $value) {
                                    $markerArray['###STATICTAX_' . $taxId . '_' . ($k + 1) . '_' . $markerKey . '###'] = $countryMarkerArray['###' . $markerKey . '###'] = $priceViewObj->priceFormat($value);
                                }

                                $countryMarkerArray['###COUNTRY_CODE###'] = $countryCode;
                                $tempContent = $templateService->substituteMarkerArrayCached(
                                    $t['taxes'],
                                    $countryMarkerArray,
                                    $countrySubpartArray,
                                    $countryWrappedSubpartArray
                                );
                                $taxContent .= $tempContent;
                            }
                        }
                    } // $t['taxes']

                    foreach ($viewTaxTagArray as $theTag => $v1) {
                        if (!isset($markerArray['###' . $theTag . '###'])) {
                            foreach ($priceCalcMarkerArray as $markerKey => $value) {
                                if (strpos($theTag, $markerKey) !== false) {
                                    $markerArray['###' . $theTag . '###'] = '';
                                }
                            }
                            if (strpos($theTag, 'STATICTAX_') === 0) {
                                $markerArray['###' . $theTag . '###'] = '';
                            }
                        }
                    }
                }
            }

            $subpartArray['###COUNTRY_TAXRATES###'] = $taxContent;

            $this->getBoundaryMarkerArray(
                $t['basketFrameWork'],
                $cObj,
                $cnf,
                $calculatedArray,
                $checkPriceArray,
                $markerArray,
                $subpartArray,
                $wrappedSubpartArray
            );

            // Call all getBasketView hooks at the end of this method
            if (
                isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView']) &&
                is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView'])
            ) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getBasketView'] as $classRef) {
                    $hookObj = GeneralUtility::makeInstance($classRef);
                    if (method_exists($hookObj, 'getMarkerArrays')) {
                        $hookObj->getMarkerArrays(
                            $this,
                            $templateCode,
                            $theCode,
                            $markerArray,
                            $subpartArray,
                            $wrappedSubpartArray,
                            $mainMarkerArray,
                            $count
                        );
                    }
                }
            }

            $pidListObj = tx_ttproducts_control_basket::getPidListObj();
            $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
            $relatedListView = GeneralUtility::makeInstance('tx_ttproducts_relatedlist_view', $eventDispatcher);
            $relatedListView->init(
                $pidListObj->getPidlist(),
                $pidListObj->getRecursive()
            );
            $relatedMarkerArray = $relatedListView->getListMarkerArray(
                $theCode,
                $templateCode,
                $viewTagArray,
                $funcTablename,
                current($basketObj->getUidArray()),
                $basketObj->getUidArray(),
                [],
                false,
                $multiOrderArray,
                $this->useArticles,
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
                $GLOBALS['TSFE']->id,
                $errorCode
            );

            if ($relatedMarkerArray && is_array($relatedMarkerArray)) {
                $markerArray = array_merge($markerArray, $relatedMarkerArray);
            }

            $frameWork =
                $templateService->substituteSubpart(
                    $t['basketFrameWork'],
                    '###ITEM_CATEGORY_AND_ITEMS###',
                    $out
                );

            PaymentShippingHandling::getSubpartArrays(
                $basketExtra,
                $markerArray,
                $subpartArray,
                $wrappedSubpartArray,
                $frameWork
            );
            $orderAddressViewObj->getWrappedSubpartArray(
                $viewTagArray,
                $useBackPid,
                $subpartArray,
                $wrappedSubpartArray
            );

            // This cObject may be used to call a function which manipulates the shopping basket based on settings in an external order system. The output is included in the top of the order (HTML) on the basket-page.
            $externalCObject = ObsoleteUtility::getExternalCObject($this, 'externalProcessing');

            $markerArray['###EXTERNAL_COBJECT###'] = $externalCObject . '';  // adding extra preprocessing CObject

            // workaround for TYPO3 bug #44270
            // substitute the main subpart with the rendered content.
            $frameWork =
                $templateService->substituteMarkerArrayCached(
                    $frameWork,
                    [],
                    $subpartArray,
                    $wrappedSubpartArray
                );
            $out =
                $templateService->substituteMarkerArray(
                    $frameWork,
                    $markerArray
                ); // workaround for TYPO3 bug
        } // if ($t['basketFrameWork'])

        return $out;
    } // getView
}
