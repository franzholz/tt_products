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
 * basket price calculation functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


use \JambageCom\TtProducts\Model\Field\FieldInterface;

class tx_ttproducts_field_price extends tx_ttproducts_field_base {
	private $bHasBeenInitialised = false;
	private $bTaxIncluded;	// if tax is already included in the price
	private $taxMode;
	public $priceConf; 	// price configuration
	static protected $priceFieldArray = array (
		'price',
		'price2',
		'pricetax',
		'price2tax',
		'priceonlytax',
		'price2onlytax',
		'pricenotax',
		'price2notax',
		'oldpricetax',
		'oldpricenotax',
		'unitpricetax',
		'unitpricenotax',
		'weightunitpricetax',
		'weightunitpricenotax',
		'pricetaxdiscount',
		'pricenotaxdiscount',
		'surcharge',
		'surcharge2',
		'surchargetax',
		'surchargenotax',
		'surcharge2tax',
		'surcharge2notax',
		'discountbyproductpricetax',
		'discountbyproductpricenotax',
		'discountbyproductutax',
		'discountbyproductunotax',
		'discountbyproductwtax',
		'discountbyproductwnotax',
	);

	static protected $convertArray = [
		'tax' => 'priceTax',
		'notax' => 'priceNoTax',
		'0tax' => 'price0Tax',
		'0notax' => 'price0NoTax',
		'2tax' => 'price2Tax',
		'2notax' => 'price2NoTax',
		'calc' => 'calcprice',
		'taxperc' => 'tax',
		'utax' => 'priceUnitTax',
		'unotax' => 'priceUnitNoTax',
		'wtax' => 'priceWeightUnitTax',
		'wnotax' => 'priceWeightUnitNoTax',
	];
	static protected $fieldConvertArray = [
		'tax' => 'pricetax',
		'notax' => 'pricenotax',
		'onlytax' => 'priceonlytax',
		'0tax' => 'oldpricetax',
		'0notax' => 'oldpricenotax',
		'2tax' => 'price2tax',
		'2notax' => 'price2notax',
		'2onlytax' => 'price2onlytax',
		'utax' => 'unitpricetax',
		'unotax' => 'unitpricenotax',
		'wtax' => 'weightunitpricetax',
		'wnotax' => 'weightunitpricenotax',
		'skontotax' => 'pricetaxdiscount',
		'skontonotax' => 'pricenotaxdiscount',
	];

	static protected $fieldKeepArray = [
		'taxperc',
		'calc',
		'skontotaxperc',
		'2skontotax',
		'2skontotaxperc',
		'2skontonotax',
		'surcharge',
		'surcharge2',
		'surchargetax',
		'surchargenotax',
		'surcharge2tax',
		'surcharge2notax',
	];


	public function preInit ($priceConf) {

		parent::init();

		$this->priceConf = &$priceConf;
		if (!isset($this->priceConf['TAXincluded'])) {
			$this->priceConf['TAXincluded'] = '1';	// default '1' for TAXincluded
		}
		$this->setTaxIncluded($this->priceConf['TAXincluded']);
		$this->bHasBeenInitialised = true;

		$this->taxMode = intval($this->priceConf['TAXmode'] ?? 1);
		if (!$this->taxMode) {
			$this->taxMode = 1;
		}
	} // init


	public function needsInit () {
		return !$this->bHasBeenInitialised;
	}

	/**
	 * Changes the string value to integer or float and considers the German float ',' separator
	 *
	 * @param		bool	convert to float?
	 * @param		string	quantity
	 * @return	    float or integer string value
	 */
	static public function toNumber ($bToFloat, $text) {
		$result = '';
		if ($bToFloat) {
			$text = (string) $text;
			// enable the German display of float
			$result = (float) str_replace (',', '.', $text);
		} else {
			$result = (int) $text;
		}

		return $result;
	}


	public function getTaxIncluded () {
		return $this->bTaxIncluded;
	}


	public function setTaxIncluded ($bTaxIncluded = true) {
		$this->bTaxIncluded = $bTaxIncluded;
	}


	public function getTaxMode () {
		return $this->taxMode;
	}


