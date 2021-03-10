<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Franz Holzinger (franz@ttproducts.de)
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
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_basket_calculate implements \TYPO3\CMS\Core\SingletonInterface {

	protected $calculatedArray = array();

	public function getCalculatedArray ()	{
		return $this->calculatedArray;
	}


	public function setCalculatedArray ($calculatedArray)	{
		$this->calculatedArray = $calculatedArray;
	}


	public static function getGoodsTotalTax ($basketExtra, $itemArray)	{
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
				$goodsTotalTax += $priceObj->getPrice($basketExtra, $totalNoTax, true, $row, false);
			}
		}

		return $goodsTotalTax;
	}


	/**
	 * This calculates the totals. Very important function.
	This function also calculates the internal arrays

	$itemArray	The basked elements, how many (quantity, count) and the price
	$this->calculatedArray	- Sums of goods, shipping, payment and total amount WITH TAX included

	... which holds the total amount, the final list of products and the price of payment and shipping!!

	 */
	public function calculate ($basketExt, $basketExtra, $funcTablename, $useArticles, $maxTax, &$itemArray)	{
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
		$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
		$priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$viewTableObj = $tablesObj->get($funcTablename);

		$conf = &$cnf->conf;

		if (count($itemArray))	{

			if ($conf['discountprice.'])	{
				$getDiscount = 0;
				$gr_list = explode (',' , $GLOBALS['TSFE']->gr_list);
				if ($conf['getDiscountPrice']) {
					$getDiscount = 1;
				} else {
					foreach ($gr_list as $k1 => $val) {
						if (((float) $val > 0) && ($getDiscount == 0)) {
							$getDiscount = 1 - strcmp($GLOBALS['TSFE']->fe_user->groupData->title, $conf['discountGroupName'] );

							if (strlen($GLOBALS['TSFE']->fe_user->groupData['title']) == 0)	// repair result of strcmp
								$getDiscount = 0;
						}
					}
				}
				$priceTotal = array();
				$priceReduction = array();
				$additive = 0;

				// Check if a special group price can be used
				if ($getDiscount == 1)	{
					$discountPrice = GeneralUtility::makeInstance('tx_ttproducts_discountprice');
					$discountArray = array();
					$goodsTotalTax = self::getGoodsTotalTax($basketExtra, $itemArray);

					$discountPrice->getCalculatedData(
						$itemArray,
						$conf['discountprice.'],
						'calc',
						$priceReduction,
						$discountArray,
						$goodsTotalTax,
						false,
						true
					);
				}
			}

			// set the 'calcprice' in itemArray
			if ($conf['pricecalc.']) {
				$pricecalc = GeneralUtility::makeInstance('tx_ttproducts_pricecalc');
				$discountArray = array();

				// do the price calculation
				$pricecalc->getCalculatedData(
					$itemArray,
					$conf['pricecalc.'],
					'calc',
					$priceReduction,
					$discountArray,
					'',
					false,
					true
				);
			}

			$pricetablescalc = GeneralUtility::makeInstance('tx_ttproducts_pricetablescalc');
			$pricetablescalc->init($pibaseObj);
			$discountArray = array();
			$pricetablescalc->getCalculatedData(
				$itemArray,
				$tmp='',
				'calc',
				$priceReduction,
				$discountArray,
				'',
				true,
				true
			);

			$this->calculatedArray['priceTax'] = array();
			$this->calculatedArray['priceNoTax'] = array();
			$this->calculatedArray['price0Tax'] = array();
			$this->calculatedArray['price0NoTax']['total'] = array();

			if ($conf['TAXmode'] == '1')	{
				$this->calculatedArray['priceNoTax']['goodssametaxtotal'] = array();
				$this->calculatedArray['price2NoTax']['goodssametaxtotal'] = array();
				$this->calculatedArray['price0NoTax']['goodssametaxtotal'] = array();
				$this->calculatedArray['categoryPriceNoTax']['goodssametaxtotal'] = array();
			}
			$this->calculatedArray['priceNoTax']['sametaxtotal'] = array();
			$this->calculatedArray['price2NoTax']['sametaxtotal'] = array();
			$this->calculatedArray['price0NoTax']['sametaxtotal'] = array();
			$this->calculatedArray['categoryPriceNoTax']['sametaxtotal'] = array();

            $shippingTax = $paymentshippingObj->getTaxPercentage($basketExtra, 'shipping', '');

			if ($shippingTax > $maxTax) {
				$maxTax = $shippingTax;
			} else if ($shippingTax == '') {
				$shippingTax = $maxTax;
			}
			$shippingRow = array('tax' => floatval($shippingTax));
			$bulkilyFeeTax = floatval($conf['bulkilyFeeTax']);

			// loop over all items in the basket indexed by a sort string
			foreach ($itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {

					$row = $actItem['rec'];
					$count = $actItem['count'];
					$tax = $actItem['tax'];
					$priceTax = $actItem['priceTax'];
					$priceNoTax = $actItem['priceNoTax'];
					$price0Tax = $actItem['price0Tax'];
					$price0NoTax = $actItem['price0NoTax'];
					$price2Tax = $actItem['price2Tax'];
					$price2NoTax = $actItem['price2NoTax'];

					// has the price been calculated before take it if it gets cheaper now
					if ($actItem['calc'] > 0) {
						$itemArray[$sort][$k1]['priceTax'] = $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $actItem['calc'], true, $row, $conf['TAXincluded']);
						$itemArray[$sort][$k1]['priceNoTax'] = $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $actItem['calc'], false, $row, $conf['TAXincluded']);
					}

					//  multiplicate it with the count :
					$itemArray[$sort][$k1]['totalNoTax'] = $itemArray[$sort][$k1]['priceNoTax'] * $count;
					$itemArray[$sort][$k1]['total0NoTax'] = $itemArray[$sort][$k1]['price0NoTax'] * $count;
					$itemArray[$sort][$k1]['total2NoTax'] = $itemArray[$sort][$k1]['price2NoTax'] * $count;

					$this->calculatedArray['price0NoTax']['goodstotal'] += $itemArray[$sort][$k1]['total0NoTax'];
					$this->calculatedArray['priceNoTax']['goodstotal'] += $itemArray[$sort][$k1]['totalNoTax'];
					$this->calculatedArray['categoryPriceNoTax']['goodstotal'][$row['category']]+= $itemArray[$sort][$k1]['totalNoTax'];
					$this->calculatedArray['price2NoTax']['goodstotal'] += $price2NoTax * $count;

					$this->calculatedArray['noDiscountPriceTax']['goodstotal']  += $priceObj->getPrice($basketExtra, $row['price'] * $actItem['count'], true, $row, $conf['TAXincluded']);
					$this->calculatedArray['noDiscountPriceNoTax']['goodstotal'] += $priceObj->getPrice($basketExtra, $row['price'] * $actItem['count'], false, $row, $conf['TAXincluded']);

					if ($conf['TAXmode'] == '1')	{
						$taxstr = strval(number_format($tax, 2)); // needed for floating point taxes as in Swizzerland
						$itemArray[$sort][$k1]['totalTax'] = $priceObj->getPrice($basketExtra, $itemArray[$sort][$k1]['totalNoTax'], true, $row, false);
						$itemArray[$sort][$k1]['total0Tax'] = $priceObj->getPrice($basketExtra, $itemArray[$sort][$k1]['total0NoTax'], true, $row, false);
						$this->calculatedArray['priceNoTax']['goodssametaxtotal'][$taxstr] +=  $itemArray[$sort][$k1]['totalNoTax'];
						$this->calculatedArray['price2NoTax']['goodssametaxtotal'][$taxstr] += $itemArray[$sort][$k1]['total2NoTax'];
						$this->calculatedArray['categoryPriceNoTax']['goodssametaxtotal'][$taxstr][$row['category']] +=  $itemArray[$sort][$k1]['totalNoTax'];
						$this->calculatedArray['price0NoTax']['goodssametaxtotal'][$taxstr] += $itemArray[$sort][$k1]['total0NoTax'];
					} else if ($conf['TAXmode'] == '2')	{
						$itemArray[$sort][$k1]['totalTax'] = $itemArray[$sort][$k1]['priceTax'] * $count;
						$itemArray[$sort][$k1]['total0Tax'] = $itemArray[$sort][$k1]['price0Tax'] * $count;

							// Fills this array with the product records. Reason: Sorting them by category (based on the page, they reside on)
						$this->calculatedArray['priceTax']['goodstotal'] += $itemArray[$sort][$k1]['totalTax'];
						$this->calculatedArray['price0Tax']['goodstotal'] += $itemArray[$sort][$k1]['total0Tax'];
						$this->calculatedArray['categoryPriceTax']['goodstotal'][$row['category']]+= $itemArray[$sort][$k1]['totalTax'];
						$this->calculatedArray['price2Tax']['goodstotal']	+= $price2Tax * $count;

						$value = $row['handling'];
						$this->calculatedArray['handling']['0']['priceTax'] += $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $value, true, $shippingRow, $conf['TAXincluded'], true);
						$value = $row['shipping'];
						$this->calculatedArray['shipping']['priceTax'] += $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $value, true, $shippingRow, $conf['TAXincluded'], true);
						$value = $row['shipping2'];

						if ($count > 1)	{
							$this->calculatedArray['shipping']['priceTax'] += $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $value * ($count-1), true, $shippingRow, $conf['TAXincluded'], true);
						}
					}
					$value = $row['handling'];
					$this->calculatedArray['handling']['0']['priceNoTax'] += $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $value, false, $shippingRow, $conf['TAXincluded'], true);

					$value = $row['shipping'];
					$this->calculatedArray['shipping']['priceNoTax'] += $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $value, false, $shippingRow, $conf['TAXincluded'], true);

					$value = $row['shipping2'];
					if ($count > 1)	{
						$this->calculatedArray['shipping']['priceNoTax'] += $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $value * ($count - 1), false, $shippingRow, $conf['TAXincluded'], true);
					}
				} // foreach ($actItemArray as $k1 => $actItem) {
			} // foreach ($this->itemArray

			$paymentshippingObj->getScriptPrices('payment', $basketExtra, $this->calculatedArray, $itemArray);
			$paymentshippingObj->getScriptPrices('shipping', $basketExtra, $this->calculatedArray, $itemArray);
			$paymentshippingObj->getScriptPrices('handling', $basketExtra, $this->calculatedArray, $itemArray);
			$this->calculatedArray['maxtax']['goodstotal'] = $maxTax;

			$taxRow = array();
			foreach ($itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {	// TODO: remove this because it has been moved to the shipping configuration
					$row = $actItem['rec'];
					if ($row['bulkily'])	{
						$value = floatval($this->conf['bulkilyAddition']) * $basketExt[$row['uid']][$viewTableObj->variant->getVariantFromRow($row)];
						$tax = ($bulkilyFeeTax != '' ? $bulkilyFeeTax : $shippingTax);
						$taxRow['tax'] = floatval($tax);
						$this->calculatedArray['shipping']['priceTax'] += $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $value, true, $taxRow, $conf['TAXincluded'], true);
						$this->calculatedArray['shipping']['priceNoTax'] += $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $value, false, $taxRow, $conf['TAXincluded'], true);
					}
				}
			}

			if ($conf['TAXmode'] == '1')	{
				$controlCalcArray = array('priceTax' => 'priceNoTax', 'price0Tax' => 'price0NoTax', 'price2Tax' => 'price2NoTax');

				$taxRow = array();
				foreach ($controlCalcArray as $keyTax => $keyNoTax)	{
					$priceTax = 0;

					foreach ($this->calculatedArray[$keyNoTax]['goodssametaxtotal'] as $tax => $value)	{
						$taxRow['tax'] = floatval($tax);
						$newPriceTax = $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $value, true, $taxRow, false, true);
						$priceTax += $newPriceTax;
                        $this->calculatedArray[$keyTax]['sametaxtotal'][$tax] = $newPriceTax;
                        $this->calculatedArray[$keyNoTax]['sametaxtotal'][$tax] = $value;
                        $this->calculatedArray[$keyTax]['goodssametaxtotal'][$tax] = $newPriceTax;
					}
                    $this->calculatedArray[$keyTax]['goodstotal'] = $priceTax;
				}

				$controlCatCalcCatArray = array('categoryPriceTax' => 'categoryPriceNoTax');
				foreach ($controlCatCalcCatArray as $keyTax => $keyNoTax)	{
					$priceTaxArray = array();
					$priceTax = 0;

					foreach ($this->calculatedArray[$keyNoTax]['goodssametaxtotal'] as $tax => $catArray)	{
						$taxRow['tax'] = floatval($tax);
						if (is_array($catArray))	{
							foreach ($catArray as $cat => $value)	{
								$newPriceTax = $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $value, true, $taxRow, false);
								$priceTax += $newPriceTax;
								$priceTaxArray[$cat] = $newPriceTax;
								$this->calculatedArray[$keyNoTax]['sametaxtotal'][$cat][$tax] = $value;
								$this->calculatedArray[$keyTax]['sametaxtotal'][$cat][$tax] = $newPriceTax;
							}
						}
					}
					$this->calculatedArray[$keyTax]['goodstotal'] = $priceTaxArray;
				}
				$this->calculatedArray['handling']['0']['priceTax'] = $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $this->calculatedArray['handling']['0']['priceNoTax'], true, $shippingRow, false, false);
				$this->calculatedArray['shipping']['priceTax'] = $priceObj->getModePrice($basketExtra, $conf['TAXmode'], $this->calculatedArray['shipping']['priceNoTax'], true, $shippingRow, false, false);
			}
		} // if (count($itemArray))
		$paymentTax = $paymentshippingObj->getTaxPercentage($basketExtra, 'payment', '');
		if ($paymentTax > $maxTax) {
			$maxTax = $paymentTax;
		} else if ($paymentTax == '') {
			$paymentTax = $maxTax;
		}
		$paymentRow = array('tax' => floatval($paymentTax));

			// Shipping must be at the end in order to use the calculated values from before
		$paymentshippingObj->getPaymentShippingData(
			$basketExtra,
			$this->calculatedArray['count'],
			$this->calculatedArray['priceTax']['goodstotal'],
			$shippingRow,
			$paymentRow,
			$itemArray,
			$this->calculatedArray,
			$this->calculatedArray['shipping']['priceTax'],
			$this->calculatedArray['shipping']['priceNoTax'],
			$this->calculatedArray['payment']['priceTax'],
			$this->calculatedArray['payment']['priceNoTax']
		);

		$paymentshippingObj->getHandlingData(
			$basketExtra,
			$this->calculatedArray['count'],
			$this->calculatedArray['priceTax']['goodstotal'],
			$this->calculatedArray,
			$itemArray
		);

		if ($shippingTax) {
			$this->calculatedArray['priceNoTax']['sametaxtotal'][strval(number_format($shippingTax, 2))] += $this->calculatedArray['shipping']['priceNoTax'];
		}
		if ($paymentTax) {
			$this->calculatedArray['priceNoTax']['sametaxtotal'][strval(number_format($paymentTax, 2))] += $this->calculatedArray['payment']['priceNoTax'];
		}
	} // getCalculatedBasket


	// This calculates the total for everything in the basket
	public function calculateSums ($recs) {
		$pricefactor = doubleval($this->conf['creditpoints.']['priceprod']);

		$creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');
		$autoCreditpointsTotal = $creditpointsObj->getBasketTotal();
		if ($autoCreditpointsTotal > 0)	{
			$creditpoints = $autoCreditpointsTotal;
		} else {
			$creditpoints = $recs['tt_products']['creditpoints'];
		}

		$this->calculatedArray['priceTax']['creditpoints'] = $this->calculatedArray['priceNoTax']['creditpoints'] = $creditpointsObj*$pricefactor;


		$this->calculatedArray['priceNoTax']['total'] = round($this->calculatedArray['priceNoTax']['goodstotal'], 2);
		$this->calculatedArray['priceTax']['total']  = $this->calculatedArray['priceTax']['goodstotal'];

		if (isset($this->calculatedArray['handling']) && is_array($this->calculatedArray['handling']))	{
			foreach ($this->calculatedArray['handling'] as $subkey => $handlingConf)	{
				$this->calculatedArray['priceNoTax']['total'] += round($handlingConf['priceNoTax'], 2);
				$this->calculatedArray['priceTax']['total'] += $handlingConf['priceTax'];
			}
		}
		$this->calculatedArray['priceNoTax']['total'] += round($this->calculatedArray['payment']['priceNoTax'], 2);
		$this->calculatedArray['priceNoTax']['total'] += round($this->calculatedArray['shipping']['priceNoTax'], 2);
		$this->calculatedArray['price0NoTax']['total']  = $this->calculatedArray['price0NoTax']['goodstotal'];
		$this->calculatedArray['price0Tax']['total']  = $this->calculatedArray['price0Tax']['goodstotal'];

		$this->calculatedArray['price2NoTax']['total']  = $this->calculatedArray['price2NoTax']['goodstotal'];
		$this->calculatedArray['price2Tax']['total']  = $this->calculatedArray['price2Tax']['goodstotal'];

		$this->calculatedArray['priceTax']['total'] += $this->calculatedArray['payment']['priceTax'];
		$this->calculatedArray['priceTax']['total'] += $this->calculatedArray['shipping']['priceTax'];
	}


	// This calculates the total for the voucher in the basket
	public function addVoucherSums () {
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$voucher = $tablesObj->get('voucher');
		$voucherAmount = $voucher->getRebateAmount();

		$this->calculatedArray['priceNoTax']['vouchertotal'] = $this->calculatedArray['priceNoTax']['total'] - $voucherAmount - $this->calculatedArray['priceNoTax']['creditpoints'];
		$this->calculatedArray['priceTax']['vouchertotal'] = $this->calculatedArray['priceTax']['total'] - $voucherAmount - $this->calculatedArray['priceTax']['creditpoints'];

		$this->calculatedArray['priceNoTax']['vouchergoodstotal'] = $this->calculatedArray['priceNoTax']['goodstotal'] - $voucherAmount - $this->calculatedArray['priceNoTax']['creditpoints'];
		$this->calculatedArray['priceTax']['vouchergoodstotal'] = $this->calculatedArray['priceTax']['goodstotal'] - $voucherAmount - $this->calculatedArray['priceTax']['creditpoints'];
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket_calculate.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket_calculate.php']);
}



