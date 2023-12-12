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
 * price view functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\PaymentShippingHandling;
use JambageCom\TtProducts\Model\Field\FieldInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_field_price_view extends tx_ttproducts_field_base_view
{
    protected static $convertArray = [
        'price' => [
            'tax' => 'PRICE_TAX',
            'taxperc' => 'TAX',
            '0tax' => 'OLD_PRICE_TAX',
            '0notax' => 'OLD_PRICE_NO_TAX',
            'calc' => 'calcprice',
            'notax' => 'PRICE_NO_TAX',
            'onlytax' => 'PRICE_ONLY_TAX',
            'skontotax' => 'PRICE_TAX_DISCOUNT',
            'skontonotax' => 'PRICE_NO_TAX_DISCOUNT',
            'skontotaxperc' => 'PRICE_TAX_DISCOUNT_PERCENT',
            'unotax' => 'UNIT_PRICE_NO_TAX',
            'utax' => 'UNIT_PRICE_TAX',
            'wnotax' => 'WEIGHT_UNIT_PRICE_NO_TAX',
            'wtax' => 'WEIGHT_UNIT_PRICE_TAX',

            'surchargetax' => 'SURCHARGE_TAX',
            'surchargenotax' => 'SURCHARGE_NO_TAX',
            'discountbyproductpricetax' => 'PRICE_TAX_RECORD_DISCOUNTED',
            'discountbyproductpricenotax' => 'PRICE_NO_TAX_RECORD_DISCOUNTED',
            'discountbyproductutax' => 'UNIT_PRICE_TAX_RECORD_DISCOUNTED',
            'discountbyproductunotax' => 'UNIT_PRICE_NO_TAX_RECORD_DISCOUNTED',
            'discountbyproductwtax' => 'WEIGHT_UNIT_PRICE_TAX_RECORD_DISCOUNTED',
            'discountbyproductwnotax' => 'WEIGHT_UNIT_PRICE_NO_TAX_RECORD_DISCOUNTED',
        ],
        'price2' => [
            '2tax' => 'PRICE2_TAX',
            '2notax' => 'PRICE2_NO_TAX',
            '2onlytax' => 'PRICE2_ONLY_TAX',
            '2skontotax' => 'PRICE2_TAX_DISCOUNT',
            '2skontotaxperc' => 'PRICE2_TAX_DISCOUNT_PERCENT',

            'surcharge2tax' => 'SURCHARGE2_TAX',
            'surcharge2notax' => 'SURCHARGE2_NO_TAX',
        ],
        'deposit' => [
            'deposittax' => 'DEPOSIT_TAX',
            'depositnotax' => 'DEPOSIT_NO_TAX',
        ],
        'directcost' => [
            'directcosttax' => 'DIRECTCOST_TAX',
            'directcostnotax' => 'DIRECTCOST_NO_TAX',
        ],
    ];

    public static function getConvertedPriceFieldArray($priceType)
    {
        $priceFieldArray = [];
        if (
            isset(self::$convertArray[$priceType]) &&
            is_array(self::$convertArray[$priceType])
        ) {
            foreach (self::$convertArray[$priceType] as $field => $marker) {
                $markerField = str_replace('_', '', strtolower($marker));
                $priceFieldArray[] = $markerField;
            }
        }

        return $priceFieldArray;
    }

    /**
     * Generate a graphical price tag or print the price as text.
     */
    public function printPrice($priceText, $taxInclExcl = '')
    {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $languageObj = GeneralUtility::makeInstance(Localization::class);

        if ($conf['usePriceTag'] && (isset($conf['priceTagObj.']))) {
            $cObj = FrontendUtility::getContentObjectRenderer();

            $ptconf = $conf['priceTagObj.'];
            $markContentArray = [];
            $markContentArray['###PRICE###'] = $priceText;
            $markContentArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? $languageObj->getLabel($taxInclExcl) : '');

            $cObj->substituteMarkerInObject($ptconf, $markContentArray);
            $result = $cObj->cObjGetSingle($conf['priceTagObj'], $ptconf);
        } else {
            $result = $priceText;
        }

        return $result;
    }

    /**
     * Formatting a price.
     */
    public function priceFormat($double)
    {
        if (!is_numeric($double)) {
            return $double;
        }
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $double = round($double, 10);

        if (
            $conf['noZeroDecimalPoint'] &&
            round($double, 2) == intval($double)
        ) {
            $result = number_format($double, 0, $conf['priceDecPoint'], $conf['priceThousandPoint']);
        } else {
            $result =
                number_format(
                    $double,
                    intval($conf['priceDec']),
                    $conf['priceDecPoint'],
                    $conf['priceThousandPoint']
                );
        }

        if ($result == '-0,00') {
            $result = '0,00';
        }

        if ($result == '-0.00') {
            $result = '0.00';
        }

        return $result;
    } // priceFormat

    /**
     * Formatting a percentage.
     */
    public function percentageFormat($double)
    {
        $result = false;
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->conf;
        $double = round($double, 10);

        $percentDecPoint = $conf['percentDecPoint'];
        $percentThousandPoint = $conf['percentThousandPoint'];
        $percentDec = $conf['percentDec'];
        $percentNoZeroDecimalPoint = $conf['percentNoZeroDecimalPoint'];

        if ($percentNoZeroDecimalPoint && round($double, 2) == intval($double)) {
            $result = number_format($double, 0, $percentDecPoint, $percentThousandPoint);
        } else {
            $result = number_format($double, intval($percentDec), $percentDecPoint, $percentThousandPoint);
        }
        if ($result == '-0,00') {
            $result = '0,00';
        }
        if ($result == '-0.00') {
            $result = '0.00';
        }

        return $result;
    } // percentageFormat

    public static function convertKey($priceType, $fieldname)
    {
        $result = false;
        if (
            isset(self::$convertArray[$fieldname]) &&
            is_array(self::$convertArray[$fieldname])
        ) {
            if (strpos($priceType, $fieldname) === 0) {
                $priceType = substr($priceType, strlen($fieldname));
            }

            if (
                isset(self::$convertArray[$fieldname][$priceType])
            ) {
                $result = self::$convertArray[$fieldname][$priceType];
            }
        }

        return $result;
    }

    public function getModelMarkerArray(
        $functablename,
        $basketExtra,
        $basketRecs,
        $field,
        $row,
        &$markerArray,
        $priceMarkerPrefix,
        $id,
        $bEnableTaxZero = false,
        $notOverwritePriceIfSet = true
    ) {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $config = $cnf->getConfig();
        $languageObj = GeneralUtility::makeInstance(Localization::class);

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $itemTableView = $tablesObj->get($functablename, true);
        $itemTable = $itemTableView->getModelObj();
        $modelObj = $this->getModelObj();
        $totalDiscountField = FieldInterface::DISCOUNT;

        if ($priceMarkerPrefix != '') {
            $priceMarkerPrefix .= '_';
        }
        $priceMarkerArray = [];
        $modelObj = $this->getModelObj();
        $taxFromShipping = PaymentShippingHandling::getReplaceTaxPercentage($basketExtra);
        $taxInclExcl =
            (
                isset($taxFromShipping) &&
                is_double($taxFromShipping) &&
                $taxFromShipping == 0 ?
                    'tax_zero' :
                    'tax_included'
            );
        $taxInfoArray = [];

        $priceTaxArray =
            $modelObj->getPriceTaxArray(
                $taxInfoArray,
                $conf['discountPriceMode'] ?? '',
                $basketExtra,
                $basketRecs,
                $field,
                tx_ttproducts_control_basket::getRoundFormat(),
                tx_ttproducts_control_basket::getRoundFormat('discount'),
                $row,
                $totalDiscountField,
                $bEnableTaxZero,
                $notOverwritePriceIfSet
            );

        foreach ($priceTaxArray as $priceKey => $priceValue) {
            $displayTax = static::convertKey($priceKey, $field);

            if ($displayTax !== false) {
                $displayKey = $priceMarkerPrefix . $displayTax;
                if ($priceKey == 'skontotaxperc') {
                    $priceMarkerArray['###' . $displayKey . '###'] = $priceValue;
                } else {
                    $priceMarkerArray['###' . $displayKey . '###'] =
                        $this->printPrice(
                            $this->priceFormat($priceValue, $taxInclExcl)
                        );
                }

                $displaySuffixId = str_replace('_', '', strtolower($displayTax));
                $priceMarkerArray['###' . $displayKey . '_ID###'] = $id . '-' . $displaySuffixId;
            }
        }

        // Todo: The following markers must not be set here. This function is called in a loop for each field.

        $priceMarkerArray['###TAX_INCL_EXCL###'] = ($taxInclExcl ? $languageObj->getLabel($taxInclExcl) : '');

        if (!isset($markerArray['###TAX###'])) {
            $priceMarkerArray['###TAX###'] = strval($row['tax']);
        }

        $pricefactor = 0;
        if (isset($conf['creditpoints.']['priceprod'])) {
            $pricefactor = doubleval($conf['creditpoints.']['priceprod']);
        }

        if ($field == 'price') {
            // price if discounted by credipoints
            $priceMarkerArray['###PRICE_IF_DISCOUNTED_BY_CREDITPOINTS_TAX###'] = $this->printPrice($this->priceFormat($priceTaxArray['tax'] - $pricefactor * ($row['creditpoints'] ?? 0), $taxInclExcl));
            $priceMarkerArray['###PRICE_IF_DISCOUNTED_BY_CREDITPOINTS_NO_TAX###'] = $this->printPrice($this->priceFormat($priceTaxArray['notax'] - $pricefactor * ($row['creditpoints'] ?? 0), $taxInclExcl));
        }

        if (is_array($markerArray)) {
            $markerArray = array_merge($markerArray, $priceMarkerArray);
        } else {
            $markerArray = $priceMarkerArray;
        }
    } // getModelMarkerArray

    public function getRowMarkerArray(
        $functablename,
        $fieldname,
        $row,
        $markerKey,
        &$markerArray,
        $fieldMarkerArray,
        $tagArray,
        $theCode,
        $id,
        $basketExtra,
        $basketRecs,
        &$bSkip,
        $bHtml = true,
        $charset = '',
        $prefix = '',
        $suffix = '',
        $imageNum = 0,
        $imageRenderObj = '',
        $linkWrap = false,
        $bEnableTaxZero = false
    ) {
        $notOverwritePriceIfSet = true;
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $itemTableView = $tablesObj->get($functablename, true);
        $itemTable = $itemTableView->getModelObj();
        $modelObj = $this->getModelObj();
        $totalDiscountField = FieldInterface::DISCOUNT;
        $marker = strtoupper($fieldname);
        $taxFromShipping = PaymentShippingHandling::getReplaceTaxPercentage($basketExtra);
        $taxInclExcl = (
            isset($taxFromShipping) && is_double($taxFromShipping) && ($taxFromShipping == 0) ?
                'tax_zero' :
                'tax_included'
        );
        $taxInfoArray = [];
        // tt-products-single-1-pricetax
        $priceArray = $modelObj->getPriceTaxArray(
            $taxInfoArray,
            $conf['discountPriceMode'] ?? '',
            $basketExtra,
            $basketRecs,
            $fieldname,
            tx_ttproducts_control_basket::getRoundFormat(),
            tx_ttproducts_control_basket::getRoundFormat('discount'),
            $row,
            $totalDiscountField,
            $bEnableTaxZero,
            $notOverwritePriceIfSet = true
        );

        $priceMarkerPrefix = $itemTableView->getMarker() . '_';
        foreach ($priceArray as $priceType => $priceValue) {
            $displayTax = self::convertKey($priceType, $fieldname);
            if ($displayTax === false) {
                continue;
            }
            $taxMarker = $priceMarkerPrefix . strtoupper($displayTax);
            $markerArray['###' . $taxMarker . '###'] =
                $this->printPrice(
                    $this->priceFormat($priceValue),
                    $taxInclExcl
                );

            $displaySuffixId = str_replace('_', '', strtolower($displayTax));
            $displaySuffixId = str_replace($fieldname, '', $displaySuffixId);
            $markerArray['###' . $taxMarker . '_ID###'] = $id . $displaySuffixId;
        }
    }
}
