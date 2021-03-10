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
		'price2notax'
	);
	static protected $convertArray = array(
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
	);


	/**
	 * Getting all tt_products_cat categories into internal array
	 * Here $conf needs not be a member of $cnf in order to have local settings e.g. with shipping
	 */
	public function preInit ($cObj, &$priceConf) {

		parent::init($cObj);

		$this->priceConf = &$priceConf;
		if (!isset($this->priceConf['TAXincluded']))	{
			$this->priceConf['TAXincluded'] = '1';	// default '1' for TAXincluded
		}
		$this->setTaxIncluded($this->priceConf['TAXincluded']);
		$this->bHasBeenInitialised = true;

		$this->taxMode = $this->priceConf['TAXmode'];
		if (!$this->taxMode)	{
			$this->taxMode = 1;
		}
	} // init


	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}


	public function getFieldValue ($basketExtra, $row, $fieldname)	{
		return $row[$fieldname];
	}


	/**
	 * Changes the string value to integer or float and considers the German float ',' separator
	 *
	 * @param		bool	convert to float?
	 * @param		string	quantity
	 * @return	    float or integer string value
	 */
	public function toNumber ($bToFloat, $text)	{
		$rc = '';
		if ($bToFloat)	{
			$text = (string) $text;
			// enable the German display of float
			$rc = (float) str_replace (',', '.', $text);
		} else {
			$rc = (int) $text;
		}

		return $rc;
	}


	public function getTaxIncluded ()	{
		return $this->bTaxIncluded;
	}


	public function setTaxIncluded ($bTaxIncluded=true)	{
		$this->bTaxIncluded = $bTaxIncluded;
	}


	public function getTaxMode ()	{
		return $this->taxMode;
	}


	public function getPriceTax ($price, $bTax, $bTaxIncluded, $taxFactor)	{
		if ($bTax)	{
			if ($bTaxIncluded)	{	// If the configuration says that prices in the database is with tax included
				$rc = $price;
			} else {
				$rc = $price * $taxFactor;
			}
		} else {
			if ($bTaxIncluded)	{	// If the configuration says that prices in the database is with tax included
				$rc = $price / $taxFactor;
			} else {
				$rc = $price;
			}
		}
		return $rc;
	}


	/**
	 * return the price with tax mode considered
	 */
	public function getModePrice ($basketExtra, $taxMode, $price, $tax=true, $row, $bTaxIncluded=false, $bEnableTaxZero=false)	{

		$rc = $this->getPrice($basketExtra, $price, $tax, $row, $bTaxIncluded, $bEnableTaxZero);
		if ($taxMode == '2')	{
			$rc = round($rc, 2);
		}
		return $rc;
	}


	/** reduces price by discount for FE user **/
	static public function getDiscountPrice ($price, $discount = '')	{

		if (floatval($discount) != 0) {
			$price = $price - ($price * ($discount / 100));
		}
		return $price;
	}


	/**
	 * Returns the $price with either tax or not tax, based on if $tax is true or false.
	 * This function reads the TypoScript configuration to see whether prices in the database
	 * are entered with or without tax. That's why this function is needed.
	 */
	public function getPrice ($basketExtra, $price, $tax, $row, $bTaxIncluded=false, $bEnableTaxZero=false)	{
		$rc = 0;
		$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');

		$bTax = ($tax==1);
		$price = $this->toNumber(true, $price);

		if (isset($row['tax']) && strlen($row['tax'])) {
			$taxpercentage = $row['tax'];
		}

		if (doubleval($taxpercentage) == 0 && !$bEnableTaxZero && $this->priceConf['TAXpercentage'] != '')	{
			$taxpercentage = doubleval($this->priceConf['TAXpercentage']);
		}

//		Buch 'Der TYPO3 Webshop'
// 		if (doubleval($taxpercentage) == -1)  {
// 			$taxpercentage = 0;
// 		}

		$taxFactor = 1 + $taxpercentage / 100;
		// $bTaxIncluded = ($bTaxIncluded ? $bTaxIncluded : $this->conf['TAXincluded']);

		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
		if (isset($paymentshippingObj) && is_object($paymentshippingObj))	{
			$taxValue = '';
			if (isset($row['tax'])) {
				$taxValue = $row['tax'];
			}
			$taxFromShipping = $paymentshippingObj->getReplaceTaxPercentage($basketExtra, 'shipping', $taxValue);	// if set then this has a tax which will override the tax of the products
		}

		if (isset($taxFromShipping) && is_double($taxFromShipping))	{
			$newtaxFactor = 1 + $taxFromShipping / 100;
			// we need the net price in order to apply another tax
			if ($bTaxIncluded)	{
				$price = $price / $taxFactor;
				$bTaxIncluded = false;
			}
			$taxFactor = $newtaxFactor;
		}

		$rc = $this->getPriceTax($price, $bTax, $bTaxIncluded, $taxFactor);
		return $rc;
	} // getPrice


	// function using getPrice and considering a reduced price for resellers
	public function getResellerPrice ($basketExtra, $row, $tax=1, $priceNo='')	{
		$rc = 0;
		if (
			!tx_div2007_core::testInt($priceNo)
		) {
				// get reseller group number
			$priceNo = intval($this->priceConf['priceNoReseller']);
		}

		if ($priceNo > 0) {
			$rc = $this->getPrice($basketExtra, $row['price'.$priceNo], $tax, $row, $this->getTaxIncluded());
		}
		// normal price; if reseller price is zero then also the normal price applies
		if ($rc == 0) {
			$rc = $this->getPrice($basketExtra, $row['price'], $tax, $row, $this->getTaxIncluded());
		}
		return $rc;
	} // getResellerPrice


	static public function getPriceFieldArray ()	{
		return self::$priceFieldArray;
	}


	static public function getSkonto (
		$relativePrice,
		$priceNumTax,
		&$skonto,
		&$skontoTaxPerc
	) {
		$skonto = ($relativePrice - $priceNumTax);

		if (floatval($relativePrice) != 0) {
			$skontoTaxPerc = (($skonto / $relativePrice) * 100);
		} else {
			$skontoTaxPerc = 'undefined';
		}
	}

	static public function calculateEndPrice ($price, $row, $discountField, $discountRoundFormat, $roundFormat) {

		$maxDiscount = 0;

		if ($discountField != '' && isset($row[$discountField])) {
			$maxDiscount = $row[$discountField];
		}

		$discount = $GLOBALS['TSFE']->fe_user->user['tt_products_discount'];
		if ($discount > $maxDiscount) {
			$maxDiscount = $discount;
		}

		$price = self::getDiscountPrice($price, $maxDiscount);

		if ($maxDiscount != 0 && $discountRoundFormat != '') {
			$price = tx_ttproducts_api::roundPrice($price, $discountRoundFormat);
		}

		if (isset($row['calc']) && $row['calc'] > 0)	{
			$price = $row['calc'];
		}

		if ($roundFormat != '') {
			$price = tx_ttproducts_api::roundPrice($price, $roundFormat);
		}

		$result = $price;

		return $result;
	}


	// fetches all calculated prices for a row
	public function getPriceTaxArray (
		$discountPriceMode,
		$basketExtra,
		$fieldname,
		$roundFormat,
		$discountRoundFormat,
		$row,
		$discountField
	) {
		$internalRow = $row;
		$priceArray = array();
		$price0tax = $this->getResellerPrice($basketExtra, $internalRow, 1, 0);

		if ($fieldname == 'price')	{
			$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
			$tax = $taxObj->getFieldValue($basketExtra, $row, 'tax');
			$priceArray['taxperc'] = $tax;

			$internalRow['price'] =
				self::calculateEndPrice(
					$row['price'],
					$row,
					$discountField,
					$discountRoundFormat,
					$roundFormat
				);

			$priceArray['tax'] = $this->getResellerPrice($basketExtra, $internalRow, 1);
			$priceArray['notax'] = $this->getResellerPrice($basketExtra, $internalRow, 0);
			if ($priceArray['notax'] > $priceArray['tax'])	{
				$priceArray['notax'] = $priceArray['tax'];
			}
			$priceArray['0tax'] = $price0tax;
			$priceArray['0notax'] = $this->getResellerPrice($basketExtra, $row, 0, 0);
			$priceArray['unotax'] = $this->getPrice($basketExtra, ($internalRow['unit_factor'] > 0 ? ($priceArray['notax'] / $row['unit_factor']) : 0), false, $row, false);
			$priceArray['utax'] = $this->getPrice($basketExtra, $priceArray['unotax'], true, $row, false);;
			$priceArray['wnotax'] = $this->getPrice($basketExtra, ($row['weight'] > 0 ? ($priceArray['notax'] / $internalRow['weight']) : 0), false, $row, false);
			$priceArray['wtax'] = $this->getPrice($basketExtra, $priceArray['wnotax'], true, $row, false);

			self::getSkonto(
				$price0tax,
				$priceArray['tax'],
				$priceArray['skontotax'],
				$priceArray['skontotaxperc']
			);

			$priceArray['onlytax'] = $priceArray['tax'] - $priceArray['notax'];
		} else if (strpos($fieldname, 'price') === 0)	{

			$internalRow['price'] =
				self::calculateEndPrice(
					$row['price'],
					$row,
					$discountField,
					$discountRoundFormat,
					$roundFormat
				);

			if ($roundFormat != '') {
				$internalRow[$fieldname] = tx_ttproducts_api::roundPrice($internalRow[$fieldname], $roundFormat);
			}

			$pricelen = strlen('price');
			$priceNum = substr($fieldname, $pricelen /*, strlen($fieldName) - $pricelen*/);
			$priceArray[$priceNum . 'tax'] = $this->getPrice($basketExtra, $internalRow[$fieldname], 1, $row, $this->getTaxIncluded());
			$priceArray[$priceNum . 'notax'] = $this->getPrice($basketExtra, $internalRow[$fieldname], 0, $row, $this->getTaxIncluded());
			$priceArray[$priceNum . 'onlytax'] = $priceArray[$priceNum . 'tax'] - $priceArray[$priceNum . 'notax'];

			$relativePrice = 0;
			$priceNumTax = 0;

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
		} else if ($fieldname == 'directcost')	{
			$priceArray['dctax'] = $this->getPrice($basketExtra, $internalRow['directcost'], 1,$row, $this->getTaxIncluded());
			$priceArray['dcnotax'] = $this->getPrice($basketExtra, $internalRow['directcost'], 0, $row, $this->getTaxIncluded());
		} else {
			$value = $row[$fieldname];
			$priceArray['tax'] = $this->getPrice($basketExtra, $value, 1, $row, $this->priceConf['TAXincluded']);
			$priceArray['notax'] = $this->getPrice($basketExtra, $value, 0, $row, $this->priceConf['TAXincluded']);
			$priceArray['onlytax'] = $priceArray['tax'] - $priceArray['notax'];
		}

		if ($this->getTaxMode() == 2)	{
			foreach ($priceArray as $field => $v)	{
				$priceArray[$field] = round($priceArray[$field], 2);
			}
		}

		return $priceArray;
	}


	static public function &convertOldPriceArray ($row)	{
		$rc = array();
		foreach (self::$convertArray as $newField => $oldField)	{
			if (isset($row[$newField]))	{
				$rc[$oldField] = $row[$newField];
			}
		}
		return $rc;
	}


	static public function &convertNewPriceArray ($row)	{
		$rc = array();
		foreach (self::$convertArray as $newField => $oldField)	{
			if (isset($row[$oldField]))	{
				$rc[$newField] = $row[$oldField];
			}
		}
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/field/class.tx_ttproducts_field_price.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/field/class.tx_ttproducts_field_price.php']);
}


