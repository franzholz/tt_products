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
 * basket calculation functions for a basket object
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\TtProducts\Api\CustomerApi;
use JambageCom\TtProducts\Api\PaymentApi;
use JambageCom\TtProducts\Api\PaymentShippingHandling;
use JambageCom\TtProducts\Model\Field\FieldInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_basket_calculate implements SingletonInterface
{
    protected $calculatedArray = [];
    protected $baseCalculatedArray = [];

    public function getBaseCalculatedArray()
    {
        return $this->baseCalculatedArray;
    }

    public function setBaseCalculatedArray(array $calculatedArray): void
    {
        $this->baseCalculatedArray = $calculatedArray;
    }

    public function getCalculatedArray()
    {
        return $this->calculatedArray;
    }

    public function setCalculatedArray($calculatedArray): void
    {
        $this->calculatedArray = $calculatedArray;
    }

    public static function getRealDiscount(
        $calculatedArray,
        $tax = true
    ) {
        $result = 0;
        if ($tax) {
            $result =
                $calculatedArray['price0Tax']['goodstotal']['ALL'] -
                $calculatedArray['priceTax']['goodstotal']['ALL'];
        } else {
            $result =
                $calculatedArray['price0NoTax']['goodstotal']['ALL'] -
                $calculatedArray['priceNoTax']['goodstotal']['ALL'];
        }

        return $result;
    }

    public static function getGoodsTotalTax(
        $basketExtra,
        $basketRecs,
        $itemArray
    ) {
        $priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
        $goodsTotalTax = 0;

        foreach ($itemArray as $sort => $actItemArray) {
            foreach ($actItemArray as $k1 => $actItem) {
                $row = $actItem['rec'];
                $count = $actItem['count'];
                $tax = $actItem['tax'];
                $priceTax = $actItem['priceTax'];
                $priceNoTax = $actItem['priceNoTax'];
                $totalNoTax = $priceNoTax * $count;
                $goodsTotalTax +=
                    $priceObj->getPrice(
                        $basketExtra,
                        $basketRecs,
                        $totalNoTax,
                        true,
                        $row,
                        false,
                        false
                    );
            }
        }

        return $goodsTotalTax;
    }

    public function clear(&$calculatedArray, $taxMode = 1): void
    {
        $baseCalculatedArray = $this->getBaseCalculatedArray();
        $calculatedArray = array_merge($calculatedArray, $baseCalculatedArray);
        $calculatedArray['priceTax'] = [];
        $calculatedArray['priceNoTax'] = [];
        $calculatedArray['price0Tax'] = [];
        $calculatedArray['price0NoTax'] = [];
        $calculatedArray['price2Tax'] = [];
        $calculatedArray['price2NoTax'] = [];
        $calculatedArray['deposittax'] = [];
        $calculatedArray['depositnotax'] = [];
        $calculatedArray['payment'] = [];
        $calculatedArray['shipping'] = [];
        $calculatedArray['handling'] =
        [
            '0' => [],
        ];

        $calculatedArray['priceTax']['goodstotal']['ALL'] = 0;
        $calculatedArray['priceNoTax']['goodstotal']['ALL'] = 0;
        $calculatedArray['price0Tax']['goodstotal']['ALL'] = 0;
        $calculatedArray['price0NoTax']['goodstotal']['ALL'] = 0;
        $calculatedArray['price2Tax']['goodstotal']['ALL'] = 0;
        $calculatedArray['price2NoTax']['goodstotal']['ALL'] = 0;
        $calculatedArray['categoryPriceNoTax']['goodstotal']['ALL'] = [];
        $calculatedArray['deposittax']['goodstotal']['ALL'] = 0;
        $calculatedArray['depositnotax']['goodstotal']['ALL'] = 0;
        $calculatedArray['noDiscountPriceTax']['goodstotal']['ALL'] = 0;
        $calculatedArray['noDiscountPriceNoTax']['goodstotal']['ALL'] = 0;

        if ($taxMode == '1') {
            $calculatedArray['priceNoTax']['goodssametaxtotal']['ALL'] = [];
            $calculatedArray['price2NoTax']['goodssametaxtotal']['ALL'] = [];
            $calculatedArray['price0NoTax']['goodssametaxtotal']['ALL'] = [];
            $calculatedArray['categoryPriceNoTax']['goodssametaxtotal']['ALL'] = [];
            $calculatedArray['depositnotax']['goodssametaxtotal']['ALL'] = [];
        }
        $calculatedArray['priceNoTax']['sametaxtotal']['ALL'] = [];
        $calculatedArray['price2NoTax']['sametaxtotal']['ALL'] = [];
        $calculatedArray['price0NoTax']['sametaxtotal']['ALL'] = [];
        $calculatedArray['categoryPriceNoTax']['sametaxtotal']['ALL'] = [];
        $calculatedArray['depositnotax']['sametaxtotal']['ALL'] = [];

        $calculatedArray['shipping']['priceTax'] = 0;
        $calculatedArray['shipping']['priceNoTax'] = 0;
        $calculatedArray['payment']['priceTax'] = 0;
        $calculatedArray['payment']['priceNoTax'] = 0;
        $calculatedArray['handling']['0']['priceTax'] = 0;
        $calculatedArray['handling']['0']['priceNoTax'] = 0;
    }

    /**
     * This calculates the totals. Very important function.
     * This function also calculates the internal arrays.
     *
     * $itemArray	The basked elements, how many (quantity, count) and the price
     * $this->calculatedArray	- Sums of goods, shipping, payment and total amount WITH TAX included
     *
     * ... which holds the total amount, the final list of products and the price of payment and shipping!!
     */
    public function calculate(
        &$itemArray,
        &$calculatedArray, // neu
        $basketExt,
        $basketExtra,
        $basketRecs, // neu
        $funcTablename,
        $useArticles,
        $maxTax,
        $roundFormat,
    ): void {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        // 		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
        $taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
        $priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $viewTableObj = $tablesObj->get($funcTablename);
        $conf = $cnf->conf;
        $shippingTax = '';
        $taxInfoArray = '';
        $bEnableTaxZero = false;
        $calculationField = FieldInterface::PRICE_CALCULATED;
        $calculationAdditionField = FieldInterface::PRICE_CALCULATED_ADDITION;

        $iso3Seller = PaymentApi::getStoreIso3('DEU');
        $iso3Buyer = CustomerApi::getBillingIso3('DEU');

        $this->clear($calculatedArray, $conf['TAXmode']);

        if (tx_ttproducts_static_tax::isInstalled()) {
            $shippingTax =
                $taxObj->getTax(
                    $taxInfoArray,
                    [],
                    $basketExtra,
                    $basketRecs,
                    $bEnableTaxZero
                );
        } else {
            $shippingTax =
                PaymentShippingHandling::getTaxPercentage(
                    $basketExtra,
                    'shipping',
                    ''
                );
        }

        $calculatedTax =
            $taxObj->getFieldCalculatedValue(
                $shippingTax,
                $basketExtra
            );
        if ($calculatedTax !== false) {
            $shippingTax = $calculatedTax;
        }

        if ($shippingTax > $maxTax) {
            $maxTax = $shippingTax;
        } elseif (!isset($shippingTax)) {
            $shippingTax = $maxTax;
        }
        $shippingRow = ['tax' => floatval($shippingTax)];

        if (
            isset($itemArray) &&
            is_array($itemArray) &&
            !empty($itemArray)
        ) {
            $discountPrice = GeneralUtility::makeInstance('tx_ttproducts_discountprice');
            $getDiscount = $discountPrice->getDiscountPrice($conf);

            $priceReduction = [];

            // Check if a special group price can be used
            if ($getDiscount == 1) {
                $discountArray = [];
                $goodsTotalTax =
                    self::getGoodsTotalTax(
                        $basketExtra,
                        $basketRecs,
                        $itemArray
                    );

                $discountPrice->getCalculatedData(
                    $itemArray,
                    $conf['discountprice.'],
                    'calc',
                    $priceReduction,
                    $discountArray,
                    $goodsTotalTax,
                    false,
                    $conf['TAXincluded'],
                    true
                );
            }

            // set the 'calcprice' in itemArray
            if (isset($conf['pricecalc.'])) {
                $pricecalc = GeneralUtility::makeInstance('tx_ttproducts_pricecalc');
                $discountArray = [];

                // do the price calculation
                $pricecalc->getCalculatedData(
                    $itemArray,
                    $conf['pricecalc.'],
                    'calc',
                    $priceReduction,
                    $discountArray,
                    '',
                    false,
                    $conf['TAXincluded'],
                    true
                );
            }

            $pricetablesCalculator = GeneralUtility::makeInstance('tx_ttproducts_pricetablescalc');
            $discountArray = [];

            $tmp = '';
            $pricetablesCalculator->getCalculatedData(
                $itemArray,
                $tmp,
                'calc',
                $priceReduction,
                $discountArray,
                '',
                true,
                $conf['TAXincluded'],
                true
            );
            $bulkilyFeeTax = floatval($conf['bulkilyFeeTax']);

            // loop over all items in the basket indexed by a sort string
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k1 => $actItem) {
                    $row = $actItem['rec'];
                    $count = $actItem['count'];
                    $tax = $actItem['tax'];
                    $taxInfo = [];
                    if (isset($actItem['taxInfo'])) {
                        $taxInfo = $actItem['taxInfo'];
                    }
                    $priceTax = $actItem['priceTax'];
                    $priceNoTax = $actItem['priceNoTax'];
                    $price0Tax = $actItem['price0Tax'];
                    $price0NoTax = $actItem['price0NoTax'];
                    $price2Tax = $actItem['price2Tax'];
                    $price2NoTax = $actItem['price2NoTax'];
                    $bEnableTaxZero =
                        tx_ttproducts_gifts_div::useTaxZero(
                            $row,
                            $conf['gift.'] ?? '',
                            $conf['whereGift'] ?? ''
                        );

                    if (!empty($actItem[$calculationAdditionField])) {
                        if (isset($actItem[$calculationField])) {
                            $actItem[$calculationField] += $actItem[$calculationAdditionField];
                        } else {
                            $extArray = $row['ext'];
                            if (
                                is_array($extArray) &&
                                !empty($extArray['mergeArticles'])
                            ) {
                                $mergeRow = $extArray['mergeArticles'];
                            } else {
                                $mergeRow = $row;
                            }

                            $actItem[$calculationField] =
                                $priceObj->getResellerPrice(
                                    $basketExtra,
                                    $basketRecs,
                                    $mergeRow,
                                    1
                                ) +
                                $actItem[$calculationAdditionField];
                        }
                    }


                    // has the price been calculated before take it if it gets cheaper now
                    if (
                        isset($actItem[$calculationField]) // && ($actItem['calcprice'] < $actItem['priceTax'])
                    ) {
                        $itemArray[$sort][$k1]['priceTax'] = $priceObj->getModePrice(
                            $basketExtra,
                            $basketRecs,
                            $conf['TAXmode'],
                            $actItem[$calculationField],
                            $row,
                            true,
                            $conf['TAXincluded'],
                            $bEnableTaxZero
                        );
                        $itemArray[$sort][$k1]['priceNoTax'] = $priceObj->getModePrice(
                            $basketExtra,
                            $basketRecs,
                            $conf['TAXmode'],
                            $actItem[$calculationField],
                            $row,
                            false,
                            $conf['TAXincluded'],
                            $bEnableTaxZero
                        );
                    }
                    if (isset($actItem[$calculationField])) {
                        $itemArray[$sort][$k1][$calculationField] = $actItem[$calculationField];
                    }

                    //  multiplicate it with the count :
                    $itemArray[$sort][$k1]['totalNoTax'] = $itemArray[$sort][$k1]['priceNoTax'] * $count;
                    $itemArray[$sort][$k1]['total0NoTax'] = $itemArray[$sort][$k1]['price0NoTax'] * $count;
                    $itemArray[$sort][$k1]['total2NoTax'] = $itemArray[$sort][$k1]['price2NoTax'] * $count;
                    $totalDepositNoTax = ($itemArray[$sort][$k1]['depositnotax'] ?? 0) * $count;

                    $calculatedArray['price0NoTax']['goodstotal']['ALL'] += $itemArray[$sort][$k1]['total0NoTax'];
                    $calculatedArray['priceNoTax']['goodstotal']['ALL'] += $itemArray[$sort][$k1]['totalNoTax'];
                    if (!isset($calculatedArray['categoryPriceNoTax']['goodstotal']['ALL'][$row['category']])) {
                        $calculatedArray['categoryPriceNoTax']['goodstotal']['ALL'][$row['category']] = 0;
                    }
                    $calculatedArray['categoryPriceNoTax']['goodstotal']['ALL'][$row['category']] += $itemArray[$sort][$k1]['totalNoTax'];
                    $calculatedArray['price2NoTax']['goodstotal']['ALL'] += $price2NoTax * $count;
                    $calculatedArray['depositnotax']['goodstotal']['ALL'] += $totalDepositNoTax;

                    $calculatedArray['noDiscountPriceTax']['goodstotal']['ALL'] +=
                        $priceObj->getPrice(
                            $basketExtra,
                            $basketRecs,
                            $row['oldpricenotax'] * $actItem['count'],
                            true,
                            $row,
                            $conf['TAXincluded'],
                            $bEnableTaxZero
                        );
                    $calculatedArray['noDiscountPriceNoTax']['goodstotal']['ALL'] +=
                        $priceObj->getPrice(
                            $basketExtra,
                            $basketRecs,
                            $row['oldpricenotax'] * $actItem['count'],   // $price0Tax
                            false,
                            $row,
                            $conf['TAXincluded'],
                            $bEnableTaxZero
                        );

                    if ($conf['TAXmode'] == '1') {
                        $taxstr = strval(number_format($tax, 2)); // needed for floating point taxes as in Swizzerland

                        $itemArray[$sort][$k1]['totalTax'] =
                            $priceObj->getPrice(
                                $basketExtra,
                                $basketRecs,
                                $itemArray[$sort][$k1]['totalNoTax'],
                                true,
                                $row,
                                false,
                                $bEnableTaxZero
                            );

                        $itemArray[$sort][$k1]['total0Tax'] =
                            $priceObj->getPrice(
                                $basketExtra,
                                $basketRecs,
                                $itemArray[$sort][$k1]['total0NoTax'],
                                true,
                                $row,
                                false,
                                $bEnableTaxZero
                            );
                        if (!isset($calculatedArray['priceNoTax']['goodssametaxtotal']['ALL'][$taxstr])) {
                            $calculatedArray['priceNoTax']['goodssametaxtotal']['ALL'][$taxstr] = 0;
                        }
                        $calculatedArray['priceNoTax']['goodssametaxtotal']['ALL'][$taxstr] += $itemArray[$sort][$k1]['totalNoTax'];
                        if (!isset($calculatedArray['price2NoTax']['goodssametaxtotal']['ALL'][$taxstr])) {
                            $calculatedArray['price2NoTax']['goodssametaxtotal']['ALL'][$taxstr] = 0;
                        }
                        $calculatedArray['price2NoTax']['goodssametaxtotal']['ALL'][$taxstr] += $itemArray[$sort][$k1]['total2NoTax'];
                        if (!isset($calculatedArray['categoryPriceNoTax']['goodssametaxtotal']['ALL'][$taxstr][$row['category']])) {
                            $calculatedArray['categoryPriceNoTax']['goodssametaxtotal']['ALL'][$taxstr][$row['category']] = 0;
                        }
                        $calculatedArray['categoryPriceNoTax']['goodssametaxtotal']['ALL'][$taxstr][$row['category']] += $itemArray[$sort][$k1]['totalNoTax'];
                        if (!isset($calculatedArray['price0NoTax']['goodssametaxtotal']['ALL'][$taxstr])) {
                            $calculatedArray['price0NoTax']['goodssametaxtotal']['ALL'][$taxstr] = 0;
                        }
                        $calculatedArray['price0NoTax']['goodssametaxtotal']['ALL'][$taxstr] += $itemArray[$sort][$k1]['total0NoTax'];

                        if (!isset($calculatedArray['depositnotax']['goodssametaxtotal']['ALL'][$taxstr])) {
                            $calculatedArray['depositnotax']['goodssametaxtotal']['ALL'][$taxstr] = 0;
                        }
                        $calculatedArray['depositnotax']['goodssametaxtotal']['ALL'][$taxstr] += $totalDepositNoTax;

                        if (!empty($taxInfo)) {
                            foreach ($taxInfo as $countryCode => $countryRows) {
                                foreach ($countryRows as $k => $taxRow) {
                                    $countryTax = strval($taxRow['tx_rate']);

                                    if (!isset($calculatedArray['priceNoTax']['goodssametaxtotal'][$countryCode][$countryTax])) {
                                        $calculatedArray['priceNoTax']['goodssametaxtotal'][$countryCode][$countryTax] = 0;
                                    }
                                    $calculatedArray['priceNoTax']['goodssametaxtotal'][$countryCode][$countryTax] += $itemArray[$sort][$k1]['totalNoTax'];
                                    if (!isset($calculatedArray['priceNoTax']['goodstotal'][$countryCode])) {
                                        $calculatedArray['priceNoTax']['goodstotal'][$countryCode] = 0;
                                    }
                                    $calculatedArray['priceNoTax']['goodstotal'][$countryCode] += $itemArray[$sort][$k1]['totalNoTax'];
                                }
                            }
                        }
                    } elseif ($conf['TAXmode'] == '2') {
                        $itemArray[$sort][$k1]['totalTax'] = $itemArray[$sort][$k1]['priceTax'] * $count;
                        $itemArray[$sort][$k1]['total0Tax'] = $itemArray[$sort][$k1]['price0Tax'] * $count;
                        $totalDepositTax = $itemArray[$sort][$k1]['deposittax'] * $count;

                        // Fills this array with the product records. Reason: Sorting them by category (based on the page, they reside on)
                        $calculatedArray['priceTax']['goodstotal']['ALL'] += $itemArray[$sort][$k1]['totalTax'];
                        $calculatedArray['price0Tax']['goodstotal']['ALL'] += $itemArray[$sort][$k1]['total0Tax'];
                        $calculatedArray['priceTax']['goodsdeposittotal']['ALL'] += $itemArray[$sort][$k1]['totalDepositTax'];

                        if (!isset($calculatedArray['categoryPriceTax']['goodstotal']['ALL'][$row['category']])) {
                            $calculatedArray['categoryPriceTax']['goodstotal']['ALL'][$row['category']] = 0;
                        }
                        $calculatedArray['categoryPriceTax']['goodstotal']['ALL'][$row['category']] += $itemArray[$sort][$k1]['totalTax'];

                        $calculatedArray['price2Tax']['goodstotal']['ALL'] += $price2Tax * $count;

                        $value = $row['handling'] ?? 0;
                        $calculatedArray['handling']['0']['priceTax'] +=
                            $priceObj->getModePrice(
                                $basketExtra,
                                $basketRecs,
                                $conf['TAXmode'],
                                $value,
                                $shippingRow,
                                true,
                                $conf['TAXincluded'],
                                true
                            );
                        $value = $row['shipping'] ?? 0;
                        $calculatedArray['shipping']['priceTax'] +=
                            $priceObj->getModePrice(
                                $basketExtra,
                                $basketRecs,
                                $conf['TAXmode'],
                                $value,
                                $shippingRow,
                                true,
                                $conf['TAXincluded'],
                                true
                            );
                        $value = $row['shipping2'] ?? 0;

                        if ($count > 1) {
                            $calculatedArray['shipping']['priceTax'] +=
                                $priceObj->getModePrice(
                                    $basketExtra,
                                    $basketRecs,
                                    $conf['TAXmode'],
                                    $value * ($count - 1),
                                    $shippingRow,
                                    true,
                                    $conf['TAXincluded'],
                                    true
                                );
                        }
                    }

                    $value = $row['handling'] ?? 0;
                    $calculatedArray['handling']['0']['priceNoTax'] +=
                        $priceObj->getModePrice(
                            $basketExtra,
                            $basketRecs,
                            $conf['TAXmode'],
                            $value,
                            $shippingRow,
                            false,
                            $conf['TAXincluded'],
                            true
                        );

                    $value = $row['shipping'] ?? 0;
                    $calculatedArray['shipping']['priceNoTax'] +=
                        $priceObj->getModePrice(
                            $basketExtra,
                            $basketRecs,
                            $conf['TAXmode'],
                            $value,
                            $shippingRow,
                            false,
                            $conf['TAXincluded'],
                            true
                        );

                    $value = $row['shipping2'] ?? 0;
                    if ($count > 1) {
                        $calculatedArray['shipping']['priceNoTax'] +=
                            $priceObj->getModePrice(
                                $basketExtra,
                                $basketRecs,
                                $conf['TAXmode'],
                                $value * ($count - 1),
                                $shippingRow,
                                false,
                                $conf['TAXincluded'],
                                true
                            );
                    }
                } // foreach ($actItemArray as $k1 => $actItem) {
            } // foreach ($itemArray

            PaymentShippingHandling::getScriptPrices(
                $calculatedArray,
                $itemArray,
                $basketExtra,
                'payment'
            );
            PaymentShippingHandling::getScriptPrices(
                $calculatedArray,
                $itemArray,
                $basketExtra,
                'shipping'
            );
            PaymentShippingHandling::getScriptPrices(
                $calculatedArray,
                $itemArray,
                $basketExtra,
                'handling'
            );
            $calculatedArray['maxtax']['goodstotal']['ALL'] = $maxTax;

            $taxRow = [];
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k1 => $actItem) {	// TODO: remove this because it has been moved to the shipping configuration
                    $row = $actItem['rec'];
                    if (!empty($row['bulkily'])) {
                        $value = floatval($this->conf['bulkilyAddition']) * $basketExt[$row['uid']][$viewTableObj->getVariant()->getVariantFromRow($row)];
                        $tax = ($bulkilyFeeTax != 0 ? $bulkilyFeeTax : $shippingTax);
                        $taxRow['tax'] = floatval($tax);
                        $calculatedArray['shipping']['priceTax'] +=
                            $priceObj->getModePrice(
                                $basketExtra,
                                $basketRecs,
                                $conf['TAXmode'],
                                $value,
                                $taxRow,
                                true,
                                $conf['TAXincluded'],
                                false
                            );
                        $calculatedArray['shipping']['priceNoTax'] +=
                            $priceObj->getModePrice(
                                $basketExtra,
                                $basketRecs,
                                $conf['TAXmode'],
                                $value,
                                $taxRow,
                                false,
                                $conf['TAXincluded'],
                                false
                            );
                    }
                }
            }

            if ($conf['TAXmode'] == '1') {
                $controlCalcArray =
                    [
                        'priceTax' => 'priceNoTax',
                        'price0Tax' => 'price0NoTax',
                        'price2Tax' => 'price2NoTax',
                        'deposittax' => 'depositnotax',
                    ];

                $taxRow = [];
                foreach ($controlCalcArray as $keyTax => $keyNoTax) {
                    foreach ($calculatedArray[$keyNoTax]['goodssametaxtotal'] as $countryIndex => $taxArray) {
                        $priceTax = 0;

                        foreach ($taxArray as $tax => $value) {
                            $taxRow['tax'] = floatval($tax);
                            $newPriceTax = $priceObj->getModePrice(
                                $basketExtra,
                                $basketRecs,
                                $conf['TAXmode'],
                                $value,
                                $taxRow,
                                true,
                                false,
                                true
                            );
                            $priceTax += $newPriceTax;

                            $calculatedArray[$keyNoTax]['sametaxtotal'][$countryIndex][$tax] = $value;
                            $calculatedArray[$keyTax]['sametaxtotal'][$countryIndex][$tax] = $newPriceTax;
                            $calculatedArray[$keyTax]['goodssametaxtotal'][$countryIndex][$tax] = $newPriceTax;
                        }

                        $calculatedArray[$keyTax]['goodstotal'][$countryIndex] = $priceTax;
                    }
                }

                $controlCatCalcCatArray = ['categoryPriceTax' => 'categoryPriceNoTax'];
                foreach ($controlCatCalcCatArray as $keyTax => $keyNoTax) {
                    $priceTaxArray = [];
                    $priceTax = 0;
                    foreach ($calculatedArray[$keyNoTax]['goodssametaxtotal']['ALL'] as $tax => $catArray) {
                        $taxRow['tax'] = floatval($tax);
                        if (is_array($catArray)) {
                            foreach ($catArray as $cat => $value) {
                                $newPriceTax =
                                    $priceObj->getModePrice(
                                        $basketExtra,
                                        $basketRecs,
                                        $conf['TAXmode'],
                                        $value,
                                        $taxRow,
                                        true,
                                        false,
                                        true
                                    );
                                $priceTax += $newPriceTax;
                                $calculatedArray[$keyNoTax]['sametaxtotal']['ALL'][$cat][$tax] = $value;
                                $calculatedArray[$keyTax]['sametaxtotal']['ALL'][$cat][$tax] = $newPriceTax;
                            }
                        }
                    }
                    $calculatedArray[$keyTax]['goodstotal']['ALL'] = $priceTaxArray;
                }
                $calculatedArray['handling']['0']['priceTax'] =
                    $priceObj->getModePrice(
                        $basketExtra,
                        $basketRecs,
                        $conf['TAXmode'],
                        $calculatedArray['handling']['0']['priceNoTax'],
                        $shippingRow,
                        true,
                        false,
                        true
                    );
                $calculatedArray['shipping']['priceTax'] =
                    $priceObj->getModePrice(
                        $basketExtra,
                        $basketRecs,
                        $conf['TAXmode'],
                        $calculatedArray['shipping']['priceNoTax'],
                        $shippingRow,
                        true,
                        false,
                        true
                    );
            }
        } // if (count($itemArray))
        $paymentTax = PaymentShippingHandling::getTaxPercentage($basketExtra, 'payment', '');
        if ($paymentTax > $maxTax) {
            $maxTax = $paymentTax;
        } elseif ($paymentTax == '') {
            $paymentTax = $maxTax;
        }
        $paymentRow = ['tax' => floatval($paymentTax)];

        PaymentShippingHandling::getHandlingData(
            $basketExtra,
            $basketRecs,
            $conf,
            $iso3Seller,
            $iso3Buyer,
            $calculatedArray['count'] ?? 0,
            $calculatedArray['priceTax']['goodstotal']['ALL'],
            $calculatedArray,
            $itemArray
        );

        // payment must be dealt with at the latest because the payment gateway must know about all other costs
        // Shipping must be at the end in order to use the calculated values from before
        PaymentShippingHandling::getPaymentShippingData(
            $basketExtra,
            $basketRecs,
            $conf,
            $iso3Seller,
            $iso3Buyer,
            $calculatedArray['count'] ?? 0,
            $calculatedArray['priceTax']['goodstotal']['ALL'],
            $shippingRow,
            $paymentRow,
            $itemArray,
            $calculatedArray,
            $calculatedArray['shipping']['priceTax'],
            $calculatedArray['shipping']['priceNoTax'],
            $calculatedArray['payment']['priceTax'],
            $calculatedArray['payment']['priceNoTax']
        );

        if ($shippingTax) {
            if (!isset($calculatedArray['priceNoTax']['sametaxtotal']['ALL'][strval(number_format($shippingTax, 2))])) {
                $calculatedArray['priceNoTax']['sametaxtotal']['ALL'][strval(number_format($shippingTax, 2))] = 0;
            }
            $calculatedArray['priceNoTax']['sametaxtotal']['ALL'][strval(number_format($shippingTax, 2))] += $calculatedArray['shipping']['priceNoTax'];
        }

        if ($paymentTax) {
            if (!isset($calculatedArray['priceNoTax']['sametaxtotal']['ALL'][strval(number_format($paymentTax, 2))])) {
                $calculatedArray['priceNoTax']['sametaxtotal']['ALL'][strval(number_format($paymentTax, 2))] = 0;
            }
            $calculatedArray['priceNoTax']['sametaxtotal']['ALL'][strval(number_format($paymentTax, 2))] += $calculatedArray['payment']['priceNoTax'];
        }
    } // calculate

    // This calculates the total for everything in the basket
    public function calculateSums(
        &$calculatedArray,
        $roundFormat,
        $pricefactor,
        $creditpoints,
        $getShopCountryCode
    ): void {
        $creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');

        // Todo: consider the $roundFormat parameter .XXXXXXXXXX
        $baseCountryArray =
            [
                'ALL',
            ];
        if ($getShopCountryCode != '') {
            $baseCountryArray[] = $getShopCountryCode;
        }

        $calculatedArray['priceTax']['creditpoints'] = $calculatedArray['priceNoTax']['creditpoints'] = $creditpointsObj->getBasketTotal() * $pricefactor;

        foreach ($calculatedArray['priceNoTax']['goodstotal'] as $countryCode => $value) {
            $calculatedArray['priceNoTax']['total'][$countryCode] = round($value, 2);
        }
        foreach ($calculatedArray['priceTax']['goodstotal'] as $countryCode => $value) {
            $calculatedArray['priceTax']['total'][$countryCode] = round($value, 2);
        }

        if (
            isset($calculatedArray['handling']) &&
            is_array($calculatedArray['handling'])
        ) {
            foreach ($calculatedArray['handling'] as $subkey => $handlingConf) {
                foreach ($baseCountryArray as $baseCountry) {
                    $calculatedArray['priceNoTax']['total'][$baseCountry] += $handlingConf['priceNoTax'];
                    $calculatedArray['priceTax']['total'][$baseCountry] += $handlingConf['priceTax'];
                }
            }
        }

        foreach ($baseCountryArray as $baseCountry) {
            $calculatedArray['priceNoTax']['total'][$baseCountry] +=
                round($calculatedArray['payment']['priceNoTax'], 2);
            $calculatedArray['priceNoTax']['total'][$baseCountry] +=
                round($calculatedArray['shipping']['priceNoTax'], 2);

            $calculatedArray['priceTax']['total'][$baseCountry] +=
                $calculatedArray['payment']['priceTax'];
            $calculatedArray['priceTax']['total'][$baseCountry] +=
                $calculatedArray['shipping']['priceTax'];
        }

        $calculatedArray['price0NoTax']['total']['ALL'] =
            $calculatedArray['price0NoTax']['goodstotal']['ALL'];
        $calculatedArray['price0Tax']['total']['ALL'] = $calculatedArray['price0Tax']['goodstotal']['ALL'];

        $calculatedArray['price2NoTax']['total']['ALL'] = $calculatedArray['price2NoTax']['goodstotal']['ALL'];
        $calculatedArray['price2Tax']['total']['ALL'] = $calculatedArray['price2Tax']['goodstotal']['ALL'];

        $this->setCalculatedArray($calculatedArray);
    }

    // This calculates the total for the voucher in the basket
    public function addVoucherSums()
    {
        $result = false;
        $calculatedArray = $this->getCalculatedArray();

        if (!isset($calculatedArray['priceNoTax']['total'])) {
            debug('internal ERROR in tt_products method addVoucherSums'); // keep this
        } else {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $voucherObj = $tablesObj->get('voucher');
            $voucherAmount = 0;

            if (is_object($voucherObj) && $voucherObj->isEnabled()) {
                $voucherAmount = $voucherObj->getRebateAmount();
            }

            $calculatedArray['priceNoTax']['vouchertotal']['ALL'] = $calculatedArray['priceNoTax']['total']['ALL'] - $voucherAmount - $calculatedArray['priceNoTax']['creditpoints'];

            $calculatedArray['priceTax']['vouchertotal']['ALL'] = $calculatedArray['priceTax']['total']['ALL'] - $voucherAmount - $calculatedArray['priceTax']['creditpoints'];

            $calculatedArray['priceNoTax']['vouchergoodstotal']['ALL'] = $calculatedArray['priceNoTax']['goodstotal']['ALL'] - $voucherAmount - $calculatedArray['priceNoTax']['creditpoints'];

            $calculatedArray['priceTax']['vouchergoodstotal']['ALL'] = $calculatedArray['priceTax']['goodstotal']['ALL'] - $voucherAmount - $calculatedArray['priceTax']['creditpoints'];

            $result = true;
        }

        $this->setCalculatedArray($calculatedArray);

        return $result;
    }
}