	static public function getPriceTax (
		$price,
		$bTax,
		$bTaxIncluded,
		$taxFactor
	) {
		if ($bTax) {
			if ($bTaxIncluded) {	// If the configuration says that prices in the database is with tax included
				$result = $price;
			} else {
				$result = $price * $taxFactor;
			}
		} else {
			if ($bTaxIncluded) {	// If the configuration says that prices in the database is with tax included
				$result = $price / $taxFactor;
			} else {
				$result = $price;
			}
		}

		return $result;
	}


	/**
	 * return the price with tax mode considered
	 */
	public function getModePrice (
		$basketExtra,
		$basketRecs,
		$taxMode,
		$price,
		$tax = true,
		$row,
		$bTaxIncluded = false,
		$bEnableTaxZero = false
	) {
		$result = $this->getPrice(
			$basketExtra,
			$basketRecs,
			$price,
			$tax,
			$row,
			$bTaxIncluded,
			$bEnableTaxZero
		);

		if ($taxMode == '2') {

			$result = round($result, 2);
		}

		return $result;
	}


	/** reduces price by discount for FE user **/
	static public function getDiscountPrice (
		$price,
		$discount = ''
	) {
		if (floatval($discount) != 0) {
			$price = $price * (1 - $discount / 100);
		}
		return $price;
	}


	/**
	 * Returns the $price with either tax or not tax, based on if $tax is true or false.
	 * This function reads the TypoScript configuration to see whether prices in the database
	 * are entered with or without tax. That's why this function is needed.
	 */
	 public function getPrice (
		$basketExtra,
		$basketRecs,
		$price,
		$tax,
		array $row,
		$bTaxIncluded = false,
		$bEnableTaxZero = false
	) {
		$taxInfoArray = '';
		$result = 0;
		$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
		$taxpercentage = 0.0;
		$taxFactor = 1;
		$bIsZeroTax = false;

		$bTax = ($tax == 1);
		$price = $this->toNumber(true, $price);

		if (
			$bEnableTaxZero &&
			isset($row['tax']) &&
			doubleval($row['tax']) == '0.0'
		) {
			$taxpercentage = 0.0;
			$bIsZeroTax = true;
		} else {
			$taxpercentage =
				$taxObj->getTax(
					$taxInfoArray,
					$row,
					$basketExtra,
					$basketRecs,
					$bEnableTaxZero
				);
		}

		if (!$bIsZeroTax) {
			$calculatedTaxpercentage =
				$taxObj->getFieldCalculatedValue(
					$taxpercentage,
					$basketExtra
				);
		}

		$taxFactor = 1 + $taxpercentage / 100;

		if (
			isset($calculatedTaxpercentage) &&
			is_double($calculatedTaxpercentage) &&
			$calculatedTaxpercentage != $taxpercentage
		) {
			$newtaxFactor = 1 + $calculatedTaxpercentage / 100;
			// we need the net price in order to apply another tax
			if ($bTaxIncluded) {
				$price = $price / $taxFactor;
				$bTaxIncluded = false;
			}
			$taxFactor = $newtaxFactor;
		}

		$result =
			$this->getPriceTax(
				$price,
				$bTax,
				$bTaxIncluded,
				$taxFactor
			);
		return $result;
	} // getPrice


	// function using getPrice and considering a reduced price for resellers
	public function getResellerPrice (
		$basketExtra,
		$basketRecs,
		$row,
		$tax = 1,
		$priceNo = '',
		$bEnableTaxZero = false
	) {
		$result = 0;

		if (
            empty($priceNo) &&
            isset($this->priceConf['priceNoReseller']) &&
			\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->priceConf['priceNoReseller'])
		) {
				// get reseller group number
			$priceNo = intval($this->priceConf['priceNoReseller']);
		}

		if ($priceNo > 0) {
			$result =
				$this->getPrice(
					$basketExtra,
					$basketRecs,
					$row['price' . $priceNo],
					$tax,
					$row,
					$this->getTaxIncluded(),
					$bEnableTaxZero
				);
		}
		// normal price; if reseller price is zero then also the normal price applies
		if ($result == 0) {
			$result =
				$this->getPrice(
					$basketExtra,
					$basketRecs,
					$row['price'],
					$tax,
					$row,
					$this->getTaxIncluded(),
					$bEnableTaxZero
				);
		}

		return $result;
	} // getResellerPrice


	static public function getPriceFieldArray () {
		return self::$priceFieldArray;
	}

	static public function convertIntoRow (&$row, $priceTaxArray) {
		foreach ($priceTaxArray as $field => $value) {
			if (isset(self::$fieldConvertArray[$field])) {
				$finalField = self::$fieldConvertArray[$field];
				if (!isset($row[$finalField])) {
					$row[$finalField] = $value;
				}
			} else if (in_array($field, self::$fieldKeepArray)) {
				if (!isset($row[$field])) {
					$row[$field] = $value;
				}
			}
		}
	}

	static public function getSkonto (
		$relativePrice,
		$priceNumTax,
		&$skonto,
		&$skontoTaxPerc
	) {
		$skonto = ((double) $relativePrice - (double) $priceNumTax);

		if (floatval($relativePrice) != 0) {
			$skontoTaxPerc = (($skonto / $relativePrice) * 100);
		} else {
			$skontoTaxPerc = 'undefined';
		}
	}


	static public function calculateEndPrice (
		$price,
		$row,
		$discountField
	) {
		$calculationField = \JambageCom\TtProducts\Model\Field\FieldInterface::PRICE_CALCULATED;
		$maxDiscount = 0;
		$discount = 0;

		if ($discountField != '' && isset($row[$discountField])) {
			$maxDiscount = $row[$discountField];
		}

		if (
            \JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn() &&
            isset($GLOBALS['TSFE']->fe_user) &&
            isset($GLOBALS['TSFE']->fe_user->user)
        ) {
			$discount = $GLOBALS['TSFE']->fe_user->user['tt_products_discount'];
		}

		if ($discount > $maxDiscount) {
			$maxDiscount = $discount;
		}
		$price = self::getDiscountPrice($price, $maxDiscount);

		if (
			isset($row[$calculationField]) &&
			floatval($row[$calculationField]) > 0
		) {
			$price = $row[$calculationField];
		}

		$result = $price;

		return $result;
	}


	// fetches all calculated prices for a row
	public function getPriceTaxArray (
		&$taxInfoArray,
		$discountPriceMode,
		$basketExtra,
		$basketRecs,
		$fieldname,
		$roundFormat,
		$discountRoundFormat,
		$row,
		$discountField,
		$bEnableTaxZero = false,
        $notOverwritePriceIfSet = true
	) {
		$internalRow = $row;
		$priceArray = [];
		$bIsZeroTax = false;

        if (
            $notOverwritePriceIfSet &&
            isset($internalRow['pricetax']) &&
            isset($internalRow['pricenotax'])
        ) {
			foreach ($internalRow as $priceField => $value) {
				$shortField = array_search($priceField, self::$fieldConvertArray);

				if (
					$shortField !== false
				) {
					$priceArray[$shortField] = $value;
				} else if (
					in_array($priceField, self::$fieldKeepArray)
				) {
					$priceArray[$priceField] = $value;
				}
			}
		} else {
			$price0tax =
				$this->getResellerPrice(
					$basketExtra,
					$basketRecs,
					$internalRow,
					1,
					0,
					$bEnableTaxZero
				);

			if ($fieldname == 'price') {
				$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');

				if (
					$bEnableTaxZero &&
					isset($row['tax']) &&
					doubleval($row['tax']) == '0.0'
				) {
					$bIsZeroTax = true;
					$taxpercentage = 0.0;
				} else {
					$taxpercentage =
						$taxObj->getTax(
							$taxInfoArray,
							$row,
							$basketExtra,
							$basketRecs,
							$bEnableTaxZero
						);
				}

				if (!$bIsZeroTax) {
					$tax = '';
					if (isset($row['tax'])) {
						$tax = $row['tax'];
					}
					$calculatedTaxpercentage =
						$taxObj->getFieldCalculatedValue(
							$tax,
							$basketExtra
						);
					if ($calculatedTaxpercentage !== false) {
						$taxpercentage = $calculatedTaxpercentage;
					}
				}

				$priceArray['taxperc'] = $taxpercentage;
				$internalRow['price'] =
					self::calculateEndPrice(
						$row['price'],
						$row,
						$discountField
					);

				$priceArray['tax'] =
					$this->getResellerPrice(
						$basketExtra,
						$basketRecs,
						$internalRow,
						1,
						'',
						$bEnableTaxZero
					);

				if (
					$roundFormat != '' &&
					!empty($priceArray['tax'])
				) {
					$oldPrice = $priceArray['tax'];
					$priceArray['tax'] =
						tx_ttproducts_api::roundPrice(
							$oldPrice,
							$roundFormat
						);
					$factor = $priceArray['tax'] / $oldPrice;
					$internalRow['price'] *= $factor; // fix the starting price with the same variance coming from the rounding
				}

				$priceArray['notax'] =
					$this->getResellerPrice(
						$basketExtra,
						$basketRecs,
						$internalRow,
						0,
						'',
						$bEnableTaxZero
					);

                for ($i = 1; $i <= 2; ++$i) {
					$suffix = '';
					if ($i > 1) {
						$suffix = $i;
					}

					$priceArray['surcharge' . $suffix . 'notax'] =
						$this->getPrice(
							$basketExtra,
							$basketRecs,
							$internalRow['surcharge' . $suffix] ?? 0,
							false,
							$row,
							false,
							$bEnableTaxZero
						);

					$priceArray['surcharge' . $suffix . 'tax'] =
						$this->getPrice(
							$basketExtra,
							$basketRecs,
							$priceArray['surcharge' . $suffix . 'notax'],
							true,
							$row,
							false,
							$bEnableTaxZero
						);
				}

				$priceArray['0tax'] = $price0tax;
				$priceArray['0notax'] =
					$this->getResellerPrice(
						$basketExtra,
						$basketRecs,
						$row,
						0,
						0,
						$bEnableTaxZero
					);

				$priceArray['unotax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						(
							isset($row['unit_factor']) && ($row['unit_factor'] > 0) ?
								($priceArray['notax'] / $row['unit_factor']) :
								0
						),
						false,
						$row,
						false,
						$bEnableTaxZero
					);

				$priceArray['utax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						$priceArray['unotax'],
						true,
						$row,
						false,
						$bEnableTaxZero
					);

				$priceArray['wnotax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						(
							$row['weight'] > 0 ?
								($priceArray['notax'] / $internalRow['weight']) :
								0
						),
						false,
						$row,
						false,
						$bEnableTaxZero
					);
				$priceArray['wtax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						$priceArray['wnotax'],
						true,
						$row,
						false,
						$bEnableTaxZero
					);

				self::getSkonto(
					$price0tax,
					$priceArray['tax'],
					$priceArray['skontotax'],
					$priceArray['skontotaxperc']
				);

				if ($discountRoundFormat != '') {
					$priceArray['skontotax'] =
						tx_ttproducts_api::roundPrice(
							$priceArray['skontotax'],
							$discountRoundFormat
						);
				}

				$priceArray['skontonotax'] = $priceArray['skontotax'] / (1 + $taxpercentage / 100);
				$priceArray['onlytax'] = $priceArray['tax'] - $priceArray['notax'];
				$priceArray['discountbyproductpricetax'] = $priceArray['0tax'];
				$priceArray['discountbyproductpricenotax'] = $priceArray['0notax'];

				if (
					isset($row[FieldInterface::DISCOUNT]) &&
					isset($row[FieldInterface::DISCOUNT_DISABLE]) &&
					$row[FieldInterface::DISCOUNT] != 0 &&
					$row[FieldInterface::DISCOUNT_DISABLE] == 0
				) {
					$priceArray['discountbyproductpricetax'] =
						self::getDiscountPrice(
							$priceArray['discountbyproductpricetax'],
							$row[FieldInterface::DISCOUNT]
						);
					$priceArray['discountbyproductpricenotax'] =
						self::getDiscountPrice(
							$priceArray['discountbyproductpricenotax'],
							$row[FieldInterface::DISCOUNT]
						);
				}

				$priceArray['discountbyproductunotax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						(isset($row['unit_factor']) && ($row['unit_factor'] > 0) ?  ($priceArray['discountbyproductpricenotax'] / $row['unit_factor']) : 0),
						false,
						$row,
						false,
						$bEnableTaxZero
					);
				$priceArray['discountbyproductutax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						$priceArray['discountbyproductunotax'],
						true,
						$row,
						false,
						$bEnableTaxZero
					);

				$priceArray['discountbyproductwnotax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						(
							$row['weight'] > 0 ?
								($priceArray['discountbyproductpricenotax'] / $internalRow['weight']) :
								0
						),
						false,
						$row,
						false,
						$bEnableTaxZero
					);
				$priceArray['discountbyproductwtax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						$priceArray['discountbyproductwnotax'],
						true,
						$row,
						false,
						$bEnableTaxZero
					);
			} else if (strpos($fieldname, 'price') === 0) {
				$internalRow['price'] =
					self::calculateEndPrice(
						$row['price'],
						$row,
						$discountField
					);

				if ($roundFormat != '') {
					$internalRow[$fieldname] =
						tx_ttproducts_api::roundPrice(
							$internalRow[$fieldname] ?? 0,
							$roundFormat
						);
				}

				$pricelen = strlen('price');
				$priceNum = substr($fieldname, $pricelen /*, strlen($fieldName) - $pricelen*/);
				$priceArray[$priceNum . 'tax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						$internalRow[$fieldname] ?? 0,
						1,
						$row,
						$this->getTaxIncluded(),
						$bEnableTaxZero
					);

				$priceArray[$priceNum . 'notax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						$internalRow[$fieldname] ?? 0,
						0,
						$row,
						$this->getTaxIncluded(),
						$bEnableTaxZero
					);
				$priceArray[$priceNum . 'onlytax'] = $priceArray[$priceNum . 'tax'] - $priceArray[$priceNum . 'notax'];

				$relativePrice = $price0tax;
				$priceNumTax = '';
				if ($discountPriceMode == 0) {
					$relativePrice = $price0tax;
					$priceNumTax = $priceArray[$priceNum . 'tax'];
				} else if ($discountPriceMode == 1) {
					$relativePrice = $priceArray[$priceNum . 'tax'];
					$priceNumTax = $internalRow['price'];
				}

				self::getSkonto(
					$relativePrice,
					$priceNumTax,
					$priceArray[$priceNum . 'skontotax'],
					$priceArray[$priceNum . 'skontotaxperc']
				);
			} else if (in_array($fieldname, ['directcost', 'deposit'])) {
				$priceArray[$fieldname . 'tax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						$internalRow[$fieldname] ?? 0,
						1,
						$row,
						$this->getTaxIncluded(),
						$bEnableTaxZero
					);
				$priceArray[$fieldname . 'notax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						$internalRow[$fieldname] ?? 0,
						0,
						$row,
						$this->getTaxIncluded(),
						$bEnableTaxZero
					);
			} else {
				$value = $row[$fieldname];
				$priceArray['tax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						$value,
						1,
						$row,
						$this->priceConf['TAXincluded'],
						$bEnableTaxZero
					);
				$priceArray['notax'] =
					$this->getPrice(
						$basketExtra,
						$basketRecs,
						$value,
						0,
						$row,
						$this->priceConf['TAXincluded'],
						$bEnableTaxZero
					);
				$priceArray['onlytax'] = $priceArray['tax'] - $priceArray['notax'];
			}

			if ($this->getTaxMode() == 2) {
				foreach ($priceArray as $field => $v) {
					$priceArray[$field] = round($priceArray[$field], 2);
				}
			}
		}

		return $priceArray;
	}


	static public function convertOldPriceArray ($row) {
		$result = [];

		foreach (self::$convertArray as $newField => $oldField) {
			if (isset($row[$newField])) {
				$result[$oldField] = $row[$newField];
			}
		}
		return $result;
	}


	static public function convertNewPriceArray ($row) {
		$result = [];

		foreach (self::$convertArray as $newField => $oldField) {
			if (isset($row[$oldField])) {
				$result[$newField] = $row[$oldField];
			}
		}
		return $result;
	}

	static public function getWithoutTaxedPrices ($record) {
		$newRecord = [];
		foreach ($record as $field => $value) {
			$hasTax = strpos($field, 'tax');
			if (!$hasTax) {
				$newRecord[$field] = $value;
			}
		}
		return $newRecord;
	}
}

